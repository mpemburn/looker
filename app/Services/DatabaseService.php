<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseService
{
    public static function setDb(string $dbName, string $driver = 'mysql')
    {
        DB::disconnect('mysql');

        $connection = config('database.connections.' . $driver);
        Config::set("database.connections." . $driver, [
            'driver' => 'mysql',
            'host' => $connection['host'],
            'username' => $connection['username'],
            'password' => $connection['password'],
            'database' => $dbName,
        ]);
    }

    public static function getDatabaseList(string $envKey = 'INSTALLED_DATABASES'): array
    {
        if (! env($envKey)) {
            return [];
        }

        $databases = [];
        collect(explode(',', env($envKey)))
            ->each(function ($db) use (&$databases) {
                $parts = explode(':', $db);
                $databases[$parts[0]] = $parts[1];
            });

        return $databases;
    }

    public static function getInverseDatabaseList(): array
    {
        return array_flip(self::getDatabaseList());
    }
}
