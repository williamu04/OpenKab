<?php

namespace Tests\Unit;

use App\Http\Requests\OtpSetupRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OtpSetupRequestTest extends TestCase
{
    /** @test */
    public function it_validates_required_fields()
    {
        $request = new OtpSetupRequest();
        $rules = $request->rules();
        
        $validator = Validator::make([], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('channel', $validator->errors()->toArray());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_channel_values()
    {
        $request = new OtpSetupRequest();
        $rules = $request->rules();
        
        // Valid channels
        $validChannels = ['email', 'telegram'];
        foreach ($validChannels as $channel) {
            $validator = Validator::make([
                'channel' => $channel,
                'identifier' => 'test@example.com'
            ], $rules);
            
            $this->assertArrayNotHasKey('channel', $validator->errors()->toArray());
        }
        
        // Invalid channel
        $validator = Validator::make([
            'channel' => 'sms',
            'identifier' => 'test@example.com'
        ], $rules);
        
        $this->assertArrayHasKey('channel', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_email_format_for_email_channel()
    {
        $request = new OtpSetupRequest();
        
        // Mock the request input for email channel
        $request->merge(['channel' => 'email']);
        $rules = $request->rules();
        
        // Valid email
        $validator = Validator::make([
            'channel' => 'email',
            'identifier' => 'test@example.com'
        ], $rules);
        
        $this->assertTrue($validator->passes());
        
        // Invalid email
        $validator = Validator::make([
            'channel' => 'email',
            'identifier' => 'invalid-email'
        ], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_telegram_chat_id_format()
    {
        $request = new OtpSetupRequest();
        
        // Mock the request input for telegram channel
        $request->merge(['channel' => 'telegram']);
        $rules = $request->rules();
        
        // Valid chat ID (numeric)
        $validator = Validator::make([
            'channel' => 'telegram',
            'identifier' => '123456789'
        ], $rules);
        
        $this->assertTrue($validator->passes());
        
        // Invalid chat ID (non-numeric)
        $validator = Validator::make([
            'channel' => 'telegram',
            'identifier' => 'abc123'
        ], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_identifier_max_length()
    {
        $request = new OtpSetupRequest();
        $rules = $request->rules();
        
        // Test with string longer than 255 characters
        $longString = str_repeat('a', 256);
        
        $validator = Validator::make([
            'channel' => 'email',
            'identifier' => $longString . '@example.com'
        ], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    /** @test */
    public function authorization_returns_true_for_authenticated_users()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $request = new OtpSetupRequest();
        
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function authorization_returns_false_for_unauthenticated_users()
    {
        $request = new OtpSetupRequest();
        
        $this->assertFalse($request->authorize());
    }
}