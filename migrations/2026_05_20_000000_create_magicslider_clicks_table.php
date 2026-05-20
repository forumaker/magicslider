<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('magicslider_clicks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slide_image', 500);
            $table->timestamp('clicked_at')->useCurrent();
            $table->index('slide_image');
        });
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('magicslider_clicks');
    },
];
