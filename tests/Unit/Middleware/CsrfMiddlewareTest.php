<?php

namespace Tests\Unit\Middleware;

use App\Middleware\CsrfMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Tests para la clase CsrfMiddleware
 */
class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_SESSION = [];
    }

    public function test_generate_token_creates_token(): void
    {
        $token = CsrfMiddleware::generateToken();

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes en hex = 64 caracteres
    }

    public function test_generate_token_stores_in_session(): void
    {
        $token = CsrfMiddleware::generateToken();

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function test_get_token_returns_stored_token(): void
    {
        $generated = CsrfMiddleware::generateToken();
        $retrieved = CsrfMiddleware::getToken();

        $this->assertEquals($generated, $retrieved);
    }

    public function test_get_token_returns_null_when_no_token(): void
    {
        unset($_SESSION['csrf_token']);
        $token = CsrfMiddleware::getToken();

        $this->assertNull($token);
    }

    public function test_regenerate_token_creates_new_token(): void
    {
        $token1 = CsrfMiddleware::generateToken();
        $token2 = CsrfMiddleware::regenerateToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function test_handle_allows_get_requests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $result = CsrfMiddleware::handle();

        $this->assertTrue($result);
    }

    public function test_handle_allows_exempt_paths(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/sistemaConsultasPIDE/public/api/login';

        $result = CsrfMiddleware::handle();

        $this->assertTrue($result);
    }

    public function test_get_hidden_input_returns_valid_html(): void
    {
        CsrfMiddleware::generateToken();
        $html = CsrfMiddleware::getHiddenInput();

        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('value="', $html);
    }

    public function test_get_meta_tag_returns_valid_html(): void
    {
        CsrfMiddleware::generateToken();
        $html = CsrfMiddleware::getMetaTag();

        $this->assertStringContainsString('<meta', $html);
        $this->assertStringContainsString('name="csrf-token"', $html);
        $this->assertStringContainsString('content="', $html);
    }
}
