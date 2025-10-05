<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'user'], function () {
    include __DIR__ . '/api/v1.php';
});
