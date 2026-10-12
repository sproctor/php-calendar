<?php

namespace App\Tests\Service;

use App\Service\AppVersion;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class AppVersionTest extends TestCase
{
    private const HASH = '8e6a918c0ffee0000000000000000000000000ab';

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/phpc-version-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->dir);
    }

    private function write(string $path, string $content): void
    {
        (new Filesystem())->dumpFile("$this->dir/$path", $content);
    }

    private function version(): string
    {
        return (new AppVersion($this->dir))->get();
    }

    public function testReleaseFileWins(): void
    {
        $this->write('VERSION', "3.0.0\n");
        $this->write('.git/HEAD', "ref: refs/heads/main\n");
        $this->assertSame('3.0.0', $this->version());
    }

    public function testBranchAndCommit(): void
    {
        $this->write('.git/HEAD', "ref: refs/heads/main\n");
        $this->write('.git/refs/heads/main', self::HASH . "\n");
        $this->assertSame('main@8e6a918', $this->version());
    }

    public function testPackedRef(): void
    {
        $this->write('.git/HEAD', "ref: refs/heads/v2.0\n");
        $this->write('.git/packed-refs', "# pack-refs with: peeled\n" . self::HASH . " refs/heads/v2.0\n");
        $this->assertSame('v2.0@8e6a918', $this->version());
    }

    public function testDetachedHead(): void
    {
        $this->write('.git/HEAD', self::HASH . "\n");
        $this->assertSame('8e6a918', $this->version());
    }

    public function testWorktree(): void
    {
        $this->write('.git', "gitdir: repo/.git/worktrees/feature\n");
        $this->write('repo/.git/worktrees/feature/HEAD', "ref: refs/heads/feature\n");
        $this->write('repo/.git/worktrees/feature/commondir', "../..\n");
        $this->write('repo/.git/refs/heads/feature', self::HASH . "\n");
        $this->assertSame('feature@8e6a918', $this->version());
    }

    public function testNeitherIsUnknown(): void
    {
        $this->assertSame('unknown', $this->version());
    }
}
