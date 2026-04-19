<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\ConversationParticipant;
use App\Modules\DirectChat\Models\Message;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

trait ChatTestHelpers
{
    protected function chatDoctorUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_type' => 'medical_statistics',
        ], $attributes));

        $role = Role::firstOrCreate(['name' => 'doctor']);
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function chatNormalUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'user_type' => 'normal',
        ], $attributes));

        $role = Role::firstOrCreate(['name' => 'user']);
        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    /** Create a private conversation between two users (creator is admin). */
    protected function makePrivateConversation(User $userA, User $userB): Conversation
    {
        $conversation = Conversation::factory()->private()->create(['created_by' => $userA->id]);

        ConversationParticipant::factory()->admin()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $userB->id,
        ]);

        return $conversation;
    }

    /**
     * Create a private conversation between two users without changing the authenticated user.
     * Useful when building fixtures without affecting auth state.
     */
    protected function makePrivateConversationFor(User $userA, User $userB): Conversation
    {
        $conversation = Conversation::factory()->private()->create(['created_by' => $userA->id]);

        ConversationParticipant::factory()->admin()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
        ]);
        ConversationParticipant::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $userB->id,
        ]);

        return $conversation;
    }

    /** Create a group conversation (social_group by default) with $creator as admin. */
    protected function makeGroupConversation(User $creator, string $type = 'social_group', string $name = 'Test Group'): Conversation
    {
        $conversation = Conversation::factory()->create([
            'type' => $type,
            'name' => $name,
            'created_by' => $creator->id,
        ]);

        ConversationParticipant::factory()->admin()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $creator->id,
        ]);

        return $conversation;
    }

    /** Send a text message directly via the service (bypasses broadcast). */
    protected function makeMessage(Conversation $conversation, User $sender, string $content = 'Hello!'): Message
    {
        return Message::factory()->text($content)->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
        ]);
    }
}
