<?php

use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Kenzal\MysqlBinaryUuids\ExtensibleUuid;
use Kenzal\MysqlBinaryUuids\Tests\Support\CustomUuid;
use Ramsey\Uuid\UuidInterface;

it('returns a CustomUuid from fromBytes', function () {
    $uuid = CustomUuid::fromBytes(hex2bin('550e8400e29b41d4a716446655440000'));

    expect($uuid)->toBeInstanceOf(CustomUuid::class);
    expect($uuid->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('returns a CustomUuid from fromString', function () {
    $uuid = CustomUuid::fromString('550e8400-e29b-41d4-a716-446655440000');

    expect($uuid)->toBeInstanceOf(CustomUuid::class);
    expect($uuid->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('returns a CustomUuid from uuid4', function () {
    $uuid = CustomUuid::uuid4();

    expect($uuid)->toBeInstanceOf(CustomUuid::class);
    expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('returns a CustomUuid from uuid7', function () {
    $uuid = CustomUuid::uuid7();

    expect($uuid)->toBeInstanceOf(CustomUuid::class);
    expect($uuid->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('returns ExtensibleUuid itself when used directly', function () {
    $uuid = ExtensibleUuid::fromBytes(hex2bin('550e8400e29b41d4a716446655440000'));

    expect($uuid)->toBeInstanceOf(ExtensibleUuid::class);
    expect($uuid)->toBeInstanceOf(UuidInterface::class);
});

it('works with BinaryUuid cast via colon syntax', function () {
    $cast = new BinaryUuid(CustomUuid::class);

    $model  = new class extends Model {};
    $result = $cast->get($model, 'id', hex2bin('550e8400e29b41d4a716446655440000'), []);

    expect($result)->toBeInstanceOf(CustomUuid::class);
    expect($result->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');

    $binary = $cast->set($model, 'id', $result, []);
    expect($binary)->toBe(hex2bin('550e8400e29b41d4a716446655440000'));
});

it('can be used directly in casts() via Castable', function () {
    $model = new class extends Model
    {
        protected function casts(): array
        {
            return [
                'id' => CustomUuid::class,
            ];
        }
    };

    $model->setRawAttributes(['id' => hex2bin('550e8400e29b41d4a716446655440000')]);
    $result = $model->getAttribute('id');

    expect($result)->toBeInstanceOf(CustomUuid::class);
    expect($result->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');
});
