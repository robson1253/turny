<?php

// Arquivo: config/database.php

use App\Core\Config;

return [
    'host'      => Config::get('DB_HOST'),
    'port'      => Config::get('DB_PORT'),
    'database'  => Config::get('DB_DATABASE'),
    'username'  => Config::get('DB_USERNAME'),
    'password'  => Config::get('DB_PASSWORD'),
    'charset'   => 'utf8mb4'
];