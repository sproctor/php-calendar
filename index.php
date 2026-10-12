<?php

// The front controller for when the whole folder is uploaded into the web server's folder; see
// .htaccess. It's the same as public/index.php.

use App\Kernel;

require __DIR__.'/config/requirements.php';
require_once __DIR__.'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
