<?php

namespace App\Modules\DirectChat\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatFileController extends Controller
{
    public function download(Request $request, int $messageId): StreamedResponse
    {
        // Signature validation is handled by the 'signed' middleware on the route.
        // Auth is handled by the 'auth:sanctum' middleware on the route.

        $message = Message::findOrFail($messageId);
        $metadata = $message->file_metadata;

        if (empty($metadata['disk_path'])) {
            abort(404, 'File not found.');
        }

        // Verify the authenticated user is a participant of the conversation
        if (! ConversationParticipant::where('conversation_id', $message->conversation_id)
            ->where('user_id', auth()->id())
            ->exists()) {
            abort(403, 'Access denied.');
        }

        $disk = Storage::disk('chat_private');

        if (! $disk->exists($metadata['disk_path'])) {
            abort(404, 'File not found.');
        }

        return $disk->download(
            $metadata['disk_path'],
            $metadata['original_name'] ?? 'download'
        );
    }
}
