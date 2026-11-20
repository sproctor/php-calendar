<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/*
 * PHP 8.4 has lazy objects built in, and Doctrine deprecates its generated proxy classes there (and even
 * setting their options). PHP 8.3, which we still support, only has the generated proxies.
 */
return static function (ContainerConfigurator $container): void {
    if (\PHP_VERSION_ID >= 80400) {
        $container->extension('doctrine', ['orm' => ['enable_native_lazy_objects' => true]]);

        return;
    }
    $container->extension('doctrine', ['orm' => [
        'enable_lazy_ghost_objects' => true,
        // Production builds the proxy classes once, with the cache, in the default proxy_dir
        'auto_generate_proxy_classes' => $container->env() !== 'prod',
    ]]);
};
