<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Languages;

class LocaleService {
    private ?array $mappings = null;

    public function __construct(
        private KernelInterface $kernel,
    )
    {
    }

    function getLocaleMappings(): array
    {
        if ($this->mappings === null) {
            $this->mappings = [];
            $finder = new Finder();

            foreach ($finder->name('*.yaml')->in($this->kernel->getProjectDir() . '/translations')->files() as $file) {
                preg_match('/[^.]\.(.+)/', $file->getFilenameWithoutExtension(), $matches);
                $code = $matches[1];
                $lang = Languages::getName($code, $code);
                $this->mappings[$code] = $lang;
            }
            ksort($this->mappings);
        }

        return $this->mappings;
    }
}