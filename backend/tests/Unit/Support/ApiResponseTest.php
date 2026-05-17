<?php

namespace Tests\Unit\Support;

use App\Support\ApiResponse;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    // ─── success() ────────────────────────────────────────────────────────────

    public function test_success_returns_200_by_default(): void
    {
        $response = ApiResponse::success();

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_success_response_has_correct_shape(): void
    {
        $response = ApiResponse::success('messages.success', ['key' => 'value']);
        $body = json_decode($response->getContent(), true);

        $this->assertTrue($body['success']);
        $this->assertNull($body['errors']);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('data', $body);
    }

    public function test_success_data_is_passed_through(): void
    {
        $payload = ['id' => 1, 'name' => 'Test'];
        $response = ApiResponse::success('messages.success', $payload);
        $body = json_decode($response->getContent(), true);

        $this->assertSame($payload, $body['data']);
    }

    public function test_success_data_can_be_null(): void
    {
        $response = ApiResponse::success('messages.success', null);
        $body = json_decode($response->getContent(), true);

        $this->assertNull($body['data']);
    }

    public function test_success_respects_custom_status_code(): void
    {
        $response = ApiResponse::success('messages.success', null, 201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function test_success_errors_field_is_always_null(): void
    {
        $response = ApiResponse::success('messages.success', ['foo' => 'bar']);
        $body = json_decode($response->getContent(), true);

        $this->assertNull($body['errors']);
    }

    // ─── error() ──────────────────────────────────────────────────────────────

    public function test_error_returns_400_by_default(): void
    {
        $response = ApiResponse::error('messages.success');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_error_response_has_correct_shape(): void
    {
        $response = ApiResponse::error('messages.success', ['field' => 'invalid']);
        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
        $this->assertNull($body['data']);
        $this->assertArrayHasKey('message', $body);
        $this->assertArrayHasKey('errors', $body);
    }

    public function test_error_errors_field_is_passed_through(): void
    {
        $errors = ['email' => ['The email is invalid.']];
        $response = ApiResponse::error('messages.success', $errors);
        $body = json_decode($response->getContent(), true);

        $this->assertSame($errors, $body['errors']);
    }

    public function test_error_respects_custom_status_code(): void
    {
        $response = ApiResponse::error('messages.success', null, 422);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_error_data_field_is_always_null(): void
    {
        $response = ApiResponse::error('messages.success', ['some' => 'error']);
        $body = json_decode($response->getContent(), true);

        $this->assertNull($body['data']);
    }

    public function test_success_is_false_on_error_response(): void
    {
        $response = ApiResponse::error('messages.success');
        $body = json_decode($response->getContent(), true);

        $this->assertFalse($body['success']);
    }
}
