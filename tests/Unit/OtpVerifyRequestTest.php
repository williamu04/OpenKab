<?php

namespace Tests\Unit;

use App\Http\Requests\OtpVerifyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OtpVerifyRequestTest extends TestCase
{
    /** @test */
    public function it_validates_required_otp_field()
    {
        $request = new OtpVerifyRequest();
        $rules = $request->rules();
        
        $validator = Validator::make([], $rules);
        
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('otp', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_otp_format()
    {
        $request = new OtpVerifyRequest();
        $rules = $request->rules();
        
        // Valid OTP (6 digits)
        $validator = Validator::make(['otp' => '123456'], $rules);
        $this->assertTrue($validator->passes());
        
        // Invalid OTP - too short
        $validator = Validator::make(['otp' => '12345'], $rules);
        $this->assertFalse($validator->passes());
        
        // Invalid OTP - too long
        $validator = Validator::make(['otp' => '1234567'], $rules);
        $this->assertFalse($validator->passes());
        
        // Invalid OTP - contains letters
        $validator = Validator::make(['otp' => '12345a'], $rules);
        $this->assertFalse($validator->passes());
        
        // Invalid OTP - contains special characters
        $validator = Validator::make(['otp' => '12345!'], $rules);
        $this->assertFalse($validator->passes());
    }

    /** @test */
    public function it_validates_otp_must_be_string()
    {
        $request = new OtpVerifyRequest();
        $rules = $request->rules();
        
        // Integer input should fail string validation
        $validator = Validator::make(['otp' => 123456], $rules);
        $this->assertFalse($validator->passes());
        
        // Array input should fail
        $validator = Validator::make(['otp' => ['1', '2', '3', '4', '5', '6']], $rules);
        $this->assertFalse($validator->passes());
    }

    /** @test */
    public function it_has_custom_error_messages()
    {
        $request = new OtpVerifyRequest();
        $messages = $request->messages();
        
        $this->assertArrayHasKey('otp.required', $messages);
        $this->assertArrayHasKey('otp.string', $messages);
        $this->assertArrayHasKey('otp.min', $messages);
        $this->assertArrayHasKey('otp.max', $messages);
        $this->assertArrayHasKey('otp.regex', $messages);
        
        // Test specific messages
        $this->assertEquals('Kode OTP wajib diisi', $messages['otp.required']);
        $this->assertEquals('Kode OTP harus berupa teks', $messages['otp.string']);
    }

    /** @test */
    public function it_validates_with_custom_messages()
    {
        $request = new OtpVerifyRequest();
        $rules = $request->rules();
        $messages = $request->messages();
        
        // Test required validation message
        $validator = Validator::make([], $rules, $messages);
        $this->assertEquals(
            'Kode OTP wajib diisi', 
            $validator->errors()->first('otp')
        );
        
        // Test regex validation message
        $validator = Validator::make(['otp' => '12345a'], $rules, $messages);
        $this->assertEquals(
            'Kode OTP harus berupa 6 digit angka', 
            $validator->errors()->first('otp')
        );
        
        // Test length validation message
        $validator = Validator::make(['otp' => '123'], $rules, $messages);
        $this->assertEquals(
            'Kode OTP harus 6 digit', 
            $validator->errors()->first('otp')
        );
    }

    /** @test */
    public function authorization_returns_true_for_authenticated_users()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $request = new OtpVerifyRequest();
        
        $this->assertTrue($request->authorize());
    }
}