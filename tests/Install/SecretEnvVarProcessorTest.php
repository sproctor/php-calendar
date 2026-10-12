<?php

namespace App\Tests\Install;

use App\Install\SecretEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class SecretEnvVarProcessorTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . '/phpc-secret-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    private function resolve(string $value): string
    {
        return (new SecretEnvVarProcessor($this->file))->getEnv('app_secret', 'APP_SECRET', fn() => $value);
    }

    public function testUsesAppSecretWhenSet(): void
    {
        $this->assertSame('configured', $this->resolve('configured'));
        $this->assertFileDoesNotExist($this->file);
    }

    public function testGeneratesAndKeepsOneOtherwise(): void
    {
        $first = $this->resolve('');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $first);
        $this->assertSame($first, $this->resolve(''));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($this->file)), -4));
    }
}
