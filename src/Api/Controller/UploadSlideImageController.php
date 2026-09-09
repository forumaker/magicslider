<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use forumaker\MagicSlider\Support\SlideAssetPath;
use forumaker\MagicSlider\Support\SvgSanitizer;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UploadSlideImageController implements RequestHandlerInterface
{
    private const ALLOWED_MIMES = [
        'image/jpeg'    => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/gif'     => 'gif',
        'image/svg+xml' => 'svg',
    ];

    private const MAX_SIZE = 5 * 1024 * 1024;

    public function __construct(
        protected FilesystemFactory $filesystem,
        protected UrlGenerator $url,
        protected SvgSanitizer $svgSanitizer
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['image'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return new JsonResponse(['errors' => [['detail' => 'No valid file uploaded']]], 422);
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return new JsonResponse(['errors' => [['detail' => 'File too large (max 5MB)']]], 422);
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = $stream->getContents();

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (!isset(self::ALLOWED_MIMES[$mime])) {
            return new JsonResponse(['errors' => [['detail' => 'Unsupported image type']]], 422);
        }

        $ext = self::ALLOWED_MIMES[$mime];

        if ($mime === 'image/svg+xml') {
            $contents = $this->svgSanitizer->sanitize($contents);
        }

        $filename = SlideAssetPath::generate($ext);

        $this->filesystem->disk('flarum-assets')->put($filename, $contents);

        $url = rtrim($this->url->to('forum')->base(), '/') . '/assets/' . $filename;

        return new JsonResponse(['data' => ['url' => $url]]);
    }
}
