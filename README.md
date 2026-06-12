# Laravel MySQL Binary UUIDs

Store UUIDs and ULIDs as efficient binary(16) columns in MySQL instead of char(36) or char(26), saving storage space and improving index performance.

[![Tests](https://github.com/kenzal/mysql-binary-uuids/actions/workflows/tests.yml/badge.svg)](https://github.com/kenzal/mysql-binary-uuids/actions/workflows/tests.yml)
[![Code Style](https://github.com/kenzal/mysql-binary-uuids/actions/workflows/code-style.yml/badge.svg)](https://github.com/kenzal/mysql-binary-uuids/actions/workflows/code-style.yml)
[![PHP Version](https://img.shields.io/badge/php-8.2%20%7C%208.3%20%7C%208.4%20%7C%208.5-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-12%20%7C%2013-red.svg)](https://laravel.com)
[![Packagist Version](https://img.shields.io/packagist/v/kenzal/mysql-binary-uuids)](https://packagist.org/packages/kenzal/mysql-binary-uuids)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

## Table of Contents

- [Why Use Binary Storage?](#why-use-binary-storage)
- [Requirements](#requirements)
- [Installation](#installation)
- [Features](#features)
- [Usage](#usage)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Performance Considerations](#performance-considerations)
- [Compatibility](#compatibility)
- [Upgrading from String UUIDs](#upgrading-from-string-uuids)
- [Contributing](#contributing)
- [Credits](#credits)

## Why Use Binary Storage?

Storing UUIDs and ULIDs as binary data provides significant benefits:

- **Storage Efficiency**: Binary(16) uses 16 bytes vs char(36)/char(26) which uses 36/26 bytes
- **Index Performance**: Smaller indexes mean faster lookups and reduced memory usage
- **Native Format**: UUIDs/ULIDs are stored in their native binary format
- **Compatibility**: Works seamlessly with Laravel's UUID/ULID objects

### Storage Comparison

| Type | String Storage | Binary Storage | Savings |
|------|---------------|----------------|---------|
| UUID | 36 bytes (char) | 16 bytes (binary) | **56% reduction** |
| ULID | 26 bytes (char) | 16 bytes (binary) | **38% reduction** |

## Requirements

- PHP 8.2+ (for Laravel 12) or PHP 8.3+ (for Laravel 13)
- Laravel 12.0 or 13.0
- MySQL 5.7 or higher (MySQL 8.0+ recommended)

### Compatibility Matrix

| Laravel | PHP 8.2 | PHP 8.3 | PHP 8.4 | PHP 8.5 |
|---------|---------|---------|---------|---------|
| 12.x    | ✅      | ✅      | ✅      | ✅      |
| 13.x    | ❌      | ✅      | ✅      | ✅      |

## Installation

Install via Composer:

```bash
composer require kenzal/mysql-binary-uuids
```

The service provider will be automatically registered.

## Features

### ✨ Automatic Schema Support

UUIDs are automatically stored as `binary(16)` when using Laravel's schema builder:

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();  // Stored as binary(16)
    $table->uuid('organization_id');
    $table->timestamps();
});
```

### 🔄 Eloquent Casts

Cast binary UUID/ULID columns to native Laravel objects:

```php
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'id' => BinaryUuid::class,
            'session_id' => BinaryUlid::class,
        ];
    }
}
```

### 🎯 Model Traits

Drop-in replacements for Laravel's `HasUuids` and `HasUlids` traits:

```php
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;

class User extends Model
{
    use HasBinaryUuids;
    
    // That's it! Automatic UUID v7 generation with binary storage
}
```

### 🛠️ Blueprint Macros

Additional Blueprint methods for ULID columns:

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->binaryUlid('id')->primary();
    $table->binaryUlid('user_id');
    $table->foreignBinaryUlid('parent_id')->nullable();
});
```

## Usage

### Basic Usage with Casts

```php
use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;

class Post extends Model
{
    protected function casts(): array
    {
        return [
            'id' => BinaryUuid::class,
            'author_id' => BinaryUuid::class,
        ];
    }
}

// Create a post (provide an ID since no trait for auto-generation)
$post = Post::create([
    'id' => '019eb8b2-8b13-7232-a60c-f19b6f0827df',
    'title' => 'My Post',
    'author_id' => '550e8400-e29b-41d4-a716-446655440000',
]);

// Access as UUID objects
echo $post->id->toString(); // "019eb8b2-8b13-7232-a60c-f19b6f0827df"
echo $post->author_id->toString(); // "550e8400-e29b-41d4-a716-446655440000"

// Works with UUID objects too
use Ramsey\Uuid\Uuid;

$post->author_id = Uuid::uuid4();
$post->save();
```

### Using Model Traits

The `HasBinaryUuids` and `HasBinaryUlids` traits provide automatic ID generation:

```php
use Illuminate\Database\Eloquent\Model;
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;

class User extends Model
{
    use HasBinaryUuids;
    
    protected $fillable = ['name', 'email'];
}

// UUID v7 is automatically generated
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

echo $user->id->toString(); // Auto-generated UUID v7
```

#### Using ULIDs

```php
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUlids;

class Session extends Model
{
    use HasBinaryUlids;

    protected $fillable = ['user_id'];
}

$session = Session::create([
    'user_id' => $user->id,
]);

// ULID objects are chronologically sortable
echo $session->id; // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
```

### Custom Unique ID Columns

By default, traits apply to the `id` column. Customize via the `uuidColumns()` or `ulidColumns()` methods:

```php
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;

class Document extends Model
{
    use HasBinaryUuids;
    
    public function uuidColumns(): array
    {
        return ['id', 'document_number', 'revision_id'];
    }
}

// All three columns get binary UUID casts and auto-generation
```

### Custom UUID/ULID Types

You can specify custom UUID/ULID subclasses for specific columns using string keys. This is useful when you need to extend UUIDs with domain-specific behavior.

First, create your custom UUID class:

```php
use Kenzal\MysqlBinaryUuids\ExtensibleUuid;

class DocumentUuid extends ExtensibleUuid
{
    // ...
}
```

Then reference it in your model:

```php
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;

class Document extends Model
{
    use HasBinaryUuids;
    
    public function uuidColumns(): array
    {
        return [
            'id',  // Uses default BinaryUuid cast
            'document_uuid' => DocumentUuid::class,  // Custom UUID subclass
        ];
    }
}
```

The same pattern works with `ExtensibleUlid` and `ulidColumns()`.

#### Using Directly in `casts()`

Since `ExtensibleUuid` and `ExtensibleUlid` implement Laravel's `Castable` interface, you can also use them directly in the `casts()` array without traits:

```php
use Kenzal\MysqlBinaryUuids\Casts\BinaryUuid;
use Kenzal\MysqlBinaryUuids\ExtensibleUuid;

class CustomUuid extends ExtensibleUuid {}

class Document extends Model
{
    protected function casts(): array
    {
        return [
            'id' => BinaryUuid::class,
            'document_uuid' => CustomUuid::class,
        ];
    }
}
```

### Using Both UUID and ULID Columns

A model can have both UUID and ULID columns by using one trait for auto-generation and explicit casts for others:

```php
use Kenzal\MysqlBinaryUuids\Concerns\HasBinaryUuids;
use Kenzal\MysqlBinaryUuids\Casts\BinaryUlid;

class MixedModel extends Model
{
    use HasBinaryUuids;  // For UUID primary key
    
    public function uuidColumns(): array
    {
        return ['id'];  // UUID auto-generated
    }
    
    protected function casts(): array
    {
        return [
            'session_id' => BinaryUlid::class,  // ULID manually specified
        ];
    }
}
```

### Migration Examples

#### Creating Tables with UUIDs

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
            
            $table->foreign('organization_id')
                  ->references('id')
                  ->on('organizations')
                  ->onDelete('cascade');
        });
    }
};
```

#### Creating Tables with ULIDs

```php
Schema::create('sessions', function (Blueprint $table) {
    $table->binaryUlid('id')->primary();
    $table->uuid('user_id');
    $table->string('ip_address');
    $table->text('user_agent');
    $table->timestamp('last_activity');
    
    $table->index('user_id');
    $table->index('last_activity');
});
```

#### Nullable UUID/ULID Columns

```php
Schema::create('posts', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('parent_id')->nullable(); // Optional parent post
    $table->uuid('author_id');
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

### Working with Existing Data

If you're migrating from string-based UUIDs, create a migration to convert:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First, create a temporary column
        DB::statement('ALTER TABLE users ADD COLUMN id_binary BINARY(16) AFTER id');
        
        // Convert existing UUIDs to binary
        DB::statement('UPDATE users SET id_binary = UNHEX(REPLACE(id, "-", ""))');
        
        // Drop old column and rename new one
        DB::statement('ALTER TABLE users DROP PRIMARY KEY, DROP COLUMN id');
        DB::statement('ALTER TABLE users CHANGE id_binary id BINARY(16)');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
    }
    
    public function down(): void
    {
        // Convert back to char(36) if needed
        DB::statement('ALTER TABLE users ADD COLUMN id_char CHAR(36) AFTER id');
        DB::statement('UPDATE users SET id_char = LOWER(CONCAT(
            HEX(SUBSTRING(id, 1, 4)), "-",
            HEX(SUBSTRING(id, 5, 2)), "-",
            HEX(SUBSTRING(id, 7, 2)), "-",
            HEX(SUBSTRING(id, 9, 2)), "-",
            HEX(SUBSTRING(id, 11, 6))
        ))');
        DB::statement('ALTER TABLE users DROP PRIMARY KEY, DROP COLUMN id');
        DB::statement('ALTER TABLE users CHANGE id_char id CHAR(36)');
        DB::statement('ALTER TABLE users ADD PRIMARY KEY (id)');
    }
};
```

## API Reference

### Casts

#### `BinaryUuid`

Casts binary(16) columns to `Ramsey\Uuid\UuidInterface` objects.

```php
protected function casts(): array
{
    return [
        'id' => BinaryUuid::class,
    ];
}
```

**Methods:**
- `get()`: Converts binary data to UUID object
- `set()`: Accepts UUID strings or objects, converts to binary

#### `BinaryUlid`

Casts binary(16) columns to `Symfony\Component\Uid\Ulid` objects.

```php
protected function casts(): array
{
    return [
        'id' => BinaryUlid::class,
    ];
}
```

**Methods:**
- `get()`: Converts binary data to ULID object
- `set()`: Accepts ULID strings or objects, converts to binary

### Traits

#### `HasBinaryUuids`

Provides automatic UUID v7 generation with binary storage.

**Features:**
- Generates UUID v7 for new models
- Applies `BinaryUuid` cast to `uuidColumns()` columns
- Sets `$keyType = 'string'` and `$incrementing = false` (via `HasUniqueStringIds`)
- Validates UUID format for route model binding

**Methods:**
- `newUniqueId()`: Generates a new UUID v7
- `isValidUniqueId($value)`: Validates UUID format
- `uuidColumns()`: Returns array of columns that should have UUIDs (default: `['id']`)
  - Supports custom UUID subclasses: `['uuid_id' => DocumentUuid::class]`

#### `HasBinaryUlids`

Provides automatic ULID generation with binary storage.

**Features:**
- Generates ULIDs for new models
- Applies `BinaryUlid` cast to `ulidColumns()` columns
- Sets `$keyType = 'string'` and `$incrementing = false` (via `HasUniqueStringIds`)
- Validates ULID format for route model binding
- ULIDs are chronologically sortable

**Methods:**
- `newUniqueId()`: Generates a new ULID
- `isValidUniqueId($value)`: Validates ULID format
- `ulidColumns()`: Returns array of columns that should have ULIDs (default: `['id']`)
  - Supports custom ULID subclasses: `['ulid_id' => CustomUlid::class]`

### Blueprint Macros

#### `binaryUlid(string $column = 'ulid')`

Create a binary(16) ULID column.

```php
$table->binaryUlid('id')->primary();
$table->binaryUlid('session_id')->nullable();
```

#### `foreignBinaryUlid(string $column)`

Create a foreign key column for referencing a binary ULID.

```php
$table->foreignBinaryUlid('parent_id')
      ->references('id')
      ->on('parents');
```

## Testing

The package includes a comprehensive test suite:

```bash
composer test
```

Run specific test suites:

```bash
composer test -- --filter=Unit
composer test -- --filter=Feature
composer test -- --filter=HasBinaryUuids
```

## Performance Considerations

### Query Performance

Binary UUIDs maintain excellent query performance:

```php
// Both work efficiently with proper indexing
$user = User::where('id', $uuid)->first();
$user = User::find($uuid);
```

### Index Recommendations

For optimal performance:

1. **Always index UUID/ULID foreign keys:**
   ```php
   $table->uuid('organization_id');
   $table->index('organization_id');
   ```

2. **Use UUID v7 or ULIDs for time-ordered data:**
   - Both have time-based sorting
   - Reduces index fragmentation
   - Better for clustered indexes

3. **Consider composite indexes:**
   ```php
   $table->index(['organization_id', 'created_at']);
   ```

## Compatibility

### UUID Versions

This package works with all UUID versions:
- **UUID v4**: Random UUIDs
- **UUID v7**: Time-ordered UUIDs (recommended, used by default)

The `HasBinaryUuids` trait generates UUID v7 by default for better database performance.

### ULID Format

ULIDs are 128-bit identifiers:
- 48-bit timestamp (millisecond precision)
- 80-bit random component
- Lexicographically sortable
- Case-insensitive base32 encoding

## Upgrading from String UUIDs

If you're currently using `char(36)` or `varchar(36)` for UUIDs:

1. Install this package
2. Update your models to use the traits or casts
3. Create migrations to convert columns (see "Working with Existing Data")
4. Test thoroughly in a staging environment
5. Deploy to production

**Note:** This is a breaking change for your database schema. Plan accordingly.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
git clone https://github.com/kenzal/mysql-binary-uuids.git
cd mysql-binary-uuids
composer install
```

### Local Testing Configuration

Copy `phpunit.xml.dist` to `phpunit.xml` and customize for your local environment:

```bash
cp phpunit.xml.dist phpunit.xml
```

Then edit `phpunit.xml` with your local MySQL credentials:

```xml
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_DATABASE" value="laravel_binary_uuids_test"/>
<env name="DB_USERNAME" value="your_username"/>
<env name="DB_PASSWORD" value="your_password"/>
```

**Note**: `phpunit.xml` is gitignored so your local credentials won't be committed.

### Running Tests

```bash
composer test
```

Or run specific test suites:

```bash
# Run only unit tests
composer test:unit

# Run only feature tests
composer test:feature
```

### Code Style

This package uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Before submitting a PR:

```bash
# Check code style
composer format:test

# Fix code style issues
composer format
```

Make sure all tests pass and code style checks pass before submitting a PR.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **Author**: J. Kenzal Hunter, Sr. ([PGP Info](https://ksavalon.net/pgp), [PGP Key](https://ksavalon.net/pgp/public.asc))
- **Laravel**: Taylor Otwell and the Laravel community
- **Ramsey UUID**: Ben Ramsey
- **Symfony UID**: Symfony community

## Support

- **Issues**: [GitHub Issues](https://github.com/kenzal/mysql-binary-uuids/issues)
- **Discussions**: [GitHub Discussions](https://github.com/kenzal/mysql-binary-uuids/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

**Made with ❤️ for the Laravel community**
