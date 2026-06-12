<?php

use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

beforeEach(function () {
    $this->cast  = new BinaryUuid;
    $this->model = new class extends Model {};
});

it('converts a UUID string to binary on set', function () {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';

    $binary = $this->cast->set($this->model, 'id', $uuid, []);

    expect($binary)->toBe(hex2bin('550e8400e29b41d4a716446655440000'));
});

it('converts a UUID object to binary on set', function () {
    $uuid = Uuid::fromString('550e8400-e29b-41d4-a716-446655440000');

    $binary = $this->cast->set($this->model, 'id', $uuid, []);

    expect($binary)->toBe(hex2bin('550e8400e29b41d4a716446655440000'));
});

it('converts binary back to a UUID object on get', function () {
    $binary = hex2bin('550e8400e29b41d4a716446655440000');

    $uuid = $this->cast->get($this->model, 'id', $binary, []);

    expect($uuid)->toBeInstanceOf(UuidInterface::class);
    expect($uuid->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('returns null for null values', function () {
    expect($this->cast->get($this->model, 'id', null, []))->toBeNull();
    expect($this->cast->set($this->model, 'id', null, []))->toBeNull();
});

it('throws for an invalid UUID string', function () {
    $this->cast->set($this->model, 'id', 'not-a-uuid', []);
})->throws(InvalidArgumentException::class);

it('strips braces from a UUID string', function () {
    $uuid = '{550e8400-e29b-41d4-a716-446655440000}';

    $binary = $this->cast->set($this->model, 'id', $uuid, []);

    expect($binary)->toBe(hex2bin('550e8400e29b41d4a716446655440000'));
});

it('accepts a custom UUID class via constructor', function () {
    $cast = new BinaryUuid(Uuid::class);

    $binary = hex2bin('550e8400e29b41d4a716446655440000');
    $uuid   = $cast->get($this->model, 'id', $binary, []);

    expect($uuid)->toBeInstanceOf(UuidInterface::class);
    expect($uuid->toString())->toBe('550e8400-e29b-41d4-a716-446655440000');
});

it('set() is unchanged when a custom UUID class is provided', function () {
    $cast = new BinaryUuid(Uuid::class);

    $binary = $cast->set($this->model, 'id', '550e8400-e29b-41d4-a716-446655440000', []);

    expect($binary)->toBe(hex2bin('550e8400e29b41d4a716446655440000'));
});

it('throws for a non-existent custom UUID class', function () {
    new BinaryUuid('Nonexistent\\Uuid\\Class');
})->throws(InvalidArgumentException::class, 'does not exist');

it('throws for a class not implementing UuidInterface', function () {
    /** @noinspection PhpUndefinedClassInspection */
    new BinaryUuid(self::class);
})->throws(InvalidArgumentException::class, 'must implement');
