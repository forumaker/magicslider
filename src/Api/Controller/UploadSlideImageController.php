<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
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
        protected UrlGenerator $url
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
            $contents = $this->sanitizeSvg($contents);
        }

        $filename = 'magicslider/' . bin2hex(random_bytes(16)) . '.' . $ext;

        $this->filesystem->disk('flarum-assets')->put($filename, $contents);

        $url = rtrim($this->url->to('forum')->base(), '/') . '/assets/' . $filename;

        return new JsonResponse(['data' => ['url' => $url]]);
    }

    private function sanitizeSvg(string $svg): string
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

        foreach ($xpath->query('//@href[starts-with(normalize-space(.), "javascript:")]') as $attr) {
            $attr->ownerElement?->removeAttributeNode($attr);
        }

        $xpath->registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        foreach ($xpath->query('//@xlink:href[starts-with(normalize-space(.), "javascript:")]') as $attr) {
            $attr->ownerElement?->removeAttributeNode($attr);
        }

        return $doc->saveXML() ?: $svg;
    }
}
