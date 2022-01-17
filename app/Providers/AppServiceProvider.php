<?php

namespace App\Providers;

use TCG\Voyager\Facades\Voyager;
use App\Admin\FormFields\AddressFormField;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Voyager::addFormField(AddressFormField::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \URL::forceScheme('https'); // Force https
        Paginator::useBootstrap();
    }
}
