<?php

uses()->beforeEach(function() {
    $wpPath = getenv('USERPROFILE') . getenv('APP_DIR');
    putenv("WP_PATH=$wpPath");
})->in('Integration');
