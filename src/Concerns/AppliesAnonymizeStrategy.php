<?php

namespace JeffersonGoncalves\Privacy\Concerns;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * For a suite's AnonymizeStrategy enum with the cases Nullify, Hash, Mask and Keep. The enum declares
 * `const HASH_KEY = '<suite>.hash_key';`, the config key of the "hash" strategy (falls back to app.key).
 */
trait AppliesAnonymizeStrategy
{
    public function apply(mixed $value): mixed
    {
        if ($value === null || $this->keeps()) {
            return $value;
        }

        return match ($this->value) {
            'nullify' => null,
            'hash' => hash_hmac('sha256', (string) $value, self::hashKey()),
            'mask' => Str::mask((string) $value, '*', 1),
            default => throw new RuntimeException("Unknown anonymize strategy [{$this->value}]."),
        };
    }

    public function keeps(): bool
    {
        return $this->value === 'keep';
    }

    private static function hashKey(): string
    {
        $key = config(self::HASH_KEY) ?: config('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Set '.self::HASH_KEY.' (or app.key) before using the hash anonymize strategy.');
        }

        return $key;
    }
}
