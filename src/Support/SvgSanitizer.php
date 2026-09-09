<?php

namespace forumaker\MagicSlider\Support;

/**
 * Strips active-content vectors from an uploaded SVG before it's written to
 * disk: <script>/<style>/<foreignObject> elements, on* event handler
 * attributes, and javascript:/unsafe data: URIs on href/src (including the
 * namespaced xlink:href form).
 *
 * Pulled out of UploadSlideImageController as its own class specifically so
 * it can be unit tested without going through the HTTP/upload machinery —
 * see tests/unit/SvgSanitizerTest.php.
 */
class SvgSanitizer
{
    /**
     * data: URIs whose declared MIME type is one of these plain raster
     * formats stay allowed — no script capability, and a normal, safe way
     * to embed a bitmap inside an SVG. Anything else data: (svg+xml, html,
     * or no declared type at all) is stripped the same as javascript: is,
     * since it can point to a second, unsanitized SVG nested inside this
     * one (complete with its own <script>/onload).
     */
    private const SAFE_DATA_URI = '/^data:image\/(png|jpe?g|gif|webp);/i';

    public function sanitize(string $svg): string
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        foreach ($xpath->query('//*[local-name()="script"]') as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*[local-name()="style"]') as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*[local-name()="foreignObject"]') as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//@*[starts-with(local-name(), "on")]') as $attr) {
            $attr->ownerElement?->removeAttributeNode($attr);
        }

        // local-name() (not a plain @href match) is what makes this also
        // catch the namespaced xlink:href form in one pass.
        foreach (['href', 'src'] as $localName) {
            foreach ($xpath->query("//@*[local-name()=\"$localName\"]") as $attr) {
                $value = trim($attr->value);

                if (str_starts_with($value, 'javascript:')
                    || (str_starts_with($value, 'data:') && !preg_match(self::SAFE_DATA_URI, $value))
                ) {
                    $attr->ownerElement?->removeAttributeNode($attr);
                }
            }
        }

        return $doc->saveXML() ?: $svg;
    }
}
