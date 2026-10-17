<?php

namespace App\Providers;

use App\Models\AgendaItem;
use App\Models\ConferenceSession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Relation::enforceMorphMap([
            'session' => ConferenceSession::class,
            'item' => AgendaItem::class,
        ]);

        JsonResource::withoutWrapping();
    }
}
