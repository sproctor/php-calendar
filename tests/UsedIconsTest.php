<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Releases only include the icons bin/used-icons finds, so it must find all of them
 */
class UsedIconsTest extends TestCase
{
    public function testEveryIconInUseIsFoundAndExists(): void
    {
        $root = dirname(__DIR__);
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg("$root/bin/used-icons") . ' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));

        $this->assertContains('gear', $output);
        // From menu_item() and from a variable holding a full name
        $this->assertContains('calendar3', $output);
        $this->assertContains('sun-fill', $output);
        foreach ($output as $icon) {
            $this->assertFileExists("$root/vendor/twbs/bootstrap-icons/icons/$icon.svg");
        }
    }
}
