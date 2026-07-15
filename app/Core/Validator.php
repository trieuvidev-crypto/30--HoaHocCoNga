<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight request validator. Rules are declared per-field as an
 * array of rule strings (e.g. 'required|email|max:190'). Error messages
 * are Vietnamese by default since all end-user-facing text in this
 * platform must be Vietnamese (see PROJECT.md §Localization).
 *
 * This is intentionally simple — it is not a general validation
 * framework, just the shared primitive every Validator class in the
 * app composes on top of.
 */
final class Validator
{
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules field => 'required|email|min:6'
     * @param array<string, string> $labels field => Vietnamese display label
     */
    public function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $labels = []
    ) {
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        $label = $this->labels[$field] ?? $field;
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        $isEmpty = $value === null || $value === '';

        match ($name) {
            'required' => $isEmpty && $this->fail($field, "{$label} là bắt buộc."),
            'email' => !$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL)
                && $this->fail($field, "{$label} không đúng định dạng email."),
            'min' => !$isEmpty && mb_strlen((string) $value) < (int) $param
                && $this->fail($field, "{$label} phải có ít nhất {$param} ký tự."),
            'max' => !$isEmpty && mb_strlen((string) $value) > (int) $param
                && $this->fail($field, "{$label} không được vượt quá {$param} ký tự."),
            'numeric' => !$isEmpty && !is_numeric($value)
                && $this->fail($field, "{$label} phải là số."),
            'alpha_dash' => !$isEmpty && !preg_match('/^[a-zA-Z0-9_\-]+$/', (string) $value)
                && $this->fail($field, "{$label} chỉ được chứa chữ, số, gạch dưới và gạch ngang."),
            'confirmed' => !$isEmpty && $value !== ($this->data["{$field}_confirmation"] ?? null)
                && $this->fail($field, "{$label} xác nhận không khớp."),
            'strong_password' => !$isEmpty && !$this->isStrongPassword((string) $value)
                && $this->fail($field, "{$label} phải có chữ hoa, chữ thường, số và ký tự đặc biệt, tối thiểu " . config('security.password.min_length') . ' ký tự.'),
            default => null,
        };
    }

    private function isStrongPassword(string $value): bool
    {
        $minLength = (int) config('security.password.min_length', 10);

        return mb_strlen($value) >= $minLength
            && preg_match('/[a-z]/', $value)
            && preg_match('/[A-Z]/', $value)
            && preg_match('/[0-9]/', $value)
            && preg_match('/[^a-zA-Z0-9]/', $value);
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field][] = $message;

        return true;
    }
}
