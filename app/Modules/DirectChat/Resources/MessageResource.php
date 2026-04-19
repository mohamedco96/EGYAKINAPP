<?php

namespace App\Modules\DirectChat\Resources;

use App\Modules\DirectChat\Services\ChatFileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    // Resolved once per collection render — not per message
    private static ?ChatFileService $fileService = null;

    private static function fileService(): ChatFileService
    {
        return static::$fileService ??= app(ChatFileService::class);
    }

    public function toArray(Request $request): array
    {
        $fileUrl = null;
        if ($this->type !== 'text' && $this->file_metadata) {
            $fileUrl = static::fileService()->getFileUrl($this->id);
        }

        // reactions.user is eager-loaded via reactions.user — no lazy loads
        $reactions = $this->whenLoaded('reactions', function () {
            return $this->reactions
                ->groupBy('reaction')
                ->map(fn ($group, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'users' => $group->map(fn ($r) => [
                        'id' => $r->user_id,
                        // user is eager-loaded — access safely
                        'name' => $r->relationLoaded('user') ? $r->user?->name : null,
                        'lname' => $r->relationLoaded('user') ? $r->user?->lname : null,
                    ])->values(),
                ])->values();
        });

        // reads.user is eager-loaded via reads.user — no lazy loads
        $reads = $this->whenLoaded('reads', fn () => $this->reads->map(fn ($r) => [
            'user_id' => $r->user_id,
            'name' => $r->relationLoaded('user') ? $r->user?->name : null,
            'lname' => $r->relationLoaded('user') ? $r->user?->lname : null,
            'read_at' => $r->read_at?->toISOString(),
        ])->values()
        );

        $replyTo = $this->whenLoaded('replyTo', fn () => $this->replyTo ? [
            'id' => $this->replyTo->id,
            'type' => $this->replyTo->type,
            'content' => $this->replyTo->content,
            'sender_id' => $this->replyTo->sender_id,
            'sender' => $this->replyTo->relationLoaded('sender') ? [
                'id' => $this->replyTo->sender?->id,
                'name' => $this->replyTo->sender?->name,
                'lname' => $this->replyTo->sender?->lname,
            ] : null,
        ] : null);

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => $this->whenLoaded('sender', fn () => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'lname' => $this->sender->lname,
                'image' => $this->sender->image,
                'specialty' => $this->sender->specialty,
            ]),
            'type' => $this->type,
            'content' => $this->content,
            'file_metadata' => $this->type !== 'text' ? [
                'original_name' => $this->file_metadata['original_name'] ?? null,
                'mime_type' => $this->file_metadata['mime_type'] ?? null,
                'size_bytes' => $this->file_metadata['size_bytes'] ?? null,
            ] : null,
            'file_url' => $fileUrl,
            'reply_to' => $replyTo,
            'reads' => $reads,
            'reads_count' => $this->whenLoaded('reads', fn () => $this->reads->count()),
            'reactions' => $reactions,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
