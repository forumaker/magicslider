<?php

namespace forumaker\MagicSlider\Frontend;

use Flarum\Frontend\Document;
use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Preloads the first slide's image, but only on routes IndexPage (and thus
 * the slider, which overrides WelcomeHero) can actually render on —
 * otherwise the browser preloads an image nothing on the page uses, which
 * Chrome flags as a wasted preload a few seconds after load.
 *
 * 'default' and 'index' are both the all-discussions listing (root '/'
 * resolves to whichever named route the admin's "default route" setting
 * points at, normally 'index' itself, hence both); 'tag' only when the
 * "hide on tag pages" setting isn't itself hiding the slider there anyway.
 */
class PreloadSlideImage
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected LoggerInterface $logger
    ) {
    }

    public function __invoke(Document $document, ServerRequestInterface $request): void
    {
        $sliderRoutes = ['default', 'index'];
        if (!(bool) $this->settings->get('forumaker-magicslider.hide_on_tag_pages', false)) {
            $sliderRoutes[] = 'tag';
        }

        if (!in_array($request->getAttribute('routeName'), $sliderRoutes, true)) {
            return;
        }

        $raw = $this->settings->get('forumaker-magicslider.slides', '[]');

        try {
            $slides = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->logger->warning('[forumaker-magicslider] Failed to parse slides JSON: ' . $e->getMessage());
            return;
        }

        if (!is_array($slides)) {
            return;
        }

        foreach ($slides as $slide) {
            $image = trim((string) ($slide['image'] ?? ''));

            if ($image !== '') {
                $document->preloads[] = [
                    'href' => $image,
                    'as' => 'image',
                    'fetchpriority' => 'high',
                ];

                return;
            }
        }
    }
}
