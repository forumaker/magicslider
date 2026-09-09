<?php

declare(strict_types=1);

namespace forumaker\MagicSlider\Tests\unit;

use forumaker\MagicSlider\Support\SlideAssetPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SlideAssetPathTest extends TestCase
{
    public function test_generate_produces_a_path_parseOwn_accepts(): void
    {
        $path = SlideAssetPath::generate('png');

        $this->assertMatchesRegularExpression('#^magicslider/[a-f0-9]{32}\.png$#', $path);
        $this->assertSame($path, SlideAssetPath::parseOwn('https://example.com/assets/' . $path));
    }

    public function test_generate_is_unique_per_call(): void
    {
        $this->assertNotSame(SlideAssetPath::generate('png'), SlideAssetPath::generate('png'));
    }

    #[DataProvider('ownUrlProvider')]
    public function test_parseOwn_accepts_own_urls(string $url, string $expected): void
    {
        $this->assertSame($expected, SlideAssetPath::parseOwn($url));
    }

    public static function ownUrlProvider(): array
    {
        $hex = str_repeat('a', 32);

        return [
            'plain domain'      => ["https://questpost.ru/assets/magicslider/$hex.svg", "magicslider/$hex.svg"],
            'double-slash base' => ["https://questpost.ru//assets/magicslider/$hex.png", "magicslider/$hex.png"],
            'query string'      => ["https://questpost.ru/assets/magicslider/$hex.jpg?v=1", "magicslider/$hex.jpg"],
        ];
    }

    /**
     * The whole reason this class exists rather than trusting the URL a
     * client sends outright: none of these may resolve to a real filename,
     * however they're phrased, or a delete request becomes a way to remove
     * arbitrary files on the assets disk.
     *
     */
    #[DataProvider('foreignUrlProvider')]
    public function test_parseOwn_rejects_anything_not_its_own_upload(string $url): void
    {
        $this->assertNull(SlideAssetPath::parseOwn($url));
    }

    public static function foreignUrlProvider(): array
    {
        $hex = str_repeat('a', 32);

        return [
            'wrong extension'          => ["https://questpost.ru/assets/magicslider/$hex.php"],
            'short id'                 => ['https://questpost.ru/assets/magicslider/abc.png'],
            'path traversal'           => ['https://questpost.ru/assets/magicslider/../../config.php'],
            'different assets subpath' => ["https://questpost.ru/assets/avatars/$hex.png"],
            'not under assets at all'  => ["https://questpost.ru/uploads/magicslider/$hex.png"],
            'empty string'             => [''],
            'not a url at all'         => ['not a url'],
        ];
    }
}
