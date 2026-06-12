<?php

namespace Kenzal\MysqlBinaryUuids;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Symfony\Component\Uid\Ulid;

class ExtensibleUlid extends Ulid implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new BinaryUlid(static::class);
    }
}
