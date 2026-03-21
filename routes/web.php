<?php

use Illuminate\Support\Facades\Route;

Route::get('/cv', function () {
    return view('cv');
});
