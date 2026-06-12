<?php

namespace Kenzal\MysqlBinaryUuids\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Symfony\Component\Uid\Ulid;

/**
 * @mixin Model
 */
trait HasBinaryUlids
{
    use HasUniqueStringIds;

    public function initializeHasBinaryUlids(): void
    {
        $this->usesUniqueIds = true;

        $this->mergeCasts($this->getBinaryUlidCasts());
    }

    /**
     * Get the columns that should receive a ULID.
     *
     * Supports two formats:
     * - Numeric keys: ['id', 'session_id'] - uses default BinaryUlid cast
     * - String keys: ['session_id' => App\Values\CustomUlid::class] - uses a custom ULID subclass
     *
     * @return array<int, string>|array<string, class-string<Ulid>>
     */
    public function ulidColumns(): array
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

        $columns = $this->ulidColumns();

        return array_values(array_map(
            fn ($key, $value) => is_int($key) ? $value : $key,
            array_keys($columns),
            $columns
        ));
    }

    /**
     * @return array<string, class-string<BinaryUlid>|string>
     */
    protected function getBinaryUlidCasts(): array
    {
        $columns = $this->ulidColumns();
        $casts   = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $casts[$value] = BinaryUlid::class;
            } else {
                $this->validateUlidClass($value);
                $casts[$key] = BinaryUlid::class.':'.$value;
            }
        }

        return $casts;
    }

    /**
     * @param  class-string  $ulidClass
     *
     * @throws InvalidArgumentException
     */
    protected function validateUlidClass(string $ulidClass): void
    {
        if (! class_exists($ulidClass)) {
            throw new InvalidArgumentException("Class [{$ulidClass}] does not exist.");
        }

        if (! is_subclass_of($ulidClass, Ulid::class)) {
            throw new InvalidArgumentException(
                "Class [{$ulidClass}] must extend ".Ulid::class
            );
        }
    }

    public function newUniqueId(): string
    {
        return Ulid::generate();
    }

    protected function isValidUniqueId(mixed $value): bool
    {
        if ($value instanceof Ulid) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        return Ulid::isValid($value);
    }
}
