<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Cache;
use PHPUnit\Framework\TestCase;

/**
 * Tests para la clase Cache
 */
class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Limpiar caché antes de cada prueba
        Cache::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Limpiar caché después de cada prueba
        Cache::flush();
    }

    public function test_set_and_get_value(): void
    {
        $key = 'test_key';
        $value = ['data' => 'test_value', 'number' => 123];

        Cache::set($key, $value, 60);
        $result = Cache::get($key);

        $this->assertEquals($value, $result);
    }

    public function test_get_returns_null_for_nonexistent_key(): void
    {
        $this->assertNull(Cache::get('nonexistent_key'));
    }

    public function test_has_returns_true_for_existing_key(): void
    {
        Cache::set('existing_key', 'value', 60);
        $this->assertTrue(Cache::has('existing_key'));
    }

    public function test_has_returns_false_for_nonexistent_key(): void
    {
        $this->assertFalse(Cache::has('nonexistent_key'));
    }

    public function test_delete_removes_value(): void
    {
        Cache::set('delete_test', 'value', 60);
        $this->assertTrue(Cache::has('delete_test'));

        Cache::delete('delete_test');
        $this->assertFalse(Cache::has('delete_test'));
    }

    public function test_expired_cache_returns_null(): void
    {
        Cache::set('expired_key', 'value', 1); // 1 segundo

        // Esperar a que expire
        sleep(2);

        $this->assertNull(Cache::get('expired_key'));
    }

    public function test_remember_returns_cached_value(): void
    {
        $callCount = 0;
        $callback = function () use (&$callCount) {
            $callCount++;
            return 'generated_value';
        };

        // Primera llamada - debería ejecutar callback
        $result1 = Cache::remember('remember_test', $callback, 60);
        $this->assertEquals('generated_value', $result1);
        $this->assertEquals(1, $callCount);

        // Segunda llamada - debería usar caché
        $result2 = Cache::remember('remember_test', $callback, 60);
        $this->assertEquals('generated_value', $result2);
        $this->assertEquals(1, $callCount); // No debería incrementar
    }

    public function test_flush_clears_all_cache(): void
    {
        Cache::set('key1', 'value1', 60);
        Cache::set('key2', 'value2', 60);
        Cache::set('key3', 'value3', 60);

        $deleted = Cache::flush();

        $this->assertGreaterThanOrEqual(3, $deleted);
        $this->assertNull(Cache::get('key1'));
        $this->assertNull(Cache::get('key2'));
        $this->assertNull(Cache::get('key3'));
    }

    public function test_stats_returns_correct_counts(): void
    {
        Cache::set('stat_key1', 'value1', 60);
        Cache::set('stat_key2', 'value2', 60);

        $stats = Cache::stats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('valid', $stats);
        $this->assertArrayHasKey('expired', $stats);
        $this->assertArrayHasKey('size_bytes', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total']);
    }

    public function test_cache_handles_complex_data(): void
    {
        $complexData = [
            'string' => 'Hello',
            'number' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null,
            'array' => [1, 2, 3],
            'nested' => [
                'key' => 'value',
                'deep' => ['more' => 'data']
            ]
        ];

        Cache::set('complex_data', $complexData, 60);
        $result = Cache::get('complex_data');

        $this->assertEquals($complexData, $result);
    }
}
