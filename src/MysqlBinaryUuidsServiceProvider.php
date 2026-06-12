<?php

namespace Kenzal\MysqlBinaryUuids;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\ServiceProvider;
use Kenzal\MysqlBinaryUuids\Connection\MySqlConnection;
use Kenzal\MysqlBinaryUuids\Grammar\MySqlGrammar;

class MysqlBinaryUuidsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
            return new MySqlConnection($connection, $database, $prefix, $config);
        });
    }

    public function boot(): void
    {
        $this->registerGrammar();
        $this->registerBlueprintMacros();
    }

    protected function registerGrammar(): void
    {
        $this->callAfterResolving('db', function ($db) {
            $db->extend('mysql', function (array $config, string $name) {
                $factory    = new ConnectionFactory($this->app);
                $connection = $factory->make($config, $name);
                $connection->setSchemaGrammar(new MySqlGrammar($connection));

                return $connection;
            });
        });
    }

    protected function registerBlueprintMacros(): void
    {
        Blueprint::macro('binaryUlid', function (string $column = 'ulid'): ColumnDefinition {
            /** @var Blueprint $this */
            return $this->addColumn('ulid', $column);
        });

        Blueprint::macro('foreignBinaryUlid', function (string $column): ColumnDefinition {
            /** @var Blueprint $this */
            return $this->addColumn('ulid', $column);
        });
    }
}
