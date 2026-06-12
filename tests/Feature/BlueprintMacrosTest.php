<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function () {
    Schema::dropIfExists('macro_test_table');
});

it('creates uuid column as binary(16) using default uuid()', function () {
    Schema::create('macro_test_table', function ($table) {
        $table->uuid('id')->primary();
        $table->uuid('user_id');
    });

    $columns = DB::select('DESCRIBE macro_test_table');

    $idColumn     = collect($columns)->firstWhere('Field', 'id');
    $userIdColumn = collect($columns)->firstWhere('Field', 'user_id');

    expect($idColumn->Type)->toBe('binary(16)');
    expect($userIdColumn->Type)->toBe('binary(16)');
});

it('creates ulid column as binary(16) using binaryUlid() macro', function () {
    Schema::create('macro_test_table', function ($table) {
        $table->binaryUlid('id')->primary();
        $table->binaryUlid('session_id')->nullable();
    });

    $columns = DB::select('DESCRIBE macro_test_table');

    $idColumn        = collect($columns)->firstWhere('Field', 'id');
    $sessionIdColumn = collect($columns)->firstWhere('Field', 'session_id');

    expect($idColumn->Type)->toBe('binary(16)');
    expect($sessionIdColumn->Type)->toBe('binary(16)');
});

it('creates binary(16) columns using foreignBinaryUlid() macro', function () {
    Schema::create('macro_test_table', function ($table) {
        $table->binaryUlid('id')->primary();
        $table->foreignBinaryUlid('parent_id');
    });

    $columns = DB::select('DESCRIBE macro_test_table');

    $parentIdColumn = collect($columns)->firstWhere('Field', 'parent_id');

    expect($parentIdColumn->Type)->toBe('binary(16)');
});

it('can mix uuid and ulid columns in the same table', function () {
    Schema::create('macro_test_table', function ($table) {
        $table->uuid('uuid_id');
        $table->binaryUlid('ulid_id');
        $table->string('name');
    });

    $columns = DB::select('DESCRIBE macro_test_table');

    $uuidColumn = collect($columns)->firstWhere('Field', 'uuid_id');
    $ulidColumn = collect($columns)->firstWhere('Field', 'ulid_id');

    expect($uuidColumn->Type)->toBe('binary(16)');
    expect($ulidColumn->Type)->toBe('binary(16)');
});

it('allows nullable binary uuid and ulid columns', function () {
    Schema::create('macro_test_table', function ($table) {
        $table->id();
        $table->uuid('optional_uuid')->nullable();
        $table->binaryUlid('optional_ulid')->nullable();
    });

    $columns = DB::select('DESCRIBE macro_test_table');

    $uuidColumn = collect($columns)->firstWhere('Field', 'optional_uuid');
    $ulidColumn = collect($columns)->firstWhere('Field', 'optional_ulid');

    expect($uuidColumn->Type)->toBe('binary(16)');
    expect($uuidColumn->Null)->toBe('YES');
    expect($ulidColumn->Type)->toBe('binary(16)');
    expect($ulidColumn->Null)->toBe('YES');
});
