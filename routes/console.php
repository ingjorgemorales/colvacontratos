<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('app:about', function () {
    $this->info('ColvaContratos Laravel 13 migration scaffold');
});
