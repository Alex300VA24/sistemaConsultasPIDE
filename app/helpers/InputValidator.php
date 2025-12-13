<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Clase para validación y sanitización de datos de entrada
 */
class InputValidator
{
    /**
     * Sanitiza una cadena removiendo HTML y caracteres peligrosos
     *
     * @param string $input Cadena a sanitizar
     * @return string Cadena sanitizada
     */
    public static function sanitizeString(string $input): string
    {
        $sanitized = strip_tags($input);
        $sanitized = htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');
        $sanitized = trim($sanitized);
        return $sanitized;
    }

    /**
     * Valida un DNI peruano (8 dígitos)
     *
     * @param string $dni DNI a validar
     * @return bool True si es válido
     */
    public static function validateDNI(string $dni): bool
    {
        return preg_match('/^\d{8}$/', $dni) === 1;
    }

    /**
     * Valida un RUC peruano (11 dígitos)
     *
     * @param string $ruc RUC a validar
     * @return bool True si es válido
     */
    public static function validateRUC(string $ruc): bool
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            return false;
        }

        // RUC debe empezar con 10, 15, 17 o 20
        $prefix = substr($ruc, 0, 2);
        return in_array($prefix, ['10', '15', '17', '20']);
    }

    /**
     * Valida un email
     *
     * @param string $email Email a validar
     * @return bool True si es válido
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida que una contraseña cumpla con los requisitos de seguridad
     * - Mínimo 8 caracteres
     * - Al menos una mayúscula
     * - Al menos una minúscula
     * - Al menos un número
     * - Al menos un carácter especial
     *
     * @param string $password Contraseña a validar
     * @return array Array con 'valid' (bool) y 'errors' (array de errores)
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una mayúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }

        if (!preg_match('/[@$!%*?&#]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial (@$!%*?&#)';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valida un nombre de usuario
     * - Mínimo 3 caracteres
     * - Máximo 50 caracteres
     * - Solo letras, números, guiones y guiones bajos
     *
     * @param string $username Nombre de usuario a validar
     * @return bool True si es válido
     */
    public static function validateUsername(string $username): bool
    {
        if (strlen($username) < 3 || strlen($username) > 50) {
            return false;
        }
        return preg_match('/^[a-zA-Z0-9_-]+$/', $username) === 1;
    }

    /**
     * Valida un CUI (último dígito)
     *
     * @param string $cui CUI a validar (1 dígito)
     * @return bool True si es válido
     */
    public static function validateCUI(string $cui): bool
    {
        return preg_match('/^\d{1}$/', $cui) === 1;
    }

    /**
     * Sanitiza un array de datos
     *
     * @param array $data Datos a sanitizar
     * @param array $fields Campos a sanitizar (si está vacío, sanitiza todos)
     * @return array Datos sanitizados
     */
    public static function sanitizeArray(array $data, array $fields = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                if (empty($fields) || in_array($key, $fields)) {
                    $sanitized[$key] = self::sanitizeString($value);
                } else {
                    $sanitized[$key] = $value;
                }
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $fields);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Valida que un valor sea un entero positivo
     *
     * @param mixed $value Valor a validar
     * @return bool True si es un entero positivo
     */
    public static function isPositiveInteger($value): bool
    {
        if (is_numeric($value)) {
            $intVal = (int)$value;
            return $intVal > 0 && $intVal == $value;
        }
        return false;
    }

    /**
     * Valida y sanitiza un ID numérico
     *
     * @param mixed $id ID a validar
     * @return int|null ID validado o null si es inválido
     */
    public static function validateId($id): ?int
    {
        if (self::isPositiveInteger($id)) {
            return (int)$id;
        }
        return null;
    }
}
