<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flarum\Http\UrlGenerator;

class UploadSlideImageController implements RequestHandlerInterface
{
    public function __construct(
        protected FilesystemFactory $filesystem,
        protected UrlGenerator $url
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['image'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return new JsonResponse([
                'errors' => [['detail' => 'No valid file uploaded']]
            ], 422);
        }

        // MIME check
        $mime = $file->getClientMediaType() ?: '';
        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ];

        if (!in_array($mime, $allowed, true)) {
            return new JsonResponse([
                'errors' => [['detail' => 'Unsupported image type']]
            ], 422);
        }

        // Size limit (5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            return new JsonResponse([
                'errors' => [['detail' => 'File too large (max 5MB)']]
            ], 422);
        }

        // Extension
        $ext = pathinfo($file->getClientFilename() ?: 'image', PATHINFO_EXTENSION);
        $ext = $ext ? strtolower($ext) : 'png';

        // Safe filename
        $filename = 'magicslider/' . bin2hex(random_bytes(16)) . '.' . $ext;

        // Save file
        $stream = $file->getStream();
        $stream->rewind();

        $disk = $this->filesystem->disk('flarum-assets');
        $disk->put($filename, $stream->getContents());

        // Generate URL
        $url = rtrim($this->url->to('forum')->base(), '/') . '/assets/' . $filename;

        return new JsonResponse([
            'data' => [
                'url' => $url,
            ],
        ]);
    }
}