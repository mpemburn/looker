<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void setDb(string $dbName, string $driver = 'mysql');
 * @method static array getDatabaseList(string $envKey = 'INSTALLED_DATABASES');
 * @method static array getInverseDatabaseList();
 */
class Database extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'database';
    }

}
