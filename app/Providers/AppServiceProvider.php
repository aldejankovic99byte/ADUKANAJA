<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Zona waktu & bahasa Indonesia (tanggal tampil seperti "02 Sep 2026")
        date_default_timezone_set('Asia/Jakarta');
        Carbon::setLocale('id');

        // Tampilan pagination sendiri (tanpa Tailwind)
        Paginator::defaultView('vendor.pagination.adukanaja');
        Paginator::defaultSimpleView('vendor.pagination.adukanaja');
    }
}