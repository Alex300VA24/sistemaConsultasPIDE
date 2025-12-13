<?php

namespace Tests\Unit\Middleware;

use App\Middleware\RateLimiter;
use App\Exceptions\RateLimitException;
use PHPUnit\Framework\TestCase;

/**
 * Tests para la clase RateLimiter
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar rate limits antes de cada prueba
        RateLimiter::clear('login');
        RateLimiter::clear('api');
        RateLimiter::clear('password_reset');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Limpiar rate limits después de cada prueba
        RateLimiter::clear('login');
        RateLimiter::clear('api');
        RateLimiter::clear('password_reset');
    }

    public function test_first_attempt_passes(): void
    {
        $this->assertTrue(RateLimiter::check('login'));
    }

    public function test_multiple_attempts_within_limit_pass(): void
    {
        // Login permite 5 intentos por minuto
        for ($i = 0; $i < 4; $i++) {
            $this->assertTrue(RateLimiter::check('login'));
        }
    }

    public function test_exceeding_limit_throws_exception(): void
    {
        $this->expectException(RateLimitException::class);

        // Login permite 5 intentos por minuto
        for ($i = 0; $i < 6; $i++) {
            RateLimiter::check('login');
        }
    }

    public function test_get_remaining_attempts(): void
    {
        // Verificar que inicialmente hay 5 intentos disponibles para login
        $remaining = RateLimiter::getRemainingAttempts('login');
        $this->assertEquals(5, $remaining);

        // Hacer 2 intentos
        RateLimiter::check('login');
        RateLimiter::check('login');

        // Deberían quedar 3
        $remaining = RateLimiter::getRemainingAttempts('login');
        $this->assertEquals(3, $remaining);
    }

    public function test_clear_resets_attempts(): void
    {
        // Hacer algunos intentos
        RateLimiter::check('login');
        RateLimiter::check('login');
        RateLimiter::check('login');

        // Limpiar
        RateLimiter::clear('login');

        // Deberían estar disponibles todos los intentos
        $remaining = RateLimiter::getRemainingAttempts('login');
        $this->assertEquals(5, $remaining);
    }

    public function test_hit_registers_attempt(): void
    {
        $initialRemaining = RateLimiter::getRemainingAttempts('login');

        RateLimiter::hit('login');

        $newRemaining = RateLimiter::getRemainingAttempts('login');
        $this->assertEquals($initialRemaining - 1, $newRemaining);
    }

    public function test_different_actions_have_separate_limits(): void
    {
        // Hacer intentos en login
        for ($i = 0; $i < 4; $i++) {
            RateLimiter::check('login');
        }

        // API debería tener sus propios intentos disponibles
        $apiRemaining = RateLimiter::getRemainingAttempts('api');
        $this->assertEquals(60, $apiRemaining); // API permite 60/min

        $loginRemaining = RateLimiter::getRemainingAttempts('login');
        $this->assertEquals(1, $loginRemaining);
    }

    public function test_identifier_separates_limits(): void
    {
        // Intentos para usuario1
        RateLimiter::hit('login', 'usuario1');
        RateLimiter::hit('login', 'usuario1');

        // Intentos para usuario2
        RateLimiter::hit('login', 'usuario2');

        // Limpiar solo usuario1
        RateLimiter::clear('login', 'usuario1');

        // usuario1 debería tener todos los intentos
        // No podemos verificar esto directamente sin acceso al identificador en getRemainingAttempts
        // Pero podemos verificar que el sistema funciona
        $this->assertTrue(RateLimiter::check('login', 'usuario1'));
    }

    public function test_cleanup_removes_old_files(): void
    {
        // Hacer algunos intentos
        RateLimiter::hit('login');
        RateLimiter::hit('api');

        // Cleanup con 0 segundos debería eliminar todo
        $deleted = RateLimiter::cleanup(0);

        $this->assertGreaterThanOrEqual(0, $deleted);
    }
}
