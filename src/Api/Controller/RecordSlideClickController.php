<?php

namespace forumaker\MagicSlider\Api\Controller;

use Illuminate\Database\ConnectionInterface;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Increments a slide's click count — fired by the forum-side slider
 * (MagicSlider.tsx) whenever a visitor clicks through a slide's link.
 * Deliberately public (no admin/auth check): this is a visitor-facing
 * analytics counter, the same trust level as a page view.
 *
 * Uses a real DB row + an atomic upsert rather than folding a `clicks` count
 * into the slides JSON settings blob: two visitors clicking at the same
 * moment would otherwise both read the same JSON, increment their own copy,
 * and whichever write lands second silently overwrites (and loses) the
 * first — a real scenario for a click counter, unlike almost everything
 * else this settings blob holds, which only an admin ever edits.
 */
class RecordSlideClickController implements RequestHandlerInterface
{
    private const MAX_ID_LENGTH = 40;

    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $id = trim((string) ($body['id'] ?? ''));

        if ($id !== '' && mb_strlen($id) <= self::MAX_ID_LENGTH) {
            $this->db->table('magicslider_slide_clicks')->upsert(
                [['slide_id' => $id, 'clicks' => 1]],
                ['slide_id'],
                ['clicks' => $this->db->raw('clicks + 1')]
            );
        }

        return new EmptyResponse(204);
    }
}
