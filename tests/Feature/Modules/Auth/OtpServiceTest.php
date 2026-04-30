<?php

namespace Tests\Feature\Modules\Auth;

use App\Modules\Auth\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OtpService::class);
    }

    // Inserts a raw OTP row, deliberately storing validity as a string to
    // reproduce the production bug where PDO returns the integer column as a string.
    private function insertRawOtp(
        string $identifier,
        string $token,
        bool $valid,
        int $validityMinutes,
        string $createdAt
    ): void {
        DB::table('otps')->insert([
            'identifier' => $identifier,
            'token' => $token,
            'valid' => $valid,
            'validity' => (string) $validityMinutes, // string on purpose — reproduces the bug
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /** @test */
    public function it_does_not_throw_carbon_type_error_when_validity_is_a_string(): void
    {
        // Exact production scenario: PDO returns the integer column as "10" (string).
        // Previously caused: rawAddUnit(): Argument #3 ($value) must be of type int|float, string given
        $this->insertRawOtp('user@example.com', '1234', true, 10, now()->subMinutes(3)->toDateTimeString());

        // Must not throw — the fix is the explicit (int) cast in validateByIdentifier
        $result = $this->service->validateByIdentifier('user@example.com', '1234');

        $this->assertTrue($result->status);
    }

    /** @test */
    public function it_returns_valid_for_a_correct_unexpired_otp(): void
    {
        $this->insertRawOtp('user@example.com', '1234', true, 10, now()->subMinutes(5)->toDateTimeString());

        $result = $this->service->validateByIdentifier('user@example.com', '1234');

        $this->assertTrue($result->status);
        $this->assertSame('OTP is valid', $result->message);
    }

    /** @test */
    public function it_marks_the_otp_as_consumed_after_successful_validation(): void
    {
        $this->insertRawOtp('user@example.com', '1234', true, 10, now()->subMinutes(2)->toDateTimeString());

        $this->service->validateByIdentifier('user@example.com', '1234');

        $this->assertFalse((bool) DB::table('otps')->where('identifier', 'user@example.com')->value('valid'));
    }

    /** @test */
    public function it_returns_expired_when_the_validity_window_has_passed(): void
    {
        $this->insertRawOtp('user@example.com', '1234', true, 10, now()->subMinutes(11)->toDateTimeString());

        $result = $this->service->validateByIdentifier('user@example.com', '1234');

        $this->assertFalse($result->status);
        $this->assertSame('OTP Expired', $result->message);
    }

    /** @test */
    public function it_returns_not_valid_for_an_already_used_otp(): void
    {
        $this->insertRawOtp('user@example.com', '1234', false, 10, now()->subMinutes(2)->toDateTimeString());

        $result = $this->service->validateByIdentifier('user@example.com', '1234');

        $this->assertFalse($result->status);
        $this->assertSame('OTP is not valid', $result->message);
    }

    /** @test */
    public function it_returns_does_not_exist_for_an_unknown_token(): void
    {
        $result = $this->service->validateByIdentifier('nobody@example.com', '9999');

        $this->assertFalse($result->status);
        $this->assertSame('OTP does not exist', $result->message);
    }

    /** @test */
    public function it_rejects_a_wrong_token_for_a_valid_identifier(): void
    {
        $this->insertRawOtp('user@example.com', '1234', true, 10, now()->subMinutes(2)->toDateTimeString());

        $result = $this->service->validateByIdentifier('user@example.com', '0000');

        $this->assertFalse($result->status);
    }
}
