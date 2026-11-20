<?php

namespace App;

use App\Install\SettingDefaults;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        // Every entry point (web, console, tests) creates the kernel after loading the settings
        SettingDefaults::apply(\dirname(__DIR__));
        parent::__construct($environment, $debug);
    }
}
