<?php

namespace Kenzal\MysqlBinaryUuids\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

class BinaryUuid implements CastsAttributes
{
    /** @var class-string<UuidInterface>|null */
    private ?string $customUuidClass = null;

    /**
     * @param  class-string<UuidInterface>|null  $customUuidClass
     *
     * @throws InvalidArgumentException
     */
    public function __construct(?string $customUuidClass = null)
    {
        if ($customUuidClass !== null) {
            if (! class_exists($customUuidClass)) {
                throw new InvalidArgumentException("Class [{$customUuidClass}] does not exist.");
            }

            if (! is_subclass_of($customUuidClass, UuidInterface::class)) {
                throw new InvalidArgumentException(
                    "Class [{$customUuidClass}] must implement ".UuidInterface::class
                );
            }

            $this->customUuidClass = $customUuidClass;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UuidInterface
    {
        if ($value === null) {
            return null;
        }

        try {
            return $this->customUuidClass !== null
                ? $this->customUuidClass::fromBytes($value)
                : Uuid::fromBytes($value);
        } catch (Throwable) {
            throw new InvalidArgumentException("Invalid UUID binary data in column [{$key}]");
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
            if ($value instanceof UuidInterface) {
                return $value->getBytes();
            }

            return Uuid::fromString($value)->getBytes();
        } catch (Throwable) {
            throw new InvalidArgumentException("Invalid UUID: [{$value}]");
        }
    }
}
