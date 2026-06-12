<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

beforeEach(function () {
    Schema::dropIfExists('uuid_trait_models');

    Schema::create('uuid_trait_models', function ($table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('uuid_trait_models');
});

it('automatically generates a UUID for the primary key', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('sets keyType to string for UUID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';
    };

    expect($model->getKeyType())->toBe('string');
});

it('sets incrementing to false for UUID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';
    };

    expect($model->getIncrementing())->toBeFalse();
});

it('automatically applies BinaryUuid cast to UUID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    // The cast should handle the conversion
    expect($instance->id)->toBeInstanceOf(UuidInterface::class);

    // Verify it's stored as binary in the database
    $raw = DB::selectOne(
        'SELECT id FROM uuid_trait_models WHERE name = ?',
        ['Test']
    );
    expect(strlen($raw->id))->toBe(16);
});

it('accepts a UUID string when creating a model', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['id', 'name'];
    };

    $uuid     = '550e8400-e29b-41d4-a716-446655440000';
    $instance = $model->create(['id' => $uuid, 'name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toBe($uuid);
});

it('accepts a UUID object when creating a model', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['id', 'name'];
    };

    $uuid     = Uuid::uuid4();
    $instance = $model->create(['id' => $uuid, 'name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->id->toString())->toBe($uuid->toString());
});

it('can retrieve a model by UUID', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);
    $id       = $instance->id;

    $found = $model->where('name', 'Test')->first();

    expect($found)->not->toBeNull();
    expect($found->id->toString())->toBe($id->toString());
});

it('validates UUID format for isValidUniqueId', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        public function testIsValidUniqueId($value): bool
        {
            return $this->isValidUniqueId($value);
        }
    };

    expect($model->testIsValidUniqueId('550e8400-e29b-41d4-a716-446655440000'))->toBeTrue();
    expect($model->testIsValidUniqueId(Uuid::uuid4()))->toBeTrue();
    expect($model->testIsValidUniqueId('not-a-uuid'))->toBeFalse();
    expect($model->testIsValidUniqueId(123))->toBeFalse();
});

it('generates UUID v7 by default', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'uuid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    // UUID v7 has version bits set to 0111 (7) in the version field
    $hex           = str_replace('-', '', $instance->id->toString());
    $versionNibble = hexdec($hex[12]);

    expect($versionNibble & 0xF0 >> 4)->toBe(7);
});
