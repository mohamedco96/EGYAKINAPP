<?php

namespace App\Modules\DirectChat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DirectChat\Events\UserTyping;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Requests\CreateConversationRequest;
use App\Modules\DirectChat\Requests\ManageParticipantsRequest;
use App\Modules\DirectChat\Requests\ReactToMessageRequest;
use App\Modules\DirectChat\Requests\SendMessageRequest;
use App\Modules\DirectChat\Requests\UpdateConversationRequest;
use App\Modules\DirectChat\Services\DirectChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectChatController extends Controller
{
    public function __construct(private readonly DirectChatService $chatService) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->chatService->getConversations(
            Auth::id(),
            $request->query('type')
        );

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function store(CreateConversationRequest $request): JsonResponse
    {
        $result = $this->chatService->createConversation(
            $request->validated(),
            Auth::id()
        );

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function show(int $id): JsonResponse
    {
        $result = $this->chatService->getConversationDetails($id, Auth::id());

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function update(UpdateConversationRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        $result = $this->chatService->updateConversation($id, Auth::id(), $data);

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function messages(Request $request, int $id): JsonResponse
    {
        $result = $this->chatService->getMessages(
            $id,
            Auth::id(),
            $request->query('before') ? (int) $request->query('before') : null
        );

        return response()->json([
            'value' => $result['value'],
            'message' => $result['message'],
            'data' => $result['data'],
            'has_more' => $result['has_more'] ?? false,
        ], $result['status_code']);
    }

    public function sendMessage(SendMessageRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file');
        }

        $result = $this->chatService->sendMessage($id, Auth::id(), $data);

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function sendDirect(SendMessageRequest $request, int $userId): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file');
        }

        $result = $this->chatService->sendDirectMessage(Auth::id(), $userId, $data);

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function deleteMessage(int $id, int $messageId): JsonResponse
    {
        $result = $this->chatService->deleteMessage($messageId, Auth::id());

        return response()->json(['value' => $result['value'], 'message' => $result['message']], $result['status_code']);
    }

    public function react(ReactToMessageRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->chatService->reactToMessage(
            $validated['message_id'],
            Auth::id(),
            $validated['reaction'],
            $id
        );

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function join(int $id): JsonResponse
    {
        $result = $this->chatService->joinConversation($id, Auth::id());

        return response()->json(['value' => $result['value'], 'message' => $result['message']], $result['status_code']);
    }

    public function leave(int $id): JsonResponse
    {
        $result = $this->chatService->leaveConversation($id, Auth::id());

        return response()->json(['value' => $result['value'], 'message' => $result['message']], $result['status_code']);
    }

    public function addParticipants(ManageParticipantsRequest $request, int $id): JsonResponse
    {
        $result = $this->chatService->addParticipants($id, Auth::id(), $request->validated()['user_ids']);

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function removeParticipant(int $id, int $userId): JsonResponse
    {
        $result = $this->chatService->removeParticipant($id, Auth::id(), $userId);

        return response()->json(['value' => $result['value'], 'message' => $result['message']], $result['status_code']);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $result = $this->chatService->searchUsers(
            $request->query('q', ''),
            Auth::id()
        );

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function mute(Request $request, int $id): JsonResponse
    {
        $mute = (bool) $request->input('mute', true);

        $result = $this->chatService->toggleMute($id, Auth::id(), $mute);

        return response()->json(['value' => $result['value'], 'message' => $result['message'], 'data' => $result['data']], $result['status_code']);
    }

    public function typing(Request $request, int $id): JsonResponse
    {
        if (! ConversationParticipant::where('conversation_id', $id)
            ->where('user_id', Auth::id())
            ->exists()) {
            return response()->json(['value' => false, 'message' => 'You are not a member of this conversation.'], 403);
        }

        broadcast(new UserTyping(
            conversationId: $id,
            userId: Auth::id(),
            userName: Auth::user()->name ?? '',
            isTyping: (bool) $request->input('is_typing', true)
        ))->toOthers();

        return response()->json(['value' => true, 'message' => 'Typing status broadcast.'], 200);
    }
}
