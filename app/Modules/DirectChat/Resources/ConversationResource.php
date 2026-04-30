<?php

namespace App\Modules\DirectChat\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'image' => $this->image,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'lname' => $this->creator->lname,
                'image' => $this->creator->image,
            ]),
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'lname' => $user->lname,
                'image' => $user->image,
                'specialty' => $user->specialty,
                'role' => $user->pivot->role,
                'joined_at' => $user->pivot->joined_at,
                'mute_notifications' => (bool) $user->pivot->mute_notifications,
            ])
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
