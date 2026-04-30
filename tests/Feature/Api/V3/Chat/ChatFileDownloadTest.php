<?php

namespace Tests\Feature\Api\V3\Chat;

use App\Models\User;
use App\Modules\DirectChat\Models\Conversation;
use App\Modules\DirectChat\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for the chat file download endpoint:
 *
 * GET /api/v3/chat/files/{messageId}  (requires signed URL + auth:sanctum)
 */
class ChatFileDownloadTest extends TestCase
{
    use ChatTestHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'doctor']);
        Role::firstOrCreate(['name' => 'user']);
        Storage::fake('chat_private');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Generate a valid signed URL for the file download route. */
    private function signedUrl(int $messageId): string
    {
        return URL::signedRoute('chat.file.download', ['messageId' => $messageId]);
    }

    /** Create a message with file_metadata pointing to a fake uploaded file. */
    private function makeFileMessage(
        Conversation $conversation,
        User $sender,
        string $diskPath = 'uploads/test.pdf',
        string $originalName = 'document.pdf'
    ): Message {
        Storage::disk('chat_private')->put($diskPath, 'fake file contents');

        return Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'type' => 'file',
            'content' => null,
            'file_metadata' => [
                'disk_path' => $diskPath,
                'original_name' => $originalName,
                'mime_type' => 'application/pdf',
                'size' => 1024,
            ],
        ]);
    }

    // =========================================================================
    // Happy path
    // =========================================================================

    /** @test */
    public function test_participant_can_download_file_via_signed_url(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeFileMessage($conversation, $other);

        $this->actingAs($user);

        $this->get($this->signedUrl($message->id))
            ->assertSuccessful();
    }

    // =========================================================================
    // Authentication & signature
    // =========================================================================

    /** @test */
    public function test_unsigned_url_is_rejected(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeFileMessage($conversation, $other);

        $this->actingAs($user);

        // Hit the route without a signature
        $this->get("/api/v3/chat/files/{$message->id}")
            ->assertStatus(403); // signed middleware returns 403 for invalid/missing signature
    }

    /** @test */
    public function test_tampered_signed_url_is_rejected(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);
        $message = $this->makeFileMessage($conversation, $other);

        $this->actingAs($user);

        $signed = $this->signedUrl($message->id);
        $tampered = $signed.'&tampered=1';

        $this->get($tampered)->assertStatus(403);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_download_file(): void
    {
        // Build a message using factories directly (no Sanctum::actingAs)
        $userA = User::factory()->create(['email_verified_at' => now()]);
        $userB = User::factory()->create(['email_verified_at' => now()]);
        $conversation = $this->makePrivateConversationFor($userA, $userB);
        $message = $this->makeFileMessage($conversation, $userB);

        // No actingAs call — request is unauthenticated
        $this->getJson($this->signedUrl($message->id))
            ->assertUnauthorized();
    }

    // =========================================================================
    // Authorization — participant check
    // =========================================================================

    /** @test */
    public function test_non_participant_cannot_download_file(): void
    {
        $owner = $this->chatNormalUser();
        $outsider = $this->chatNormalUser();
        $conversation = $this->makeGroupConversation($owner, 'social_group', 'Group');
        $message = $this->makeFileMessage($conversation, $owner);

        $this->actingAs($outsider);

        $this->get($this->signedUrl($message->id))
            ->assertForbidden();
    }

    // =========================================================================
    // Not found cases
    // =========================================================================

    /** @test */
    public function test_download_returns_404_for_non_existent_message(): void
    {
        $user = $this->chatNormalUser();
        $this->actingAs($user);

        $this->get($this->signedUrl(99999))
            ->assertNotFound();
    }

    /** @test */
    public function test_download_returns_404_when_message_has_no_file_metadata(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        // Text message — no file_metadata
        $message = $this->makeMessage($conversation, $other, 'just text');

        $this->actingAs($user);

        $this->get($this->signedUrl($message->id))
            ->assertNotFound();
    }

    /** @test */
    public function test_download_returns_404_when_file_missing_from_disk(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        // Message metadata points to a file that does NOT exist on disk
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'type' => 'file',
            'file_metadata' => [
                'disk_path' => 'uploads/ghost.pdf',
                'original_name' => 'ghost.pdf',
            ],
        ]);

        $this->actingAs($user);

        $this->get($this->signedUrl($message->id))
            ->assertNotFound();
    }

    /** @test */
    public function test_download_returns_404_when_disk_path_is_empty(): void
    {
        $user = $this->chatNormalUser();
        $other = $this->chatNormalUser();
        $conversation = $this->makePrivateConversation($user, $other);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'type' => 'file',
            'file_metadata' => [
                'disk_path' => '', // empty
                'original_name' => 'file.pdf',
            ],
        ]);

        $this->actingAs($user);

        $this->get($this->signedUrl($message->id))
            ->assertNotFound();
    }
}
