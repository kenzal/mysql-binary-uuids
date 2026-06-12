<?php

namespace Kenzal\MysqlBinaryUuids\Grammar;

use Illuminate\Database\Schema\Grammars\MySqlGrammar as BaseMySqlGrammar;
use Illuminate\Support\Fluent;

class MySqlGrammar extends BaseMySqlGrammar
{
    protected function typeUuid(Fluent $column): string
    {
        return 'binary(16)';
    }

    /**
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function typeUlid(Fluent $column): string
    {
        return 'binary(16)';
    }
}
