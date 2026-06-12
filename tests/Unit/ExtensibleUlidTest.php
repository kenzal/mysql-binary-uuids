<?php

use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Kenzal\MysqlBinaryUuids\ExtensibleUlid;
use Kenzal\MysqlBinaryUuids\Tests\Support\CustomUlid;
use Symfony\Component\Uid\Ulid;

it('returns a CustomUlid from fromBinary', function () {
    $ulid = CustomUlid::fromBinary(Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary());

    expect($ulid)->toBeInstanceOf(CustomUlid::class);
    expect((string) $ulid)->toBe('01ARZ3NDEKTSV4RRFFQ69G5FAV');
});

it('returns a CustomUlid from fromString', function () {
    $ulid = CustomUlid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

    expect($ulid)->toBeInstanceOf(CustomUlid::class);
    expect((string) $ulid)->toBe('01ARZ3NDEKTSV4RRFFQ69G5FAV');
});

it('returns ExtensibleUlid itself when used directly', function () {
    $ulid = ExtensibleUlid::fromBinary(Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary());

    expect($ulid)->toBeInstanceOf(ExtensibleUlid::class);
    expect($ulid)->toBeInstanceOf(Ulid::class);
});

it('works with BinaryUlid cast via colon syntax', function () {
    $cast = new BinaryUlid(CustomUlid::class);

    $model  = new class extends Model {};
    $result = $cast->get($model, 'id', Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary(), []);

    expect($result)->toBeInstanceOf(CustomUlid::class);
    expect((string) $result)->toBe('01ARZ3NDEKTSV4RRFFQ69G5FAV');

    $binary = $cast->set($model, 'id', $result, []);
    expect($binary)->toBe(Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary());
});

it('can be used directly in casts() via Castable', function () {
    $model = new class extends Model
    {
        protected function casts(): array
        {
            return [
                'id' => CustomUlid::class,
            ];
        }
    };

    $model->setRawAttributes(['id' => Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV')->toBinary()]);
    $result = $model->getAttribute('id');

    expect($result)->toBeInstanceOf(CustomUlid::class);
    expect((string) $result)->toBe('01ARZ3NDEKTSV4RRFFQ69G5FAV');
});
