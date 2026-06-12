<?php

/**
 * @noinspection SqlResolve
 * @noinspection PhpUndefinedMethodInspection
 * @noinspection PhpExpressionAlwaysNullInspection
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUlids;
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;
use Kenzal\MysqlBinaryUuids\Tests\Support\CustomUlid;
use Kenzal\MysqlBinaryUuids\Tests\Support\DocumentUuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    Schema::dropIfExists('mixed_id_models');

    Schema::create('mixed_id_models', function ($table) {
        $table->uuid('uuid_id')->nullable();
        $table->binaryUlid('ulid_id')->nullable();
        $table->uuid('secondary_uuid')->nullable();
        $table->binaryUlid('secondary_ulid')->nullable();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('mixed_id_models');
});

it('can have both UUID and ULID columns with explicit casts', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'uuid_id';

        protected $fillable = ['name', 'ulid_id'];

        // Only the primary key gets UUID
        public function uuidColumns(): array
        {
            return ['uuid_id'];
        }

        protected function casts(): array
        {
            return array_merge(parent::casts(), [
                'secondary_uuid' => BinaryUuid::class,
                'ulid_id'        => BinaryUlid::class,
                'secondary_ulid' => BinaryUlid::class,
            ]);
        }
    };

    $ulidId   = Ulid::generate();
    $instance = $model->create([
        'name'    => 'Test',
        'ulid_id' => $ulidId,
    ]);

    // UUID columns
    expect($instance->uuid_id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->secondary_uuid)->toBeNull();

    // ULID columns use explicit casts
    expect($instance->ulid_id)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->ulid_id)->toBe($ulidId);
    expect($instance->secondary_ulid)->toBeNull();
});

it('supports custom UUID subclasses for columns with string keys', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'uuid_id';

        protected $fillable = ['name', 'secondary_uuid'];

        public function uuidColumns(): array
        {
            return [
                'uuid_id', // Auto-generated, uses default BinaryUuid cast
            ];
        }

        protected function casts(): array
        {
            return array_merge(parent::casts(), [
                'secondary_uuid' => BinaryUuid::class, // Manual, uses explicit cast
            ]);
        }
    };

    $uuid     = '550e8400-e29b-41d4-a716-446655440000';
    $instance = $model->create([
        'name'           => 'Test',
        'secondary_uuid' => $uuid,
    ]);

    expect($instance->uuid_id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->secondary_uuid)->toBeInstanceOf(UuidInterface::class);
    expect($instance->secondary_uuid->toString())->toBe($uuid);
});

it('supports mixing HasBinaryUlids trait with explicit BinaryUlid casts', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'ulid_id';

        protected $fillable = ['name', 'secondary_ulid'];

        public function ulidColumns(): array
        {
            return [
                'ulid_id', // Auto-generated, uses default BinaryUlid cast
            ];
        }

        protected function casts(): array
        {
            return array_merge(parent::casts(), [
                'secondary_ulid' => BinaryUlid::class, // Manual, uses explicit cast
            ]);
        }
    };

    $ulid     = Ulid::generate();
    $instance = $model->create([
        'name'           => 'Test',
        'secondary_ulid' => $ulid,
    ]);

    expect($instance->ulid_id)->toBeInstanceOf(Ulid::class);
    expect($instance->secondary_ulid)->toBeInstanceOf(Ulid::class);
    expect((string) $instance->secondary_ulid)->toBe($ulid);
});

it('supports custom ULID subclasses via string keys in ulidColumns', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'ulid_id';

        protected $fillable = ['name'];

        public function ulidColumns(): array
        {
            return [
                'ulid_id' => CustomUlid::class,
            ];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->ulid_id)->toBeInstanceOf(CustomUlid::class);
});

it('supports custom UUID subclasses via string keys in uuidColumns', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'uuid_id';

        protected $fillable = ['name'];

        public function uuidColumns(): array
        {
            return [
                'uuid_id' => DocumentUuid::class,
            ];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->uuid_id)->toBeInstanceOf(DocumentUuid::class);
});

it('supports mixing numeric and string keys in uuidColumns', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $fillable = ['name', 'secondary_uuid'];

        public function uuidColumns(): array
        {
            return [
                'uuid_id',
                'secondary_uuid' => DocumentUuid::class,
            ];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->uuid_id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->secondary_uuid)->toBeInstanceOf(DocumentUuid::class);
});

it('can auto-generate UUIDs for multiple columns via uuidColumns numeric keys', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $fillable = ['name'];

        public function uuidColumns(): array
        {
            return ['uuid_id', 'secondary_uuid'];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->uuid_id)->toBeInstanceOf(UuidInterface::class);
    expect($instance->secondary_uuid)->toBeInstanceOf(UuidInterface::class);
});

it('can auto-generate ULIDs for multiple columns via ulidColumns numeric keys', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'mixed_id_models';

        protected $fillable = ['name'];

        public function ulidColumns(): array
        {
            return ['ulid_id', 'secondary_ulid'];
        }
    };

    $instance = $model->create(['name' => 'Test']);

    expect($instance->ulid_id)->toBeInstanceOf(Ulid::class);
    expect($instance->secondary_ulid)->toBeInstanceOf(Ulid::class);
});

it('validates custom UUID classes implement UuidInterface', function () {
    $model = new class extends Model
    {
        use HasBinaryUuids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'uuid_id';

        public function uuidColumns(): array
        {
            return [
                'uuid_id' => 'InvalidUuidClass',
            ];
        }
    };

    // This should throw when initializing
    $model->create(['name' => 'Test']);
})->throws(InvalidArgumentException::class, 'Class [InvalidUuidClass] does not exist');

it('validates custom ULID classes extend Ulid', function () {
    $model = new class extends Model
    {
        use HasBinaryUlids;

        protected $table = 'mixed_id_models';

        protected $primaryKey = 'ulid_id';

        public function ulidColumns(): array
        {
            return [
                'ulid_id' => 'InvalidUlidClass',
            ];
        }
    };

    // This should throw when initializing
    $model->create(['name' => 'Test']);
})->throws(InvalidArgumentException::class, 'Class [InvalidUlidClass] does not exist');
