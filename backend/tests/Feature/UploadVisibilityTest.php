<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

it('serves editor media but hides legacy file manager caches and quarantine', function () {
    $root = storage_path('framework/testing/upload-visibility-'.Str::random(10));
    config()->set('site.upload_path', $root);

    try {
        foreach (['editor', '.tmb', '.quarantine', '.trash'] as $folder) {
            $directory = $root.'/filemanager/'.$folder;
            File::ensureDirectoryExists($directory);
            File::put($directory.'/sample.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+nmZkAAAAASUVORK5CYII='));
        }

        $this->get('/upload/filemanager/editor/sample.png')->assertOk()->assertHeader('Content-Type', 'image/png');

        foreach (['.tmb', '.quarantine', '.trash'] as $folder) {
            $this->get('/upload/filemanager/'.$folder.'/sample.png')->assertNotFound();
        }
    } finally {
        File::deleteDirectory($root);
    }
});
