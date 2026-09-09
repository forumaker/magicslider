<?php

namespace forumaker\MagicSlider\Support;

/**
 * The one place that knows what an UploadSlideImageController-generated
 * filename/URL looks like — used to both generate one and, later, to check
 * whether some arbitrary URL a client sent us is actually one of ours
 * before DeleteSlideImageController is allowed to delete anything named by
 * it. Keeping generation and validation next to each other in one class is
 * what guarantees they can't quietly drift apart.
 */
class SlideAssetPath
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'png', 'webp', 'gif', 'svg'];

    /** Relative path (under the flarum-assets disk) for a newly uploaded slide image. */
    public static function generate(string $extension): string
    {
        return 'magicslider/' . bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Extracts the relative asset-disk path from a URL, but ONLY if it
     * matches exactly the <32 hex chars>.<ext> shape generate() itself
     * produces — never anything else, regardless of what the URL says.
     * This is what keeps a delete request from being usable to remove
     * arbitrary files elsewhere on the assets disk.
     */
    public static function parseOwn(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extensions = implode('|', self::ALLOWED_EXTENSIONS);

        if (!preg_match("#/assets/(magicslider/[a-f0-9]{32}\\.(?:$extensions))$#", $path, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
