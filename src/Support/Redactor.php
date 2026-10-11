<?php

declare(strict_types=1);

namespace Enadstack\ActionLogger\Support;

/**
 * Replaces the value of sensitive keys (case-insensitive, at any depth) with a marker.
 */
final class Redactor
{
    public const MARKER = '[redacted]';

    /** @var list<string> */
    private array $keys;

    /**
     * @param list<string> $keys
     */
    public function __construct(array $keys)
    {
        $this->keys = array_values(array_map(
            static fn (string $key): string => mb_strtolower($key),
            array_filter($keys, 'is_string'),
        ));
    }

    /**
     * @param array<array-key, mixed>|null $values
     * @return array<array-key, mixed>|null
     */
    public function redact(?array $values): ?array
    {
        if ($values === null || $this->keys === []) {
            return $values;
        }

        $result = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && in_array(mb_strtolower($key), $this->keys, true)) {
                $result[$key] = self::MARKER;

                continue;
            }

            $result[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $result;
    }
}
