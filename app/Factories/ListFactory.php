<?php

namespace App\Factories;

use App\Services\BlogService;
use Illuminate\Http\JsonResponse;

class ListFactory
{
    protected static string $database;
    protected static string $type;

    public static function build(string $type, string $database): JsonResponse
    {
        static::$database = $database;
        static::$type = $type;

        return match ($type) {
            'plugins' => self::response('getPluginList'),
            'post_type' => self::response('getPostTypeList'),
            'roles' => self::response('getRolesList'),
            'themes' => self::response( 'getThemeList'),
            default => null
        };
    }

    protected static function response(string $method): JsonResponse
    {
        $list = [];
        $type = static::$type;

        $service = new BlogService();
        if (method_exists($service, $method)) {
            $list = call_user_func([$service, $method], static::$database);
        }

        return response()->json(['type' => $type, "{$type}" => $list]);
    }
}
