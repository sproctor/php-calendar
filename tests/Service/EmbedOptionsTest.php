<?php

namespace App\Tests\Service;

use App\Service\EmbedOptions;
use App\Theme\Theme;
use App\Theme\ThemeRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmbedOptionsTest extends TestCase
{
    /**
     * @param array<string, string> $query
     */
    private function options(array $query): EmbedOptions
    {
        $theme = new Theme('custom:club', 'Club', Theme::GROUP_CUSTOM, 'themes/club.css', null, false);
        $themes = $this->createStub(ThemeRegistry::class);
        $themes->method('get')->willReturnCallback(fn(?string $id) => $id === 'custom:club' ? $theme : null);

        return EmbedOptions::fromQuery($query, $themes);
    }

    public function testDefaults(): void
    {
        $options = $this->options([]);
        $this->assertTrue($options->header);
        $this->assertNull($options->background);
        $this->assertNull($options->accent);
        $this->assertNull($options->font);
        $this->assertNull($options->mode);
        $this->assertNull($options->theme);
        $this->assertSame('', $options->css());
    }

    public function testValidValues(): void
    {
        $options = $this->options(['header' => '0', 'bg' => 'F8F4E8', 'accent' => '#0A6', 'font' => "'Fira Sans', serif",
            'mode' => 'auto', 'theme' => 'custom:club']);
        $this->assertFalse($options->header);
        $this->assertSame('#f8f4e8', $options->background);
        $this->assertSame('#00aa66', $options->accent);
        $this->assertSame("'Fira Sans', serif", $options->font);
        $this->assertSame('auto', $options->mode);
        $this->assertSame('custom:club', $options->theme?->id);
        $this->assertStringContainsString('--bs-body-bg-rgb: 248, 244, 232', $options->css());
    }

    /**
     * @return iterable<array{string, string}>
     */
    public static function invalidValues(): iterable
    {
        yield ['bg', 'red'];
        yield ['bg', '#12345'];
        yield ['bg', '#fff;}body{display:none'];
        yield ['accent', 'transparent'];
        yield ['accent', 'rgb(0,0,0)'];
        yield ['font', 'Georgia; color: red'];
        yield ['font', '"Unclosed, serif'];
        yield ['font', '</style>'];
        yield ['font', 'url(x)'];
        yield ['font', str_repeat('a', 201)];
    }

    #[DataProvider('invalidValues')]
    public function testInvalidValuesAreIgnored(string $name, string $value): void
    {
        $this->assertSame('', $this->options([$name => $value])->css());
    }

    public function testButtonTextContrastsWithTheAccent(): void
    {
        $this->assertStringContainsString('--bs-btn-color: #000', $this->options(['accent' => '#ffd700'])->css());
        $this->assertStringContainsString('--bs-btn-color: #fff', $this->options(['accent' => '#003366'])->css());
    }

    public function testRgb(): void
    {
        $this->assertSame('0, 170, 102', EmbedOptions::rgb('#00aa66'));
    }
}
