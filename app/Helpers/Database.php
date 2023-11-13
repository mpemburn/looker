<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Database
{
    public function setDb(string $dbName, string $default = 'mysql')
    {
        if (config('database.is_remote')) {
            $this->setRemoteDb($dbName);

            return;
        }

        DB::disconnect($default);

        $connection = config('database.connections.' . $default);
        Config::set("database.connections." . $default, [
            'driver' => 'mysql',
            'host' => $connection['host'],
            'username' => $connection['username'],
            'password' => $connection['password'],
            'database' => $dbName,
        ]);
    }

    public function setRemoteDb(string $dbName)
    {
        Log::debug('It is remote');
        $credString = env(strtoupper($dbName));
        if (! $credString) {
            return;
        }

        Config::set('database.default', 'remote_mysql');
        $default = config('database.default');

        DB::disconnect($default);

        $credentials = $this->unpackCredentials($credString);

        $connection = config('database.connections.' . $default);
        Config::set("database.connections." . $default, [
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

    public function getDatabaseList(string $source = 'remote'): array
    {
        $envKey = 'INSTALLED_DATABASES';
        if (! env($envKey)) {
            return [];
        }

        $databases = [];
        collect(explode(',', env($envKey)))
            ->each(function ($db) use (&$databases) {
                $parts = explode(':', $db);
                $site = $parts[0];
                $dbName = $parts[1];
                $dbExists = true;

                if (! config('database.is_remote')) {
                    $dbExists = $this->testLocalDatabase($dbName);
                }

                if ($dbExists) {
                    $databases[$site] = $dbName;
                }
             });

        return $databases;
    }

    protected function testLocalDatabase(string $dbName): bool
    {
        try {
            // Make sure database exists before adding to list
            self::setDb($dbName);
            $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
            $db = DB::select($query, [$dbName]);
            self::setDb(env('DB_DATABASE'));

            return (bool) $db;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getInverseDatabaseList(): array
    {
        return array_flip(self::getDatabaseList());
    }

}
