<?php

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable(
    'magicslider_slide_clicks',
    function (Blueprint $table) {
        // Slide id, not an autoincrementing row id — a slide's own
        // client-generated id (see MagicSliderSettingsPage.tsx's genId()) is
        // the primary key here, so RecordSlideClickController can do a
        // single atomic upsert-and-increment per click instead of a
        // read-modify-write race on the settings JSON blob.
        $table->string('slide_id', 40)->primary();
        $table->unsignedInteger('clicks')->default(0);
    }
);
