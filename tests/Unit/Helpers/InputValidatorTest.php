<?php

namespace Tests\Unit\Helpers;

use App\Helpers\InputValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests para la clase InputValidator
 */
class InputValidatorTest extends TestCase
{
    // ========================================
    // Tests para validateDNI
    // ========================================

    public function test_validate_dni_with_valid_8_digits(): void
    {
        $this->assertTrue(InputValidator::validateDNI('12345678'));
        $this->assertTrue(InputValidator::validateDNI('00000001'));
        $this->assertTrue(InputValidator::validateDNI('99999999'));
    }

    public function test_validate_dni_with_invalid_length(): void
    {
        $this->assertFalse(InputValidator::validateDNI('1234567'));   // 7 dígitos
        $this->assertFalse(InputValidator::validateDNI('123456789')); // 9 dígitos
        $this->assertFalse(InputValidator::validateDNI(''));          // vacío
    }

    public function test_validate_dni_with_letters(): void
    {
        $this->assertFalse(InputValidator::validateDNI('1234567a'));
        $this->assertFalse(InputValidator::validateDNI('abcdefgh'));
    }

    public function test_validate_dni_with_special_characters(): void
    {
        $this->assertFalse(InputValidator::validateDNI('1234567-'));
        $this->assertFalse(InputValidator::validateDNI('12345.78'));
    }

    // ========================================
    // Tests para validateRUC
    // ========================================

    public function test_validate_ruc_with_valid_11_digits(): void
    {
        $this->assertTrue(InputValidator::validateRUC('10123456789')); // Persona natural
        $this->assertTrue(InputValidator::validateRUC('20123456789')); // Empresa
        $this->assertTrue(InputValidator::validateRUC('15123456789')); // Otro tipo
        $this->assertTrue(InputValidator::validateRUC('17123456789')); // Otro tipo
    }

    public function test_validate_ruc_with_invalid_prefix(): void
    {
        $this->assertFalse(InputValidator::validateRUC('11123456789')); // Prefijo inválido
        $this->assertFalse(InputValidator::validateRUC('30123456789')); // Prefijo inválido
    }

    public function test_validate_ruc_with_invalid_length(): void
    {
        $this->assertFalse(InputValidator::validateRUC('1012345678'));  // 10 dígitos
        $this->assertFalse(InputValidator::validateRUC('101234567890')); // 12 dígitos
    }

    // ========================================
    // Tests para validateEmail
    // ========================================

    public function test_validate_email_with_valid_emails(): void
    {
        $this->assertTrue(InputValidator::validateEmail('test@example.com'));
        $this->assertTrue(InputValidator::validateEmail('user.name@domain.co.pe'));
        $this->assertTrue(InputValidator::validateEmail('user+tag@gmail.com'));
    }

    public function test_validate_email_with_invalid_emails(): void
    {
        $this->assertFalse(InputValidator::validateEmail('invalid'));
        $this->assertFalse(InputValidator::validateEmail('invalid@'));
        $this->assertFalse(InputValidator::validateEmail('@invalid.com'));
        $this->assertFalse(InputValidator::validateEmail('invalid@.com'));
    }

    // ========================================
    // Tests para validatePassword
    // ========================================

    public function test_validate_password_with_valid_password(): void
    {
        $result = InputValidator::validatePassword('Password1@');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function test_validate_password_too_short(): void
    {
        $result = InputValidator::validatePassword('Pass1@');
        $this->assertFalse($result['valid']);
        $this->assertContains('La contraseña debe tener al menos 8 caracteres', $result['errors']);
    }

    public function test_validate_password_missing_uppercase(): void
    {
        $result = InputValidator::validatePassword('password1@');
        $this->assertFalse($result['valid']);
        $this->assertContains('La contraseña debe contener al menos una mayúscula', $result['errors']);
    }

    public function test_validate_password_missing_lowercase(): void
    {
        $result = InputValidator::validatePassword('PASSWORD1@');
        $this->assertFalse($result['valid']);
        $this->assertContains('La contraseña debe contener al menos una minúscula', $result['errors']);
    }

    public function test_validate_password_missing_number(): void
    {
        $result = InputValidator::validatePassword('Password@a');
        $this->assertFalse($result['valid']);
        $this->assertContains('La contraseña debe contener al menos un número', $result['errors']);
    }

    public function test_validate_password_missing_special_char(): void
    {
        $result = InputValidator::validatePassword('Password12');
        $this->assertFalse($result['valid']);
        $this->assertContains('La contraseña debe contener al menos un carácter especial (@$!%*?&#)', $result['errors']);
    }

    // ========================================
    // Tests para validateCUI
    // ========================================

    public function test_validate_cui_with_valid_single_digit(): void
    {
        $this->assertTrue(InputValidator::validateCUI('0'));
        $this->assertTrue(InputValidator::validateCUI('5'));
        $this->assertTrue(InputValidator::validateCUI('9'));
    }

    public function test_validate_cui_with_invalid_values(): void
    {
        $this->assertFalse(InputValidator::validateCUI(''));      // vacío
        $this->assertFalse(InputValidator::validateCUI('12'));    // 2 dígitos
        $this->assertFalse(InputValidator::validateCUI('a'));     // letra
        $this->assertFalse(InputValidator::validateCUI('-'));     // especial
    }

    // ========================================
    // Tests para sanitizeString
    // ========================================

    public function test_sanitize_string_removes_html_tags(): void
    {
        $this->assertEquals('Hello World', InputValidator::sanitizeString('<script>Hello World</script>'));
        $this->assertEquals('Test', InputValidator::sanitizeString('<b>Test</b>'));
    }

    public function test_sanitize_string_escapes_special_chars(): void
    {
        $result = InputValidator::sanitizeString('Test & "quotes" <tag>');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_sanitize_string_trims_whitespace(): void
    {
        $this->assertEquals('Hello', InputValidator::sanitizeString('  Hello  '));
        $this->assertEquals('Test', InputValidator::sanitizeString("\tTest\n"));
    }

    // ========================================
    // Tests para validateUsername
    // ========================================

    public function test_validate_username_with_valid_usernames(): void
    {
        $this->assertTrue(InputValidator::validateUsername('user123'));
        $this->assertTrue(InputValidator::validateUsername('user_name'));
        $this->assertTrue(InputValidator::validateUsername('user-name'));
        $this->assertTrue(InputValidator::validateUsername('ABC'));
    }

    public function test_validate_username_too_short(): void
    {
        $this->assertFalse(InputValidator::validateUsername('ab')); // 2 chars
        $this->assertFalse(InputValidator::validateUsername(''));
    }

    public function test_validate_username_with_invalid_chars(): void
    {
        $this->assertFalse(InputValidator::validateUsername('user@name'));
        $this->assertFalse(InputValidator::validateUsername('user name')); // espacio
        $this->assertFalse(InputValidator::validateUsername('user.name')); // punto
    }

    // ========================================
    // Tests para validateId
    // ========================================

    public function test_validate_id_with_valid_ids(): void
    {
        $this->assertEquals(1, InputValidator::validateId(1));
        $this->assertEquals(100, InputValidator::validateId('100'));
        $this->assertEquals(999999, InputValidator::validateId(999999));
    }

    public function test_validate_id_with_invalid_ids(): void
    {
        $this->assertNull(InputValidator::validateId(0));
        $this->assertNull(InputValidator::validateId(-1));
        $this->assertNull(InputValidator::validateId('abc'));
        $this->assertNull(InputValidator::validateId(''));
    }

    // ========================================
    // Tests para sanitizeArray
    // ========================================

    public function test_sanitize_array_sanitizes_all_strings(): void
    {
        $input = [
            'name' => '<b>John</b>',
            'email' => 'john@example.com',
            'age' => 25
        ];

        $result = InputValidator::sanitizeArray($input);

        $this->assertStringNotContainsString('<', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals(25, $result['age']);
    }

    public function test_sanitize_array_with_specific_fields(): void
    {
        $input = [
            'name' => '<b>John</b>',
            'html' => '<p>Content</p>'
        ];

        $result = InputValidator::sanitizeArray($input, ['name']);

        $this->assertStringNotContainsString('<', $result['name']);
        $this->assertEquals('<p>Content</p>', $result['html']); // No sanitizado
    }
}
