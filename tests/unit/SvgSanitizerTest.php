<?php

declare(strict_types=1);

namespace forumaker\MagicSlider\Tests\unit;

use forumaker\MagicSlider\Support\SvgSanitizer;
use PHPUnit\Framework\TestCase;

class SvgSanitizerTest extends TestCase
{
    private SvgSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new SvgSanitizer();
    }

    public function test_strips_script_elements(): void
    {
        $out = $this->sanitizer->sanitize('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect/></svg>');

        $this->assertStringNotContainsString('<script', $out);
        $this->assertStringNotContainsString('alert', $out);
        $this->assertStringContainsString('<rect', $out);
    }

    public function test_strips_style_and_foreignobject_elements(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<style>rect{fill:red}</style>'
            . '<foreignObject><body xmlns="http://www.w3.org/1999/xhtml">hi</body></foreignObject>'
            . '<rect/>'
            . '</svg>'
        );

        $this->assertStringNotContainsString('<style', $out);
        $this->assertStringNotContainsString('foreignObject', $out);
        $this->assertStringContainsString('<rect', $out);
    }

    public function test_strips_event_handler_attributes(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect onload="alert(1)" onclick="alert(2)" width="1"/></svg>'
        );

        $this->assertStringNotContainsString('onload', $out);
        $this->assertStringNotContainsString('onclick', $out);
        $this->assertStringContainsString('width="1"', $out);
    }

    public function test_strips_javascript_href(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><rect/></a></svg>'
        );

        $this->assertStringNotContainsString('javascript:', $out);
    }

    public function test_strips_javascript_xlink_href(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
            . '<use xlink:href="javascript:alert(1)"/>'
            . '</svg>'
        );

        $this->assertStringNotContainsString('javascript:', $out);
    }

    /**
     * The actual gap this class exists to close: a nested SVG (its own
     * <script>/onload) smuggled in through a data: URI, which a naive
     * javascript:-only filter would let straight through.
     */
    public function test_strips_unsafe_data_uri_pointing_to_nested_svg(): void
    {
        $nestedSvg = '<svg onload="alert(1)"/>';
        $dataUri = 'data:image/svg+xml;base64,' . base64_encode($nestedSvg);

        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="' . $dataUri . '"/></svg>'
        );

        $this->assertStringNotContainsString('data:', $out);
    }

    public function test_strips_data_uri_with_no_declared_type(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="data:,whatever"/></svg>'
        );

        $this->assertStringNotContainsString('data:', $out);
    }

    /** Plain raster data: URIs have no script capability and are a normal, legitimate way to embed a bitmap. */
    public function test_keeps_safe_raster_data_uri(): void
    {
        $dataUri = 'data:image/png;base64,iVBORw0KGgo=';

        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg"><image href="' . $dataUri . '"/></svg>'
        );

        $this->assertStringContainsString($dataUri, $out);
    }

    public function test_leaves_ordinary_markup_untouched(): void
    {
        $out = $this->sanitizer->sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="red"/></svg>'
        );

        $this->assertStringContainsString('<circle', $out);
        $this->assertStringContainsString('fill="red"', $out);
    }
}
