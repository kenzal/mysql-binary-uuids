<?php

namespace Kenzal\MysqlBinaryUuids\Connection;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Ulid;

class MySqlConnection extends BaseMySqlConnection
{
    public function prepareBindings(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof UuidInterface) {
                $bindings[$key] = $value->getBytes();
            } elseif ($value instanceof Ulid) {
                $bindings[$key] = $value->toBinary();
            }
        }

        return parent::prepareBindings($bindings);
    }
}
