<?php

namespace forumaker\MagicSlider;

use Flarum\Extend;
use Flarum\Settings\SettingsRepositoryInterface;
use forumaker\MagicSlider\Api\Controller\UploadSlideImageController;
use Psr\Log\LoggerInterface;

return [
    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\Frontend('forum'))
        ->css(__DIR__ . '/resources/less/forum.less')
        ->js(__DIR__ . '/js/dist/forum.js')
        ->preloads(function () {
            $raw = resolve(SettingsRepositoryInterface::class)->get('forumaker-magicslider.slides', '[]');

            try {
                $slides = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                resolve(LoggerInterface::class)->warning('[forumaker-magicslider] Failed to parse slides JSON: ' . $e->getMessage());
                return [];
            }

            if (!is_array($slides)) {
                return [];
            }

            foreach ($slides as $slide) {
                $image = trim((string) ($slide['image'] ?? ''));

                if ($image !== '') {
                    return [[
                        'href' => $image,
                        'as' => 'image',
                        'fetchpriority' => 'high',
                    ]];
                }
            }

            return [];
        }),

    (new Extend\Frontend('admin'))
        ->css(__DIR__ . '/resources/less/admin.less')
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Routes('api'))
        ->post('/forumaker/magicslider/upload', 'forumaker.magicslider.upload', UploadSlideImageController::class),

    (new Extend\Settings())
        ->default('forumaker-magicslider.slides', '[]')
        ->default('forumaker-magicslider.height_desktop', '250')
        ->default('forumaker-magicslider.height_mobile', '200')
        ->default('forumaker-magicslider.padding_desktop', '0')
        ->default('forumaker-magicslider.padding_mobile', '0')
        ->default('forumaker-magicslider.radius_desktop', '0')
        ->default('forumaker-magicslider.radius_mobile', '0')
        ->default('forumaker-magicslider.autoplay', '0')
        ->default('forumaker-magicslider.hide_on_tag_pages', '0')
        ->default('forumaker-magicslider.fit_to_layout', '0')
        ->default('forumaker-magicslider.disable_mobile', '0')
        ->default('forumaker-magicslider.disable_desktop', '0')

        ->serializeToForum('forumaker-magicslider.slides', 'forumaker-magicslider.slides', 'strval')
        ->serializeToForum('forumaker-magicslider.height_desktop', 'forumaker-magicslider.height_desktop', 'intval')
        ->serializeToForum('forumaker-magicslider.height_mobile', 'forumaker-magicslider.height_mobile', 'intval')
        ->serializeToForum('forumaker-magicslider.padding_desktop', 'forumaker-magicslider.padding_desktop', 'intval')
        ->serializeToForum('forumaker-magicslider.padding_mobile', 'forumaker-magicslider.padding_mobile', 'intval')
        ->serializeToForum('forumaker-magicslider.radius_desktop', 'forumaker-magicslider.radius_desktop', 'intval')
        ->serializeToForum('forumaker-magicslider.radius_mobile', 'forumaker-magicslider.radius_mobile', 'intval')
        ->serializeToForum('forumaker-magicslider.autoplay', 'forumaker-magicslider.autoplay', 'intval')
        ->serializeToForum('forumaker-magicslider.hide_on_tag_pages', 'forumaker-magicslider.hide_on_tag_pages', 'boolval')
        ->serializeToForum('forumaker-magicslider.fit_to_layout', 'forumaker-magicslider.fit_to_layout', 'boolval')
        ->serializeToForum('forumaker-magicslider.disable_mobile', 'forumaker-magicslider.disable_mobile', 'boolval')
        ->serializeToForum('forumaker-magicslider.disable_desktop', 'forumaker-magicslider.disable_desktop', 'boolval'),
];