<?php

declare(strict_types=1);

namespace forumaker\MagicSlider\Tests\integration\api;

use Flarum\Testing\integration\TestCase;

class RecordSlideClickTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->extension('forumaker-magicslider');
    }

    public function test_a_guest_can_record_a_click_and_it_creates_a_row(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/forumaker/magicslider/click', [
                'json' => ['id' => 'slide-1'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $row = $this->database()->table('magicslider_slide_clicks')->where('slide_id', 'slide-1')->first();
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->clicks);
    }

    public function test_repeated_clicks_on_the_same_slide_accumulate_instead_of_overwriting(): void
    {
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-2']]));
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-2']]));
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-2']]));

        $row = $this->database()->table('magicslider_slide_clicks')->where('slide_id', 'slide-2')->first();
        $this->assertSame(3, (int) $row->clicks);
    }

    public function test_clicks_on_different_slides_are_tracked_independently(): void
    {
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-a']]));
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-a']]));
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-b']]));

        $this->assertSame(2, (int) $this->database()->table('magicslider_slide_clicks')->where('slide_id', 'slide-a')->value('clicks'));
        $this->assertSame(1, (int) $this->database()->table('magicslider_slide_clicks')->where('slide_id', 'slide-b')->value('clicks'));
    }

    /** No id, no row — but still a clean 204, since the frontend fires this best-effort and never checks the response. */
    public function test_a_missing_id_is_a_no_op_not_an_error(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/forumaker/magicslider/click', ['json' => []])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame(0, $this->database()->table('magicslider_slide_clicks')->count());
    }

    public function test_admin_can_read_back_recorded_clicks(): void
    {
        $this->send($this->request('POST', '/api/forumaker/magicslider/click', ['json' => ['id' => 'slide-x']]));

        $response = $this->send(
            $this->request('GET', '/api/forumaker/magicslider/clicks', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame(1, $body['data']['slide-x'] ?? null);
    }

    public function test_a_guest_cannot_read_clicks(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/forumaker/magicslider/clicks')
        );

        $this->assertEquals(403, $response->getStatusCode());
    }
}
