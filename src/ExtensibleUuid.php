<?php

namespace Kenzal\MysqlBinaryUuids;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ExtensibleUuid extends Uuid implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new BinaryUuid(static::class);
    }

    public static function fromBytes(string $bytes): static
    {
        /** @var static|UuidInterface $resolved */
        $resolved = static::getFactory()->fromBytes($bytes);

        if ($resolved instanceof static) {
            return $resolved;
        }

        return new static(
            $resolved->fields,
            $resolved->numberConverter,
            $resolved->codec,
            $resolved->timeConverter,
        );
    }

    public static function fromString(string $uuid): static
    {
        /** @var static|UuidInterface $resolved */
        $resolved = static::getFactory()->fromString($uuid);

        if ($resolved instanceof static) {
            return $resolved;
        }

        return new static(
            $resolved->fields,
            $resolved->numberConverter,
            $resolved->codec,
            $resolved->timeConverter,
        );
    }

    public static function uuid4(): static
    {
        return static::fromString(parent::uuid4()->toString());
    }

    public static function uuid7(?DateTimeInterface $dateTime = null): static
    {
        return static::fromString(parent::uuid7($dateTime)->toString());
    }
}
