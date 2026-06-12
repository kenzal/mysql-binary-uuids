# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-12

### Added
- **Connection-level binding conversion**: New `MySqlConnection::prepareBindings()` (registered via a connection resolver in the service provider) automatically converts `UuidInterface` and `Ulid` objects to binary bytes before querying. This ensures all query paths (`find`, `where`, subqueries) are handled uniformly at the connection layer.
- **`getKeyType()` override**: Both traits now override `getKeyType()` to return `'uuid'` when the primary key is in `uniqueIds()`. This prevents `Eloquent\Builder::whereKey()` from stringifying UUID/ULID objects before they reach `prepareBindings()`.
- **Cast-guarded route binding**: Both traits now override `resolveRouteBindingQuery()` to convert string route values to UUID/ULID objects before resolving. The conversion is guarded by a new `hasBinary{Uuid,Ulid}Cast()` check on the field's cast type, rather than relying on `uniqueIds()` membership.
- **Tests**: Route-model binding tests (string, object, invalid), `find($uuidObject)`/`find($ulidObject)`, `find([...])` with object arrays, `findMany([...])`, and `find($string)` returning `null` — for both UUID and ULID traits.

### Changed
- Key type assertion in trait tests updated from `'string'` to `'uuid'` to match the new `getKeyType()` behavior.

## [1.0.1] - 2026-06-12

### Fixed
- Remove unnecessary composer dependencies (`illuminate/contracts`, `ramsey/uuid`, `symfony/uid`) that are already pulled in by `illuminate/database`

## [1.0.0] - 2026-06-11

### Core Features
- **Binary UUID Storage**: Store UUIDs as binary(16) instead of char(36), reducing storage by 56%
- **Binary ULID Storage**: Store ULIDs as binary(16) instead of char(26), reducing storage by 38%
- **Automatic Schema Grammar Override**: `uuid()` Blueprint method automatically creates binary(16) columns

### Eloquent Casts
- `BinaryUuid` cast - Converts between binary(16) database storage and `Ramsey\Uuid\UuidInterface` objects
- `BinaryUlid` cast - Converts between binary(16) database storage and `Symfony\Component\Uid\Ulid` objects
- Support for both string and object inputs when setting values
- Proper null handling for nullable columns

### Model Traits
- `HasBinaryUuids` trait - Drop-in replacement for Laravel's `HasUuids` trait
  - Automatic UUID v7 generation for new models
  - Automatic `BinaryUuid` cast application
  - Configurable via `uuidColumns()` method
  - Route model binding validation
- `HasBinaryUlids` trait - Drop-in replacement for Laravel's `HasUlids` trait
  - Automatic ULID generation for new models
  - Automatic `BinaryUlid` cast application
  - Configurable via `ulidColumns()` method
  - Route model binding validation
  - Chronologically sortable identifiers
- Both traits can be used on the same model via one trait and explicit casts for the other type

### Blueprint Macros
- `binaryUlid(string $column = 'ulid')` - Create binary(16) ULID columns
- `foreignBinaryUlid(string $column)` - Create foreign key columns for binary ULIDs

### Service Provider
- `MysqlBinaryUuidsServiceProvider` - Automatic registration and configuration
- MySQL grammar override for schema operations
- Blueprint macro registration

### Custom Cast Support
- Custom UUID/ULID subclasses per column via string keys in `uuidColumns()` and `ulidColumns()`
  - Numeric keys: `['id', 'document_id']` uses default cast
  - String keys: `['id' => CustomUuid::class]` uses custom cast
- Cast type validation to ensure proper implementation
- `ExtensibleUuid` and `ExtensibleUlid` base classes implementing `Castable` for use directly in `casts()`

### Technical Details
- **PHP Version**: PHP 8.2-8.5 (Laravel 12 supports 8.2+, Laravel 13 requires 8.3+)
- **Laravel Version**: Supports Laravel 12.0 and 13.0
- **Database**: MySQL 5.7 or higher (MySQL 8.0+ recommended)
- **Code Style**: Laravel Pint for consistent formatting
- **Testing**: Comprehensive test suite with 81 tests and 186 assertions
  - Tested on PHP 8.2, 8.3, 8.4, and 8.5
  - Tested on Laravel 12.x and 13.x (with appropriate PHP versions)
- **CI/CD**: GitHub Actions for automated testing and code style checks
  - Unit tests for casts, grammar, and extensible types
  - Feature tests for database integration, model traits, and Blueprint macros

### Dependencies
- `ramsey/uuid` - For UUID object handling (included with Laravel)
- `symfony/uid` - For ULID object handling (included with Laravel)

[1.1.0]: https://github.com/kenzal/mysql-binary-uuids/releases/tag/v1.1.0
[1.0.0]: https://github.com/kenzal/mysql-binary-uuids/releases/tag/v1.0.0
