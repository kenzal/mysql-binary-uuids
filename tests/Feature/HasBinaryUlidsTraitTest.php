<?php

/**
 * @noinspection PhpExpressionAlwaysNullInspection
 * @noinspection PhpPossiblePolymorphicInvocationInspection
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpUnusedLocalVariableInspection
 * @noinspection SqlResolve
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

it('sets keyType to uuid for ULID columns', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';
    };

    expect($model->getKeyType())->toBe('uuid');
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

it('resolves route binding with a string ULID', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance   = $model->create(['name' => 'Test']);
    $ulidString = (string) $instance->id;

    $found = $model->resolveRouteBinding($ulidString);

    expect($found)->not->toBeNull();
    expect((string) $found->id)->toBe($ulidString);
});

it('resolves route binding with a ULID object', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    $found = $model->resolveRouteBinding($instance->id);

    expect($found)->not->toBeNull();
    expect((string) $found->id)->toBe((string) $instance->id);
});

it('throws ModelNotFoundException for invalid route binding value', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';
    };

    $model->resolveRouteBinding('not-a-ulid');
})->throws(ModelNotFoundException::class);

it('finds a model by ULID object', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    $found = $model->find($instance->id);

    expect($found)->not->toBeNull();
    expect((string) $found->id)->toBe((string) $instance->id);
});

it('finds multiple models by an array of ULID objects', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $a = $model->create(['name' => 'A']);
    $b = $model->create(['name' => 'B']);
    $c = $model->create(['name' => 'C']);

    $results = $model->find([$a->id, $c->id]);

    expect($results)->toHaveCount(2);
    expect((string) $results->first()->id)->toBe((string) $a->id);
    expect((string) $results->last()->id)->toBe((string) $c->id);
});

it('finds multiple models using findMany with ULID objects', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $a = $model->create(['name' => 'A']);
    $b = $model->create(['name' => 'B']);
    $c = $model->create(['name' => 'C']);

    $results = $model->findMany([$a->id, $b->id]);

    expect($results)->toHaveCount(2);
    expect((string) $results->first()->id)->toBe((string) $a->id);
    expect((string) $results->last()->id)->toBe((string) $b->id);
});

it('cannot find a model by raw string ULID', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'ulid_trait_models';

        protected $fillable = ['name'];
    };

    $instance = $model->create(['name' => 'Test']);

    $found = $model->find((string) $instance->id);

    expect($found)->toBeNull();
});
