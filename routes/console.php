<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Register command contoh untuk menampilkan quote inspirasi.
// Closure command contoh bawaan Laravel untuk menampilkan kutipan inspirasi.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

