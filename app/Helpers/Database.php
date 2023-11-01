<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Database
{
    public function setDb(string $dbName, string $driver = 'mysql')
    {
        DB::disconnect($driver);

        $connection = config('database.connections.' . $driver);
        Config::set("database.connections." . $driver, [
            'driver' => 'mysql',
            'host' => $connection['host'],
            'username' => $connection['username'],
            'password' => $connection['password'],
            'database' => $dbName,
        ]);
    }
    public function setRemoteDb(string $dbName, string $driver = 'server_mysql')
    {
        DB::disconnect($driver);
        $credentials = $this->unpackCredentials(env(strtoupper($dbName)));

        $connection = config('database.connections.' . $driver);
        Config::set("database.connections." . $driver, [
            'driver' => 'mysql',
            'host' => $connection['host'],
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'database' => $credentials['database'],
        ]);
    }

    protected function unpackCredentials(string $packed): array
    {
        $credentials = [];

        collect(explode(',', $packed))->each(function ($var) use (&$credentials) {
            $parts = explode(':', $var);
            $credentials[$parts[0]] = $parts[1];
        });

        return $credentials;
    }

    public function getDatabaseList(string $envKey = 'INSTALLED_DATABASES'): array
    {
        if (! env($envKey)) {
            return [];
        }

        $databases = [];
        collect(explode(',', env($envKey)))
            ->each(function ($db) use (&$databases) {
                $parts = explode(':', $db);
                $site = $parts[0];
                $dbName = $parts[1];
                try {
                    // Make sure database exists before adding to list
                    self::setDb($dbName);
                    $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
                    $db = DB::select($query, [$dbName]);
                    if ($db) {
                        $databases[$site] = $dbName;
                    }
                    self::setDb(env('DB_DATABASE'));
                } catch (\Exception $e) {
                    return null;
                }
            });

        return $databases;
    }

    public function getInverseDatabaseList(): array
    {
        return array_flip(self::getDatabaseList());
    }

}
