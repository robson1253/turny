<?php

// Arquivo: config/mail.php

use App\Core\Config;

return [
    'username' => Config::get('EMAIL_USERNAME'),
    'password' => Config::get('EMAIL_PASSWORD'),
];