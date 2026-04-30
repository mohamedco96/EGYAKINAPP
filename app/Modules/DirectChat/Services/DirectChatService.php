<?php

namespace App\Modules\DirectChat\Services;

use App\Models\User;
use App\Modules\DirectChat\Events\MessageDeleted;
use App\Modules\DirectChat\Events\MessageReacted;
use App\Modules\DirectChat\Events\MessageRead;
use App\Modules\DirectChat\Events\MessageSent;
use App\Modules\DirectChat\Jobs\SendChatNotificationJob;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Models\Message;
use App\Modules\DirectChat\Models\MessageReaction;
use App\Modules\DirectChat\Models\MessageRead as MessageReadModel;
use App\Modules\DirectChat\Resources\ConversationListResource;
use App\Modules\DirectChat\Resources\ConversationResource;
use App\Modules\DirectChat\Resources\MessageResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DirectChatService
{
    public function __construct(private readonly ChatFileService $fileService) {}

    // -------------------------------------------------------------------------
    // Conversations
    // -------------------------------------------------------------------------

    public function getConversations(int $userId, ?string $type = null): array
    {
        try {
            // Counts per type across ALL user conversations (not filtered by $type)
            $typeCounts = Conversation::forUser($userId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type');

            $counts = [
                'all' => $typeCounts->sum(),
                'private' => (int) ($typeCounts['private'] ?? 0),
                'case_group' => (int) ($typeCounts['case_group'] ?? 0),
                'social_group' => (int) ($typeCounts['social_group'] ?? 0),
            ];

            $conversations = Conversation::forUser($userId)
                ->with([
                    'participants',
                    'participantRecords',
                    'latestMessage.sender',
                ])
                ->when($type, fn ($q) => $q->where('type', $type))
                ->orderByDesc(function ($q) {
                    $q->select('created_at')
                        ->from('messages')
                        ->whereColumn('conversation_id', 'conversations.id')
                        ->latest()
                        ->limit(1);
                })
                ->paginate(20);

            // Load last 10 messages per conversation (first page for instant UI render)
            // Uses ROW_NUMBER() window function — single query, no PHP-side trimming
            $conversationIds = $conversations->pluck('id')->toArray();

            $allMessages = collect();

            if (! empty($conversationIds)) {
                $placeholders = implode(',', array_fill(0, count($conversationIds), '?'));

                $ranked = DB::select("
                    SELECT m.*
                    FROM (
                        SELECT *,
                               ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY created_at DESC) AS rn
                        FROM messages
                        WHERE conversation_id IN ({$placeholders})
                          AND deleted_at IS NULL
                    ) m
                    WHERE m.rn <= 10
                    ORDER BY m.conversation_id, m.created_at ASC
                ", $conversationIds);

                // Hydrate plain objects into Message models so relations can be eager-loaded
                $messageIds = array_column($ranked, 'id');

                $allMessages = Message::with(['sender', 'reactions.user'])
                    ->whereIn('id', $messageIds)
                    ->orderBy('created_at')
                    ->get()
                    ->groupBy('conversation_id');
            }

            // Single JOIN query: unread counts per conversation for the auth user
            $unreadCounts = DB::table('messages')
                ->join('conversation_participants as cp', function ($join) use ($userId) {
                    $join->on('cp.conversation_id', '=', 'messages.conversation_id')
                        ->where('cp.user_id', $userId);
                })
                ->whereIn('messages.conversation_id', $conversationIds)
                ->where('messages.sender_id', '!=', $userId)
                ->whereNull('messages.deleted_at')
                ->whereRaw('(cp.last_read_at IS NULL OR messages.created_at > cp.last_read_at)')
                ->groupBy('messages.conversation_id')
                ->selectRaw('messages.conversation_id, COUNT(*) as unread')
                ->pluck('unread', 'messages.conversation_id');

            // Single query: total message counts per conversation for has_more detection
            $messageCounts = DB::table('messages')
                ->whereIn('conversation_id', $conversationIds)
                ->whereNull('deleted_at')
                ->groupBy('conversation_id')
                ->selectRaw('conversation_id, COUNT(*) as total')
                ->pluck('total', 'conversation_id');

            // Attach all pre-computed data to each model instance
            $conversations->each(function ($conversation) use ($allMessages, $unreadCounts, $messageCounts) {
                $conversation->setRelation('recentMessages', $allMessages->get($conversation->id, collect()));
                $conversation->setAttribute('precomputed_unread', (int) ($unreadCounts[$conversation->id] ?? 0));
                $conversation->setAttribute('precomputed_has_more', ($messageCounts[$conversation->id] ?? 0) > 10);
            });

            $paginatedData = ConversationListResource::collection($conversations)->response()->getData(true);

            return [
                'value' => true,
                'message' => 'Conversations retrieved successfully.',
                'data' => array_merge(['counts' => $counts], $paginatedData),
                'status_code' => 200,
            ];
        } catch (\Throwable $e) {
            Log::error('DirectChat: getConversations failed', ['user_id' => $userId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to retrieve conversations.', 'data' => null, 'status_code' => 500];
        }
    }

    public function createConversation(array $data, int $creatorId): array
    {
        try {
            $type = $data['type'];
            $participantIds = array_unique(array_filter($data['participant_ids'], fn ($id) => $id !== $creatorId));

            // Validate role restrictions
            if ($type === 'case_group') {
                $creator = User::find($creatorId);
                if (! $creator?->hasRole('doctor')) {
                    return ['value' => false, 'message' => 'Only doctors can create case groups.', 'data' => null, 'status_code' => 403];
                }
                $nonDoctors = User::whereIn('id', $participantIds)->whereDoesntHave('roles', fn ($q) => $q->where('name', 'doctor'))->count();
                if ($nonDoctors > 0) {
                    return ['value' => false, 'message' => 'Case groups can only include doctors.', 'data' => null, 'status_code' => 422];
                }
            }

            // For private chats: return existing conversation if duplicate
            if ($type === 'private') {
                if (count($participantIds) !== 1) {
                    return ['value' => false, 'message' => 'Private conversations require exactly one other participant.', 'data' => null, 'status_code' => 422];
                }
                $otherId = $participantIds[0];
                $existing = $this->findPrivateConversation($creatorId, $otherId);
                if ($existing) {
                    $existing->load(['participants', 'creator']);

                    return ['value' => true, 'message' => 'Conversation already exists.', 'data' => new ConversationResource($existing), 'status_code' => 200];
                }
            }

            $conversation = DB::transaction(function () use ($data, $type, $creatorId, $participantIds) {
                $conversation = Conversation::create([
                    'type' => $type,
                    'name' => $data['name'] ?? null,
                    'description' => $data['description'] ?? null,
                    'created_by' => $creatorId,
                ]);

                // Add creator as admin
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $creatorId,
                    'role' => 'admin',
                    'joined_at' => now(),
                    'mute_notifications' => false,
                ]);

                // Add other participants as members
                foreach ($participantIds as $userId) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'role' => 'member',
                        'joined_at' => now(),
                        'mute_notifications' => $type === 'social_group',
                    ]);
                }

                return $conversation;
            });

            $conversation->load(['participants', 'creator']);

            Log::info('DirectChat: Conversation created', ['conversation_id' => $conversation->id, 'type' => $type, 'creator_id' => $creatorId]);

            return ['value' => true, 'message' => 'Conversation created successfully.', 'data' => new ConversationResource($conversation), 'status_code' => 201];
        } catch (\Throwable $e) {
            Log::error('DirectChat: createConversation failed', ['creator_id' => $creatorId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to create conversation.', 'data' => null, 'status_code' => 500];
        }
    }

    public function getConversationDetails(int $conversationId, int $userId): array
    {
        try {
            $conversation = Conversation::with(['participants', 'creator'])->find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if (! $this->isParticipant($conversationId, $userId)) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            return ['value' => true, 'message' => 'Conversation retrieved successfully.', 'data' => new ConversationResource($conversation), 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: getConversationDetails failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to retrieve conversation.', 'data' => null, 'status_code' => 500];
        }
    }

    public function updateConversation(int $conversationId, int $userId, array $data): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversation->type === 'private') {
                return ['value' => false, 'message' => 'Private conversations cannot be updated.', 'data' => null, 'status_code' => 422];
            }

            if (! $this->isAdmin($conversationId, $userId)) {
                return ['value' => false, 'message' => 'Only group admins can update conversation details.', 'data' => null, 'status_code' => 403];
            }

            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $data['image']->store('group_images', 'public');
            }

            $conversation->update($data);
            $conversation->load(['participants', 'creator']);

            return ['value' => true, 'message' => 'Conversation updated successfully.', 'data' => new ConversationResource($conversation), 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: updateConversation failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to update conversation.', 'data' => null, 'status_code' => 500];
        }
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    public function getMessages(int $conversationId, int $userId, ?int $before = null): array
    {
        try {
            if (! $this->isParticipant($conversationId, $userId)) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            $fetched = Message::with(['sender', 'reads.user', 'reactions.user', 'replyTo.sender'])
                ->where('conversation_id', $conversationId)
                ->when($before, fn ($q) => $q->where('id', '<', $before))
                ->orderByDesc('created_at')
                ->limit(31)
                ->get();

            $hasMore = $fetched->count() === 31;
            $messages = $fetched->take(30)->reverse()->values();

            // Auto-mark all fetched messages as read when the user loads them
            $this->markAsRead($conversationId, $userId);

            return [
                'value' => true,
                'message' => 'Messages retrieved successfully.',
                'data' => MessageResource::collection($messages),
                'has_more' => $hasMore,
                'status_code' => 200,
            ];
        } catch (\Throwable $e) {
            Log::error('DirectChat: getMessages failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to retrieve messages.', 'data' => null, 'status_code' => 500];
        }
    }

    public function sendDirectMessage(int $senderId, int $recipientId, array $data): array
    {
        try {
            if ($senderId === $recipientId) {
                return ['value' => false, 'message' => 'You cannot send a message to yourself.', 'data' => null, 'status_code' => 422];
            }

            if (! User::where('id', $recipientId)->exists()) {
                return ['value' => false, 'message' => 'Recipient not found.', 'data' => null, 'status_code' => 404];
            }

            // Find existing private conversation or create one on the fly
            $conversation = $this->findPrivateConversation($senderId, $recipientId);

            if (! $conversation) {
                $conversation = DB::transaction(function () use ($senderId, $recipientId) {
                    $conversation = Conversation::create([
                        'type' => 'private',
                        'created_by' => $senderId,
                    ]);

                    ConversationParticipant::insert([
                        [
                            'conversation_id' => $conversation->id,
                            'user_id' => $senderId,
                            'role' => 'admin',
                            'joined_at' => now(),
                            'mute_notifications' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'conversation_id' => $conversation->id,
                            'user_id' => $recipientId,
                            'role' => 'member',
                            'joined_at' => now(),
                            'mute_notifications' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);

                    return $conversation;
                });

                Log::info('DirectChat: Private conversation auto-created', [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $senderId,
                    'recipient_id' => $recipientId,
                ]);
            }

            return $this->sendMessage($conversation->id, $senderId, $data);
        } catch (\Throwable $e) {
            Log::error('DirectChat: sendDirectMessage failed', ['sender_id' => $senderId, 'recipient_id' => $recipientId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to send message.', 'data' => null, 'status_code' => 500];
        }
    }

    public function sendMessage(int $conversationId, int $senderId, array $data): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if (! $this->isParticipant($conversationId, $senderId)) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            if ($conversation->type === 'case_group') {
                $sender = User::find($senderId);
                if (! $sender?->hasRole('doctor')) {
                    return ['value' => false, 'message' => 'Only doctors can send messages in case groups.', 'data' => null, 'status_code' => 403];
                }
            }

            if (! empty($data['reply_to_id'])) {
                $replyTarget = Message::find($data['reply_to_id']);
                if (! $replyTarget || $replyTarget->conversation_id !== (int) $conversationId) {
                    return ['value' => false, 'message' => 'Invalid reply target.', 'data' => null, 'status_code' => 422];
                }
            }

            $fileMetadata = null;
            if ($data['type'] !== 'text' && isset($data['file']) && $data['file'] instanceof UploadedFile) {
                $fileMetadata = $this->fileService->uploadFile($data['file'], $data['type']);
            }

            $message = Message::create([
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'type' => $data['type'],
                'content' => $data['content'] ?? null,
                'file_metadata' => $fileMetadata,
                'reply_to_id' => $data['reply_to_id'] ?? null,
            ]);

            $conversation->touch();

            // Load all relations needed by MessageResource (used in broadcast payload and API response)
            // Loading here once avoids N+1 inside the resource and the broadcast serialization
            $message->load(['sender', 'replyTo.sender', 'reactions.user', 'reads.user']);

            // Broadcast to all participants in real-time (non-fatal if Reverb not running)
            try {
                broadcast(new MessageSent($message))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::warning('DirectChat: Broadcast failed (non-fatal)', [
                    'message_id' => $message->id,
                    'error' => $broadcastException->getMessage(),
                ]);
            }

            // Queue FCM push for offline participants (excludes muted, non-fatal)
            try {
                $recipientIds = ConversationParticipant::where('conversation_id', $conversationId)
                    ->where('user_id', '!=', $senderId)
                    ->where('mute_notifications', false)
                    ->pluck('user_id')
                    ->toArray();

                if (! empty($recipientIds)) {
                    SendChatNotificationJob::dispatch($message, $recipientIds);
                }
            } catch (\Throwable $notifyException) {
                Log::warning('DirectChat: Notification dispatch failed (non-fatal)', [
                    'message_id' => $message->id,
                    'error' => $notifyException->getMessage(),
                ]);
            }

            Log::info('DirectChat: Message sent', ['message_id' => $message->id, 'conversation_id' => $conversationId, 'sender_id' => $senderId]);

            return ['value' => true, 'message' => 'Message sent successfully.', 'data' => new MessageResource($message), 'status_code' => 201];
        } catch (\Throwable $e) {
            Log::error('DirectChat: sendMessage failed', [
                'conversation_id' => $conversationId,
                'sender_id' => $senderId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->map(fn ($f) => ($f['file'] ?? '?').':'.($f['line'] ?? '?'))->toArray(),
            ]);

            return ['value' => false, 'message' => 'Failed to send message.', 'data' => null, 'status_code' => 500];
        }
    }

    public function deleteMessage(int $messageId, int $userId, ?int $conversationId = null): array
    {
        try {
            $message = Message::find($messageId);

            if (! $message) {
                return ['value' => false, 'message' => 'Message not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversationId !== null && $message->conversation_id !== $conversationId) {
                return ['value' => false, 'message' => 'Message not found.', 'data' => null, 'status_code' => 404];
            }

            if ($message->sender_id !== $userId) {
                return ['value' => false, 'message' => 'You can only delete your own messages.', 'data' => null, 'status_code' => 403];
            }

            if (! $this->isParticipant($message->conversation_id, $userId)) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            $conversationId = $message->conversation_id;

            $message->delete(); // soft delete — MessageObserver handles file cleanup

            try {
                broadcast(new MessageDeleted($messageId, $conversationId))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::warning('DirectChat: MessageDeleted broadcast failed (non-fatal)', [
                    'message_id' => $messageId,
                    'error' => $broadcastException->getMessage(),
                ]);
            }

            return ['value' => true, 'message' => 'Message deleted.', 'data' => null, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: deleteMessage failed', ['message_id' => $messageId, 'user_id' => $userId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to delete message.', 'data' => null, 'status_code' => 500];
        }
    }

    public function markAsRead(int $conversationId, int $userId, ?int $upToMessageId = null): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->first();

            if (! $participant) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            $readCount = 0;
            $now = now();

            // For private and case_group: insert individual message_reads rows
            if (in_array($conversation->type, ['private', 'case_group'])) {
                // Single query: fetch unread message IDs + created_at in one shot
                $unreadMessages = Message::select('id', 'created_at')
                    ->where('conversation_id', $conversationId)
                    ->where('sender_id', '!=', $userId)
                    ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $userId))
                    ->when($upToMessageId, fn ($q) => $q->where('id', '<=', $upToMessageId))
                    ->orderByDesc('created_at')
                    ->get();

                $readCount = $unreadMessages->count();

                if ($readCount > 0) {
                    $rows = $unreadMessages->map(fn ($msg) => [
                        'message_id' => $msg->id,
                        'user_id' => $userId,
                        'read_at' => $now,
                    ])->toArray();

                    MessageReadModel::insertOrIgnore($rows);
                }

                // Reuse the already-fetched collection for last_read_at — no extra query
                $latestReadAt = $unreadMessages->first()?->created_at ?? $participant->last_read_at ?? $now;
            } else {
                // social_group: no message_reads rows, just get latest message timestamp
                $latestMessage = Message::where('conversation_id', $conversationId)
                    ->when($upToMessageId, fn ($q) => $q->where('id', '<=', $upToMessageId))
                    ->latest()
                    ->value('created_at');

                $latestReadAt = $latestMessage ?? $now;
            }

            // For all types: update the last_read_at pivot
            $lastReadMessageId = $upToMessageId;
            if (! $lastReadMessageId) {
                // Get the last message id for the broadcast payload only (cheap — already has index)
                $lastReadMessageId = Message::where('conversation_id', $conversationId)
                    ->latest()
                    ->value('id') ?? 0;
            }

            $participant->update(['last_read_at' => $latestReadAt]);

            // Broadcast read receipt to others in the conversation (non-fatal if Reverb not running)
            try {
                broadcast(new MessageRead(
                    conversationId: $conversationId,
                    userId: $userId,
                    lastReadMessageId: $lastReadMessageId,
                    readAt: $now->toISOString()
                ))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::warning('DirectChat: MessageRead broadcast failed (non-fatal)', [
                    'conversation_id' => $conversationId,
                    'error' => $broadcastException->getMessage(),
                ]);
            }

            return ['value' => true, 'message' => 'Messages marked as read.', 'data' => ['read_count' => $readCount], 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: markAsRead failed', ['conversation_id' => $conversationId, 'user_id' => $userId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to mark messages as read.', 'data' => null, 'status_code' => 500];
        }
    }

    public function reactToMessage(int $messageId, int $userId, string $reaction, int $conversationId): array
    {
        try {
            if (! $this->isParticipant($conversationId, $userId)) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            $message = Message::find($messageId);

            if (! $message) {
                return ['value' => false, 'message' => 'Message not found.', 'data' => null, 'status_code' => 404];
            }

            if ($message->conversation_id !== $conversationId) {
                return ['value' => false, 'message' => 'Message does not belong to this conversation.', 'data' => null, 'status_code' => 403];
            }

            $existing = MessageReaction::where('message_id', $messageId)
                ->where('user_id', $userId)
                ->where('reaction', $reaction)
                ->first();

            if ($existing) {
                $existing->delete();
                $action = 'removed';
            } else {
                MessageReaction::create([
                    'message_id' => $messageId,
                    'user_id' => $userId,
                    'reaction' => $reaction,
                ]);
                $action = 'added';
            }

            try {
                broadcast(new MessageReacted(
                    messageId: $messageId,
                    conversationId: $message->conversation_id,
                    userId: $userId,
                    userName: auth()->user()?->name ?? '',
                    reaction: $reaction,
                    action: $action
                ))->toOthers();
            } catch (\Throwable $broadcastException) {
                Log::warning('DirectChat: Broadcast failed for reaction (non-fatal)', [
                    'message_id' => $messageId,
                    'conversation_id' => $conversationId,
                    'error' => $broadcastException->getMessage(),
                ]);
            }

            $updatedReactions = MessageReaction::with('user')
                ->where('message_id', $messageId)
                ->get()
                ->groupBy('reaction')
                ->map(fn ($group, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'users' => $group->map(fn ($r) => ['id' => $r->user_id, 'name' => $r->user?->name])->values(),
                ])->values();

            return ['value' => true, 'message' => "Reaction {$action}.", 'data' => ['reactions' => $updatedReactions], 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: reactToMessage failed', ['message_id' => $messageId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to process reaction.', 'data' => null, 'status_code' => 500];
        }
    }

    // -------------------------------------------------------------------------
    // Participation
    // -------------------------------------------------------------------------

    public function joinConversation(int $conversationId, int $userId): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversation->type !== 'social_group') {
                return ['value' => false, 'message' => 'You can only join open social groups.', 'data' => null, 'status_code' => 403];
            }

            if ($this->isParticipant($conversationId, $userId)) {
                return ['value' => false, 'message' => 'You are already a member of this conversation.', 'data' => null, 'status_code' => 422];
            }

            ConversationParticipant::create([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'role' => 'member',
                'joined_at' => now(),
                'mute_notifications' => true, // default muted for social groups
            ]);

            return ['value' => true, 'message' => 'Joined conversation successfully.', 'data' => null, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: joinConversation failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to join conversation.', 'data' => null, 'status_code' => 500];
        }
    }

    public function leaveConversation(int $conversationId, int $userId): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversation->type === 'private') {
                return ['value' => false, 'message' => 'You cannot leave a private conversation.', 'data' => null, 'status_code' => 422];
            }

            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->first();

            if (! $participant) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 404];
            }

            // If leaving user is the only admin, promote the earliest member
            if ($participant->role === 'admin') {
                $otherAdmins = ConversationParticipant::where('conversation_id', $conversationId)
                    ->where('user_id', '!=', $userId)
                    ->where('role', 'admin')
                    ->count();

                if ($otherAdmins === 0) {
                    $nextMember = ConversationParticipant::where('conversation_id', $conversationId)
                        ->where('user_id', '!=', $userId)
                        ->orderBy('joined_at')
                        ->first();

                    if ($nextMember) {
                        $nextMember->update(['role' => 'admin']);
                    }
                }
            }

            $participant->delete();

            return ['value' => true, 'message' => 'Left conversation successfully.', 'data' => null, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: leaveConversation failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to leave conversation.', 'data' => null, 'status_code' => 500];
        }
    }

    public function addParticipants(int $conversationId, int $adminId, array $userIds): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversation->type === 'private') {
                return ['value' => false, 'message' => 'Cannot add participants to a private conversation.', 'data' => null, 'status_code' => 422];
            }

            if (! $this->isAdmin($conversationId, $adminId)) {
                return ['value' => false, 'message' => 'Only group admins can add participants.', 'data' => null, 'status_code' => 403];
            }

            if ($conversation->type === 'case_group') {
                $nonDoctors = User::whereIn('id', $userIds)->whereDoesntHave('roles', fn ($q) => $q->where('name', 'doctor'))->count();
                if ($nonDoctors > 0) {
                    return ['value' => false, 'message' => 'Case groups can only include doctors.', 'data' => null, 'status_code' => 422];
                }
            }

            // Pre-fetch existing participants to avoid N+1
            $existingIds = ConversationParticipant::where('conversation_id', $conversationId)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->flip();

            $newParticipants = [];
            $now = now();
            foreach ($userIds as $userId) {
                if (! $existingIds->has($userId)) {
                    $newParticipants[] = [
                        'conversation_id' => $conversationId,
                        'user_id' => $userId,
                        'role' => 'member',
                        'joined_at' => $now,
                        'mute_notifications' => $conversation->type === 'social_group',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $added = count($newParticipants);
            if ($added > 0) {
                ConversationParticipant::insert($newParticipants);
            }

            Log::info('DirectChat: Participants added', ['conversation_id' => $conversationId, 'added' => $added]);

            return ['value' => true, 'message' => "{$added} participant(s) added.", 'data' => null, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: addParticipants failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to add participants.', 'data' => null, 'status_code' => 500];
        }
    }

    public function removeParticipant(int $conversationId, int $adminId, int $userId): array
    {
        try {
            $conversation = Conversation::find($conversationId);

            if (! $conversation) {
                return ['value' => false, 'message' => 'Conversation not found.', 'data' => null, 'status_code' => 404];
            }

            if ($conversation->type === 'private') {
                return ['value' => false, 'message' => 'Cannot remove participants from private conversations.', 'data' => null, 'status_code' => 422];
            }

            if (! $this->isAdmin($conversationId, $adminId)) {
                return ['value' => false, 'message' => 'Only group admins can remove participants.', 'data' => null, 'status_code' => 403];
            }

            if ($userId === $adminId) {
                return ['value' => false, 'message' => 'Use the leave endpoint to remove yourself.', 'data' => null, 'status_code' => 422];
            }

            $deleted = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->delete();

            if (! $deleted) {
                return ['value' => false, 'message' => 'User is not a member of this conversation.', 'data' => null, 'status_code' => 404];
            }

            return ['value' => true, 'message' => 'Participant removed successfully.', 'data' => null, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: removeParticipant failed', ['conversation_id' => $conversationId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to remove participant.', 'data' => null, 'status_code' => 500];
        }
    }

    public function toggleMute(int $conversationId, int $userId, bool $mute): array
    {
        try {
            $participant = ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->first();

            if (! $participant) {
                return ['value' => false, 'message' => 'You are not a member of this conversation.', 'data' => null, 'status_code' => 403];
            }

            $participant->update(['mute_notifications' => $mute]);

            return [
                'value' => true,
                'message' => $mute ? 'Notifications muted.' : 'Notifications unmuted.',
                'data' => ['mute_notifications' => $mute],
                'status_code' => 200,
            ];
        } catch (\Throwable $e) {
            Log::error('DirectChat: toggleMute failed', ['conversation_id' => $conversationId, 'user_id' => $userId, 'error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Failed to update mute setting.', 'data' => null, 'status_code' => 500];
        }
    }

    public function searchUsers(string $query, int $userId): array
    {
        try {
            if (strlen(trim($query)) < 2) {
                return ['value' => false, 'message' => 'Search query must be at least 2 characters.', 'data' => null, 'status_code' => 422];
            }

            $users = User::where('id', '!=', $userId)
                ->where(fn ($q) => $q
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('lname', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                )
                ->select('id', 'name', 'lname', 'image', 'specialty')
                ->limit(20)
                ->get();

            return ['value' => true, 'message' => 'Users retrieved.', 'data' => $users, 'status_code' => 200];
        } catch (\Throwable $e) {
            Log::error('DirectChat: searchUsers failed', ['error' => $e->getMessage()]);

            return ['value' => false, 'message' => 'Search failed.', 'data' => null, 'status_code' => 500];
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isParticipant(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function isAdmin(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->exists();
    }

    private function findPrivateConversation(int $userA, int $userB): ?Conversation
    {
        return Conversation::where('type', 'private')
            ->whereHas('participantRecords', fn ($q) => $q->where('user_id', $userA))
            ->whereHas('participantRecords', fn ($q) => $q->where('user_id', $userB))
            ->first();
    }
}
