<?php

namespace App\Core;

use Dotenv\Dotenv;

class Config
{
    private static $settings = [];

    public static function load()
    {
        // Só carrega as variáveis uma única vez
        if (empty(self::$settings)) {
            $project_root = __DIR__ . '/../../';
            $dotenv = Dotenv::createImmutable($project_root);
            
            // O método safeLoad() carrega as variáveis e as retorna como um array
            // Ele é seguro pois não quebra o site se o .env não existir
            self::$settings = $dotenv->load();
        }
    }

    public static function get($key, $default = null)
    {
        // Primeiro, tenta obter da forma que acabamos de carregar
        if (!empty(self::$settings[$key])) {
            return self::$settings[$key];
        }

        // Se falhar, tenta o getenv() como um fallback
        $value = getenv($key);

        return $value === false ? $default : $value;
    }
}