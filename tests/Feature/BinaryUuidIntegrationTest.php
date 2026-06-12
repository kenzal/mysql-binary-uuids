<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

beforeEach(function () {
    Schema::dropIfExists('uuid_test_models');

    Schema::create('uuid_test_models', function ($table) {
        $table->uuid('id')->primary();
        $table->uuid('alternate_id')->nullable();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('uuid_test_models');
});

it('creates a table with uuid columns as binary(16)', function () {
    $columns = DB::select('DESCRIBE uuid_test_models');

    $idColumn          = collect($columns)->firstWhere('Field', 'id');
    $alternateIdColumn = collect($columns)->firstWhere('Field', 'alternate_id');

    expect($idColumn->Type)->toBe('binary(16)');
    expect($alternateIdColumn->Type)->toBe('binary(16)');
});

it('stores and retrieves a model with UUID cast', function () {
    $model = new class extends Model
    {
        protected $table = 'uuid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUuid::class,
                'alternate_id' => BinaryUuid::class,
            ];
        }
    };

    $uuid    = '550e8400-e29b-41d4-a716-446655440000';
    $altUuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    $instance = $model->create([
        'id'           => $uuid,
        'alternate_id' => $altUuid,
        'name'         => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toBe($uuid);
    expect($instance->alternate_id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->alternate_id->toString())->toBe($altUuid);
    expect($instance->name)->toBe('Test Model');

    // Verify it's stored as binary in the database
    $raw = DB::selectOne('SELECT id, alternate_id FROM uuid_test_models WHERE name = ?', ['Test Model']);
    expect(strlen($raw->id))->toBe(16);
    expect(strlen($raw->alternate_id))->toBe(16);

    // Verify we can retrieve it by casting the UUID to binary for the query
    $retrieved = $model->where('name', 'Test Model')->first();
    expect($retrieved)->not->toBeNull();
    expect($retrieved->id)->toBeInstanceOf(UuidInterface::class);
    expect($retrieved->id->toString())->toBe($uuid);
    expect($retrieved->alternate_id)->toBeInstanceOf(UuidInterface::class);
    expect($retrieved->alternate_id->toString())->toBe($altUuid);
    expect($retrieved->name)->toBe('Test Model');
});

it('handles null UUID values correctly', function () {
    $model = new class extends Model
    {
        protected $table = 'uuid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUuid::class,
                'alternate_id' => BinaryUuid::class,
            ];
        }
    };

    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $instance = $model->create([
        'id'           => $uuid,
        'alternate_id' => null,
        'name'         => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toBe($uuid);
    expect($instance->alternate_id)->toBeNull();

    $retrieved = $model->where('name', 'Test Model')->first();
    expect($retrieved)->not->toBeNull();
    expect($retrieved->alternate_id)->toBeNull();
});

it('can query models by UUID', function () {
    $model = new class extends Model
    {
        protected $table = 'uuid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUuid::class,
                'alternate_id' => BinaryUuid::class,
            ];
        }
    };

    $uuid1 = '550e8400-e29b-41d4-a716-446655440000';
    $uuid2 = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    $model->create(['id' => $uuid1, 'name' => 'Model 1']);
    $model->create(['id' => $uuid2, 'name' => 'Model 2']);

    $found = $model->where('name', 'Model 1')->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBeInstanceOf(UuidInterface::class);
    expect($found->id->toString())->toBe($uuid1);

    $found = $model->where('name', 'Model 2')->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBeInstanceOf(UuidInterface::class);
    expect($found->id->toString())->toBe($uuid2);
});

it('accepts UUID objects when creating models', function () {
    $model = new class extends Model
    {
        protected $table = 'uuid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'name'];

        protected function casts(): array
        {
            return [
                'id' => BinaryUuid::class,
            ];
        }
    };

    $uuid = Uuid::uuid4();

    $instance = $model->create([
        'id'   => $uuid,
        'name' => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toBe($uuid->toString());
});
