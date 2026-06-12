<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Kenzal\MysqlBinaryUuids\Grammar\MySqlGrammar;

it('registers the custom MySQL grammar on the connection', function () {
    $grammar = DB::connection()->getSchemaGrammar();

    expect($grammar)->toBeInstanceOf(MySqlGrammar::class);
});

it('compiles uuid column as binary(16)', function () {
    $connection = DB::connection();
    $blueprint  = new Blueprint($connection, 'test_table');
    $blueprint->uuid('id');

    $statements = $blueprint->toSql();

    expect(implode(' ', $statements))->toContain('binary(16)');
});

it('compiles binaryUlid column as binary(16)', function () {
    $connection = DB::connection();
    $blueprint  = new Blueprint($connection, 'test_table');
    /** @noinspection PhpUndefinedMethodInspection */
    $blueprint->binaryUlid('ulid_col');

    $statements = $blueprint->toSql();

    expect(implode(' ', $statements))->toContain('binary(16)');
});

it('registers the binaryUlid Blueprint macro', function () {
    expect(Blueprint::hasMacro('binaryUlid'))->toBeTrue();
});

it('registers the foreignBinaryUlid Blueprint macro', function () {
    expect(Blueprint::hasMacro('foreignBinaryUlid'))->toBeTrue();
});
