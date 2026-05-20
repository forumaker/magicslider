<?php

namespace forumaker\MagicSlider\Api\Controller;

use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordClickController implements RequestHandlerInterface
{
    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $image = trim((string) ($body['image'] ?? ''));

        if ($image === '') {
            return new JsonResponse([], 422);
        }

        $this->db->table('magicslider_clicks')->insert([
            'slide_image' => mb_substr($image, 0, 500),
        ]);

        return new JsonResponse([]);
    }
}
