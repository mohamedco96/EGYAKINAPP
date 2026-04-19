<?php

namespace App\Modules\DirectChat\Resources;

use App\Modules\DirectChat\Services\ChatFileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationListResource extends JsonResource
{
    // Resolved once per collection render — not per message
    private static ?ChatFileService $fileService = null;

    private static function fileService(): ChatFileService
    {
        return static::$fileService ??= app(ChatFileService::class);
    }

    public function toArray(Request $request): array
    {
        $authId = $request->user()->id;

        // Other participant (private chats only)
        $otherParticipant = null;
        if ($this->type === 'private') {
            $other = $this->participants->firstWhere('id', '!=', $authId);
            if ($other) {
                $otherParticipant = [
                    'id' => $other->id,
                    'name' => $other->name,
                    'lname' => $other->lname,
                    'image' => $other->image,
                    'specialty' => $other->specialty,
                ];
            }
        }

        // All participants for groups
        $participants = null;
        if ($this->type !== 'private') {
            $participants = $this->participants->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'lname' => $u->lname,
                'image' => $u->image,
                'specialty' => $u->specialty,
                'role' => $u->pivot->role,
            ])->values();
        }

        // Unread count — pre-computed in DirectChatService::getConversations() via batch JOIN query
        $myParticipant = $this->participantRecords->firstWhere('user_id', $authId);
        $unreadCount = $this->getAttribute('precomputed_unread') ?? 0;

        // Last 10 messages (pre-loaded by service — no extra query)
        $recentMessages = $this->relationLoaded('recentMessages')
            ? $this->getRelation('recentMessages')->map(fn ($msg) => $this->formatMessage($msg))
            : collect();

        // Latest message preview for conversation list row
        $latestMessage = $this->latestMessage;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->type === 'private' ? null : $this->name,
            'description' => $this->type === 'private' ? null : $this->description,
            'image' => $this->type === 'private' ? null : $this->image,
            'my_role' => $myParticipant?->role,
            'mute_notifications' => (bool) $myParticipant?->mute_notifications,
            'other_participant' => $otherParticipant,
            'participants' => $participants,
            'unread_count' => $unreadCount,
            'latest_message' => $latestMessage ? [
                'id' => $latestMessage->id,
                'type' => $latestMessage->type,
                'content' => $latestMessage->type === 'text' ? $latestMessage->content : null,
                'file_label' => $latestMessage->type !== 'text'
                    ? $this->fileLabel($latestMessage->type, $latestMessage->file_metadata)
                    : null,
                'sender_id' => $latestMessage->sender_id,
                'sender_name' => trim(($latestMessage->sender?->name ?? '').' '.($latestMessage->sender?->lname ?? '')),
                'created_at' => $latestMessage->created_at?->toISOString(),
            ] : null,
            // First page of messages — client uses these immediately on open
            // For older messages: GET /api/v3/chat/conversations/{id}/messages?before={oldest_id}
            'messages' => $recentMessages->values(),
            'messages_has_more' => (bool) $this->getAttribute('precomputed_has_more'),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function formatMessage($msg): array
    {
        $fileUrl = null;
        if ($msg->type !== 'text' && $msg->file_metadata) {
            // Service resolved once per collection via static cache — not per message
            $fileUrl = static::fileService()->getFileUrl($msg->id);
        }

        // reactions.user is eager-loaded by the service batch query — no lazy loads here
        $reactions = $msg->relationLoaded('reactions')
            ? $msg->reactions->groupBy('reaction')->map(fn ($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'users' => $group->map(fn ($r) => [
                    'id' => $r->user_id,
                    // user is eager-loaded via reactions.user — safe to access
                    'name' => $r->relationLoaded('user') ? $r->user?->name : null,
                ])->values(),
            ])->values()
            : collect();

        return [
            'id' => $msg->id,
            'sender' => $msg->relationLoaded('sender') ? [
                'id' => $msg->sender?->id,
                'name' => $msg->sender?->name,
                'lname' => $msg->sender?->lname,
                'image' => $msg->sender?->image,
            ] : null,
            'type' => $msg->type,
            'content' => $msg->content,
            'file_metadata' => $msg->type !== 'text' ? [
                'original_name' => $msg->file_metadata['original_name'] ?? null,
                'mime_type' => $msg->file_metadata['mime_type'] ?? null,
                'size_bytes' => $msg->file_metadata['size_bytes'] ?? null,
            ] : null,
            'file_url' => $fileUrl,
            'reactions' => $reactions,
            'created_at' => $msg->created_at?->toISOString(),
        ];
    }

    private function fileLabel(string $type, ?array $metadata): string
    {
        return match ($type) {
            'image' => '[Image]',
            'voice' => '[Voice Message]',
            'file' => '[File: '.($metadata['original_name'] ?? 'attachment').']',
            default => '[Attachment]',
        };
    }
}
