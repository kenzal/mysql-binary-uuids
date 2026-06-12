<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    Schema::dropIfExists('ulid_test_models');

    Schema::create('ulid_test_models', function ($table) {
        $table->binaryUlid('id')->primary();
        $table->binaryUlid('alternate_id')->nullable();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('ulid_test_models');
});

it('creates a table with binaryUlid columns as binary(16)', function () {
    $columns = DB::select('DESCRIBE ulid_test_models');

    $idColumn          = collect($columns)->firstWhere('Field', 'id');
    $alternateIdColumn = collect($columns)->firstWhere('Field', 'alternate_id');

    expect($idColumn->Type)->toBe('binary(16)');
    expect($alternateIdColumn->Type)->toBe('binary(16)');
});

it('stores and retrieves a model with ULID cast', function () {
    $model = new class extends Model
    {
        protected $table = 'ulid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUlid::class,
                'alternate_id' => BinaryUlid::class,
            ];
        }
    };

    $ulid    = Ulid::generate();
    $altUlid = Ulid::generate();

    $instance = $model->create([
        'id'           => $ulid,
        'alternate_id' => $altUlid,
        'name'         => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toBe($ulid);
    expect($instance->alternate_id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->alternate_id)->toBe($altUlid);
    expect($instance->name)->toBe('Test Model');

    // Verify it's stored as binary in the database
    $raw = DB::selectOne('SELECT id, alternate_id FROM ulid_test_models WHERE name = ?', ['Test Model']);
    expect(strlen($raw->id))->toBe(16);
    expect(strlen($raw->alternate_id))->toBe(16);

    // Verify we can retrieve it by name instead of by ID
    $retrieved = $model->where('name', 'Test Model')->first();
    expect($retrieved)->not->toBeNull();
    expect($retrieved->id)->toBeInstanceOf(Ulid::class);
    expect((string) $retrieved->id)->toBe($ulid);
    expect($retrieved->alternate_id)->toBeInstanceOf(Ulid::class);
    expect((string) $retrieved->alternate_id)->toBe($altUlid);
    expect($retrieved->name)->toBe('Test Model');
});

it('handles null ULID values correctly', function () {
    $model = new class extends Model
    {
        protected $table = 'ulid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUlid::class,
                'alternate_id' => BinaryUlid::class,
            ];
        }
    };

    $ulid = Ulid::generate();

    $instance = $model->create([
        'id'           => $ulid,
        'alternate_id' => null,
        'name'         => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toBe($ulid);
    expect($instance->alternate_id)->toBeNull();

    $retrieved = $model->where('name', 'Test Model')->first();
    expect($retrieved)->not->toBeNull();
    expect($retrieved->alternate_id)->toBeNull();
});

it('can query models by ULID', function () {
    $model = new class extends Model
    {
        protected $table = 'ulid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'alternate_id', 'name'];

        protected function casts(): array
        {
            return [
                'id'           => BinaryUlid::class,
                'alternate_id' => BinaryUlid::class,
            ];
        }
    };

    $ulid1 = Ulid::generate();
    $ulid2 = Ulid::generate();

    $model->create(['id' => $ulid1, 'name' => 'Model 1']);
    $model->create(['id' => $ulid2, 'name' => 'Model 2']);

    $found = $model->where('name', 'Model 1')->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBeInstanceOf(Ulid::class);
    expect((string) $found->id)->toBe($ulid1);

    $found = $model->where('name', 'Model 2')->first();
    expect($found)->not->toBeNull();
    expect($found->id)->toBeInstanceOf(Ulid::class);
    expect((string) $found->id)->toBe($ulid2);
});

it('sorts ULIDs chronologically when stored as binary', function () {
    $model = new class extends Model
    {
        protected $table = 'ulid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'name'];

        protected function casts(): array
        {
            return [
                'id' => BinaryUlid::class,
            ];
        }
    };

    // Create ULIDs with small delays to ensure different timestamps
    $ulid1 = Ulid::generate();
    usleep(1000); // 1ms delay
    $ulid2 = Ulid::generate();
    usleep(1000);
    $ulid3 = Ulid::generate();

    // Insert in random order
    $model->create(['id' => $ulid2, 'name' => 'Second']);
    $model->create(['id' => $ulid1, 'name' => 'First']);
    $model->create(['id' => $ulid3, 'name' => 'Third']);

    // Query by ID - binary ULIDs should sort chronologically
    $sorted = $model->orderBy('id')->pluck('name')->toArray();

    expect($sorted)->toBe(['First', 'Second', 'Third']);
});

it('accepts ULID objects when creating models', function () {
    $model = new class extends Model
    {
        protected $table = 'ulid_test_models';

        public $incrementing = false;

        protected $keyType = 'string';

        protected $fillable = ['id', 'name'];

        protected function casts(): array
        {
            return [
                'id' => BinaryUlid::class,
            ];
        }
    };

    $ulid = Ulid::generate();

    $instance = $model->create([
        'id'   => $ulid,
        'name' => 'Test Model',
    ]);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toBe($ulid);
});
