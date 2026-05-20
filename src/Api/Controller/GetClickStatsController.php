<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetClickStatsController implements RequestHandlerInterface
{
    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $rows = $this->db->table('magicslider_clicks')
            ->selectRaw('slide_image, COUNT(*) as clicks')
            ->groupBy('slide_image')
            ->orderByDesc('clicks')
            ->get();

        return new JsonResponse([
            'data' => $rows->map(fn($r) => [
                'image'  => $r->slide_image,
                'clicks' => (int) $r->clicks,
            ])->values()->all(),
        ]);
    }
}
