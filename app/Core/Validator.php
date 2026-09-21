<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ValidationException;

/**
 * Small, explicit validation helper.
 *
 * Rules are declared as [field => list of rules]. Rules can be method names
 * on this class ('required', 'email', 'min:8', ...) optionally parameterised.
 * Examples:
 *
 *   Validator::validate($input, [
 *       'email'      => ['required', 'email', 'max:190'],
 *       'password'   => ['required', 'min:8'],
 *       'check_out'  => ['required', 'date', 'after:check_in'],
 *   ]);
 *
 * On failure a ValidationException is thrown carrying a "name => message" map.
 */
final class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                [$name, $param] = self::parseRule($rule);

                $message = self::check($name, $field, $value, $data, $param);
                if ($message !== null) {
                    $errors[$field] = $message;
                    break; // only report the first failing rule per field
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Return a cleaned, type-normalised copy of the input.
        $normalised = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $normalised[$key] = trim($value);
            } else {
                $normalised[$key] = $value;
            }
        }

        return $normalised;
    }

    private static function parseRule(string $rule): array
    {
        return str_contains($rule, ':')
            ? explode(':', $rule, 2)
            : [$rule, null];
    }

    private static function check(string $rule, string $field, mixed $value, array $data, ?string $param): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        switch ($rule) {
            case 'required':
                if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
                    return "The {$label} field is required.";
                }
                return null;

            case 'email':
                if ($value === null || $value === '') {
                    return null; // "required" handles empty values
                }
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "The {$label} must be a valid email address.";

            case 'min':
                if ($value !== null && mb_strlen((string) $value) < (int) $param) {
                    return "The {$label} must be at least {$param} characters.";
                }
                return null;

            case 'max':
                if ($value !== null && mb_strlen((string) $value) > (int) $param) {
                    return "The {$label} must not exceed {$param} characters.";
                }
                return null;

            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    return "The {$label} must be a number.";
                }
                return null;

            case 'integer':
                if ($value !== null && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return "The {$label} must be a whole number.";
                }
                return null;

            case 'min_value':
                if (is_numeric($value) && (float) $value < (float) $param) {
                    return "The {$label} must be at least {$param}.";
                }
                return null;

            case 'max_value':
                if ($value !== null && is_numeric($value) && (float) $value > (float) $param) {
                    return "The {$label} must not exceed {$param}.";
                }
                return null;

            case 'date':
                if ($value !== null && !strtotime((string) $value)) {
                    return "The {$label} must be a valid date.";
                }
                return null;

            case 'after':
                // Ensures e.g. check_out comes after check_in.
                if ($value !== null && strtotime((string) $value) <= strtotime((string) ($data[$param] ?? ''))) {
                    $other = ucfirst(str_replace('_', ' ', (string) $param));
                    return "The {$label} must be after the {$other}.";
                }
                return null;

            case 'in':
                $allowed = array_map('trim', explode(',', (string) $param));
                if ($value !== null && !in_array($value, $allowed, true)) {
                    return "The selected {$label} is invalid.";
                }
                return null;

            case 'unique':
                // unique:table,column,[ignoreId]
                [$table, $column, $ignoreId] = array_pad(explode(',', (string) $param), 3, null);
                if ($value === null || $value === '') {
                    return null;
                }
                $sql = "SELECT id FROM `{$table}` WHERE `{$column}` = ?";
                $sql .= $ignoreId !== null && $ignoreId !== '' ? ' AND id <> ?' : '';
                $stmt = db()->prepare($sql);
                $stmt->execute($ignoreId !== null && $ignoreId !== '' ? [$value, $ignoreId] : [$value]);
                return $stmt->fetch() ? "The {$label} is already in use." : null;

            case 'confirmed':
                $other = $data[$field . '_confirmation'] ?? null;
                if ($value !== $other) {
                    return "The {$label} confirmation does not match.";
                }
                return null;

            case 'password_regex':
                // At least: one letter, one number, one special character.
                if ($value !== null && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/', (string) $value)) {
                    return "The {$label} must include a letter, a number and a special character.";
                }
                return null;

            case 'image':
                if ($value !== null) {
                    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($value, $allowed, true)) {
                        return "The {$label} must be a JPEG, PNG, WebP or GIF image.";
                    }
                }
                return null;

            case 'max_kb':
                if (is_numeric($value) && (int) $value > (int) $param) {
                    return "The {$label} must not exceed " . ((int) $param / 1024) . ' MB.';
                }
                return null;

            default:
                throw new \RuntimeException("Unknown validation rule: {$rule}");
        }
    }
}