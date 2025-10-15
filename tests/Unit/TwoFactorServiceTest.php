<?php

namespace Tests\Unit;

use App\Models\OtpToken;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected TwoFactorService $twoFactorService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->twoFactorService = new TwoFactorService();
        $this->user = User::factory()->create([
            '2fa_enabled' => false,
            '2fa_channel' => null,
            '2fa_identifier' => null,
        ]);
    }

    /** @test */
    public function it_can_get_user_two_factor_status_when_disabled()
    {
        $status = $this->twoFactorService->getUserTwoFactorStatus($this->user);

        $this->assertFalse($status['enabled']);
        $this->assertNull($status['channel']);
        $this->assertNull($status['identifier']);
    }

    /** @test */
    public function it_can_get_user_two_factor_status_when_enabled()
    {
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com',
        ]);

        $status = $this->twoFactorService->getUserTwoFactorStatus($this->user);

        $this->assertTrue($status['enabled']);
        $this->assertEquals(['email'], $status['channel']);
        $this->assertEquals('test@example.com', $status['identifier']);
    }

    /** @test */
    public function it_can_enable_two_factor_authentication()
    {
        $result = $this->twoFactorService->enableTwoFactor(
            $this->user,
            'email',
            'test@example.com'
        );

        $this->assertTrue($result);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->{'2fa_enabled'});
        $this->assertEquals(['email'], json_decode($this->user->{'2fa_channel'}, true));
        $this->assertEquals('test@example.com', $this->user->{'2fa_identifier'});
    }

    /** @test */
    public function it_can_enable_two_factor_with_telegram_channel()
    {
        $result = $this->twoFactorService->enableTwoFactor(
            $this->user,
            'telegram',
            '123456789'
        );

        $this->assertTrue($result);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->{'2fa_enabled'});
        $this->assertEquals(['telegram'], json_decode($this->user->{'2fa_channel'}, true));
        $this->assertEquals('123456789', $this->user->{'2fa_identifier'});
    }

    /** @test */
    public function it_can_disable_two_factor_authentication()
    {
        // First enable 2FA
        $this->user->update([
            '2fa_enabled' => true,
            '2fa_channel' => json_encode(['email']),
            '2fa_identifier' => 'test@example.com',
        ]);

        // Create some OTP tokens
        OtpToken::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $result = $this->twoFactorService->disableTwoFactor($this->user);

        $this->assertTrue($result);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->{'2fa_enabled'});
        $this->assertNull($this->user->{'2fa_channel'});
        $this->assertNull($this->user->{'2fa_identifier'});

        // Verify OTP tokens were deleted
        $this->assertEquals(0, OtpToken::where('user_id', $this->user->id)->count());
    }

    /** @test */
    public function it_can_check_if_user_has_two_factor_enabled()
    {
        // Initially disabled
        $this->assertFalse($this->twoFactorService->hasTwoFactorEnabled($this->user));

        // Enable 2FA
        $this->user->update(['2fa_enabled' => 1]);
        $this->assertTrue($this->twoFactorService->hasTwoFactorEnabled($this->user));
    }

    /** @test */
    public function it_can_get_two_factor_channels()
    {
        // Initially empty
        $this->assertEquals([], $this->twoFactorService->getTwoFactorChannels($this->user));

        // Enable with single channel
        $this->user->update([
            '2fa_enabled' => 1,
            '2fa_channel' => json_encode(['email'])
        ]);
        $this->assertEquals(['email'], $this->twoFactorService->getTwoFactorChannels($this->user));

        // Enable with multiple channels
        $this->user->update([
            '2fa_channel' => json_encode(['email', 'telegram'])
        ]);
        $this->assertEquals(['email', 'telegram'], $this->twoFactorService->getTwoFactorChannels($this->user));
    }

    /** @test */
    public function it_can_get_two_factor_identifier()
    {
        // Initially null
        $this->assertNull($this->twoFactorService->getTwoFactorIdentifier($this->user));

        // Enable with identifier
        $this->user->update([
            '2fa_enabled' => 1,
            '2fa_identifier' => 'test@example.com'
        ]);
        $this->assertEquals('test@example.com', $this->twoFactorService->getTwoFactorIdentifier($this->user));
    }

    /** @test */
    public function it_returns_empty_channels_when_two_factor_disabled()
    {
        $this->user->update([
            '2fa_enabled' => 0,
            '2fa_channel' => json_encode(['email']) // Channel set but disabled
        ]);

        $this->assertEquals([], $this->twoFactorService->getTwoFactorChannels($this->user));
    }

    /** @test */
    public function it_returns_null_identifier_when_two_factor_disabled()
    {
        $this->user->update([
            '2fa_enabled' => 0,
            '2fa_identifier' => 'test@example.com' // Identifier set but disabled
        ]);

        $this->assertNull($this->twoFactorService->getTwoFactorIdentifier($this->user));
    }

    /** @test */
    public function it_handles_null_channel_when_2fa_enabled()
    {
        // Test the case where 2FA is enabled but channel is null
        $this->user->update([
            '2fa_enabled' => 1,
            '2fa_channel' => null,
            '2fa_identifier' => 'test@example.com'
        ]);

        $status = $this->twoFactorService->getUserTwoFactorStatus($this->user);
        $this->assertNull($status['channel']);

        $channels = $this->twoFactorService->getTwoFactorChannels($this->user);
        // The service returns null when json_decode fails on null
        $this->assertNull($channels);
    }
}