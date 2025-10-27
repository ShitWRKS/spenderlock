<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Parallax\FilamentComments\Models\FilamentComment;
use App\Listeners\PopulateCommentMetadataOnCreate;

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
        Passport::enableImplicitGrant();

        FilamentComment::created(function (FilamentComment $comment) {
            (new PopulateCommentMetadataOnCreate())->handle($comment);
        });
    }
}
