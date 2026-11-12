<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about-project', function () {
    $this->comment('Task Management API - Laravel 12, JSON file storage.');
})->purpose('Display information about this project');