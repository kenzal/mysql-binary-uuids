<?php

namespace Kenzal\MysqlBinaryUuids\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Throwable;

/**
 * @mixin Model
 */
trait HasBinaryUuids
{
    use HasUniqueStringIds;

    public function initializeHasBinaryUuids(): void
    {
        $this->usesUniqueIds = true;

        $this->mergeCasts($this->getBinaryUuidCasts());
    }

    /**
     * Get the columns that should receive a UUID.
     *
     * Supports two formats:
     * - Numeric keys: ['id', 'document_id'] - uses default BinaryUuid cast
     * - String keys: ['custom_id' => App\Values\DocumentUuid::class] - uses a custom UUID subclass
     *
     * @return array<int, string>|array<string, class-string<UuidInterface>>
     */
    public function uuidColumns(): array
    {
        return [$this->getKeyName()];
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        if (! $this->usesUniqueIds()) {
            /** @noinspection PhpUndefinedClassInspection */
            return parent::uniqueIds();
        }

        $columns = $this->uuidColumns();

        return array_values(array_map(
            fn ($key, $value) => is_int($key) ? $value : $key,
            array_keys($columns),
            $columns
        ));
    }

    /**
     * @return array<string, class-string<BinaryUuid>|string>
     */
    protected function getBinaryUuidCasts(): array
    {
        $columns = $this->uuidColumns();
        $casts   = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $casts[$value] = BinaryUuid::class;
            } else {
                $this->validateUuidClass($value);
                $casts[$key] = BinaryUuid::class.':'.$value;
            }
        }

        return $casts;
    }

    /**
     * @param  class-string  $uuidClass
     *
     * @throws InvalidArgumentException
     */
    protected function validateUuidClass(string $uuidClass): void
    {
        if (! class_exists($uuidClass)) {
            throw new InvalidArgumentException("Class [{$uuidClass}] does not exist.");
        }

        if (! is_subclass_of($uuidClass, UuidInterface::class)) {
            throw new InvalidArgumentException(
                "Class [{$uuidClass}] must implement ".UuidInterface::class
            );
        }
    }

    public function newUniqueId(): string
    {
        return (string) Uuid::uuid7();
    }

    protected function isValidUniqueId(mixed $value): bool
    {
        if ($value instanceof UuidInterface) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        try {
            Uuid::fromString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
