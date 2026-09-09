<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use forumaker\MagicSlider\Support\SlideAssetPath;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Deletes a slide image previously uploaded through UploadSlideImageController
 * — called when a slide's image is replaced with a new upload, or the slide
 * itself is removed, so replaced/removed images don't just pile up forever
 * under assets/magicslider/.
 *
 * Always 204s, even when there was nothing to delete (unrecognized URL,
 * already-gone file) — this only ever runs as best-effort cleanup after
 * something else already succeeded client-side, so there's nothing for the
 * caller to react to either way.
 */
class DeleteSlideImageController implements RequestHandlerInterface
{
    public function __construct(
        protected FilesystemFactory $filesystem
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $body = (array) $request->getParsedBody();
        $filename = SlideAssetPath::parseOwn((string) ($body['url'] ?? ''));

        if ($filename !== null) {
            $disk = $this->filesystem->disk('flarum-assets');

            if ($disk->exists($filename)) {
                $disk->delete($filename);
            }
        }

        return new EmptyResponse(204);
    }
}
