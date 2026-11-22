<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('service-desk:stats', function () {
    $this->info('Tickets: '.\App\Models\Ticket::count());
});

// service-desk:create-admin is implemented by App\Console\Commands\CreateAdmin.
// Do not register a second closure with the same signature here.
