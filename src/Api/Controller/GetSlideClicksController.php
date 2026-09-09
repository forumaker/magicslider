<?php

namespace forumaker\MagicSlider\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Returns click counts for every slide that has ever recorded one, as a
 * flat { slideId: clicks } map — read by the admin settings page to show a
 * count next to each slide. Rows for slides since deleted or with a
 * regenerated id are simply never looked up again by the admin page; they
 * aren't cleaned up here, since that's a handful of small rows at most and
 * pruning them buys nothing an admin would notice.
 */
class GetSlideClicksController implements RequestHandlerInterface
{
    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $rows = $this->db->table('magicslider_slide_clicks')->select('slide_id', 'clicks')->get();

        $data = [];
        foreach ($rows as $row) {
            $data[$row->slide_id] = (int) $row->clicks;
        }

        return new JsonResponse(['data' => $data]);
    }
}
