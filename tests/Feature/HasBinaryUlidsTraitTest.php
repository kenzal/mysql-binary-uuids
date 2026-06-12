<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUlids;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    Schema::dropIfExists('ulid_trait_models');

    Schema::create('ulid_trait_models', function ($table) {
        $table->binaryUlid('id')->primary();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('ulid_trait_models');
});

it('automatically generates a ULID for the primary key', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toMatch('/^[0123456789ABCDEFGHJKMNPQRSTVWXYZ]{26}$/');
});

it('sets keyType to string for ULID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';
    };

    expect($model->getKeyType())->toBe('string');
});

it('sets incrementing to false for ULID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';
    };

    expect($model->getIncrementing())->toBeFalse();
});

it('automatically applies BinaryUlid cast to ULID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    // The cast should handle the conversion
    expect($instance->id)->toBeInstanceOf(Ulid::class);

    // Verify it's stored as binary in the database
    $raw = DB::selectOne(
        'SELECT id FROM ulid_trait_models WHERE name = ?',
        ['Test']
    );
    expect(strlen($raw->id))->toBe(16);
});

it('accepts a ULID string when creating a model', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['id', 'name'];
    };

    $ulid     = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $instance = $model->create(['id' => $ulid, 'name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toBe($ulid);
});

it('accepts a ULID object when creating a model', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['id', 'name'];
    };

    $ulid     = Ulid::generate();
    $instance = $model->create(['id' => $ulid, 'name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->id)->toBe($ulid);
});

it('can retrieve a model by ULID', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);
    $id       = $instance->id;

    $found = $model->where('name', 'Test')->first();

    expect($found)->not->toBeNull();
    expect((string) $found->id)->toBe((string) $id);
});

it('validates ULID format for isValidUniqueId', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        public function testIsValidUniqueId($value): bool
        {
            return $this->isValidUniqueId($value);
        }
    };

    expect($model->testIsValidUniqueId('01ARZ3NDEKTSV4RRFFQ69G5FAV'))->toBeTrue();
    expect($model->testIsValidUniqueId(Ulid::generate()))->toBeTrue();
    expect($model->testIsValidUniqueId('not-a-ulid'))->toBeFalse();
    expect($model->testIsValidUniqueId(123))->toBeFalse();
});

it('generates chronologically sortable ULIDs', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $first = $model->create(['name' => 'First']);
    usleep(1000); // 1ms delay
    $second = $model->create(['name' => 'Second']);
    usleep(1000);
    $third = $model->create(['name' => 'Third']);

    // ULIDs should be sortable by their string representation
    $ids       = [$first->id, $second->id, $third->id];
    $sortedIds = $ids;
    usort($sortedIds, fn ($a, $b) => strcmp((string) $a, (string) $b));

    expect($sortedIds)->toBe($ids);
});

it('can use multiple ULID columns with ulidColumns method', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['id', 'name'];

        public function ulidColumns(): array
        {
            return ['id'];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->id)->toBeInstanceOf(Ulid::class);
});
