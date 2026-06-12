<?php

namespace Kenzal\MysqlBinaryUuids\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Symfony\Component\Uid\Ulid;
use Throwable;

class BinaryUlid implements CastsAttributes
{
    /** @var class-string<Ulid>|null */
    private ?string $customUlidClass = null;

    /**
     * @param  class-string<Ulid>|null  $customUlidClass
     *
     * @throws InvalidArgumentException
     */
    public function __construct(?string $customUlidClass = null)
    {
        if ($customUlidClass !== null) {
            if (! class_exists($customUlidClass)) {
                throw new InvalidArgumentException("Class [{$customUlidClass}] does not exist.");
            }

            if (! is_subclass_of($customUlidClass, Ulid::class)) {
                throw new InvalidArgumentException(
                    "Class [{$customUlidClass}] must extend ".Ulid::class
                );
            }

            $this->customUlidClass = $customUlidClass;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Ulid
    {
        if ($value === null) {
            return null;
        }

        try {
            return $this->customUlidClass !== null
                ? $this->customUlidClass::fromBinary($value)
                : Ulid::fromBinary($value);
        } catch (Throwable) {
            throw new InvalidArgumentException("Invalid ULID binary data in column [{$key}]");
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            if ($value instanceof Ulid) {
                return $value->toBinary();
            }

            return Ulid::fromString($value)->toBinary();
        } catch (Throwable) {
            throw new InvalidArgumentException("Invalid ULID: [{$value}]");
        }
    }
}
