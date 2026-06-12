<?php

use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;
use Symfony\Component\Uid\Ulid;

beforeEach(function () {
    $this->cast  = new BinaryUlid;
    $this->model = new class extends Model {};
});

it('converts a ULID string to binary on set', function () {
    $ulid     = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $expected = Ulid::fromString($ulid)->toBinary();

    $binary = $this->cast->set($this->model, 'id', $ulid, []);

    expect($binary)->toBe($expected);
});

it('converts a ULID object to binary on set', function () {
    $ulid     = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
    $expected = $ulid->toBinary();

    $binary = $this->cast->set($this->model, 'id', $ulid, []);

    expect($binary)->toBe($expected);
});

it('converts binary back to a ULID object on get', function () {
    $ulidString = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $binary     = Ulid::fromString($ulidString)->toBinary();

    $result = $this->cast->get($this->model, 'id', $binary, []);

    expect($result)->toBeInstanceOf(Ulid::class);
    expect((string) $result)->toBe($ulidString);
});

it('returns null for null values', function () {
    expect($this->cast->get($this->model, 'id', null, []))->toBeNull();
    expect($this->cast->set($this->model, 'id', null, []))->toBeNull();
});

it('throws for an invalid ULID string', function () {
    $this->cast->set($this->model, 'id', 'not-a-ulid!!', []);
})->throws(InvalidArgumentException::class);

it('accepts a custom ULID class via constructor and returns an instance of it', function () {
    $customUlidClass = new class extends Ulid {};
    $ulidString      = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $binary          = Ulid::fromString($ulidString)->toBinary();

    $cast   = new BinaryUlid($customUlidClass::class);
    $result = $cast->get($this->model, 'id', $binary, []);

    expect($result)->toBeInstanceOf($customUlidClass::class);
    expect((string) $result)->toBe($ulidString);
});

it('set() is unchanged when a custom ULID class is provided', function () {
    $customUlidClass = new class extends Ulid {};
    $cast            = new BinaryUlid($customUlidClass::class);

    $ulidString = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    $binary     = $cast->set($this->model, 'id', $ulidString, []);

    expect($binary)->toBe(Ulid::fromString($ulidString)->toBinary());
});

it('throws for a non-existent custom ULID class', function () {
    new BinaryUlid('Nonexistent\\Ulid\\Class');
})->throws(InvalidArgumentException::class, 'does not exist');

it('throws for a class not extending Ulid', function () {
    /** @noinspection PhpUndefinedClassInspection */
    new BinaryUlid(self::class);
})->throws(InvalidArgumentException::class, 'must extend');
