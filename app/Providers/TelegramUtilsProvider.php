<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TelegramUtilsProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        foreach (glob(app_path() . '/Utils/*.php') as $helpersfilename)
        {
            require_once($helpersfilename);
        }
    }
}
