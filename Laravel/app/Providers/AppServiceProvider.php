<?php

namespace App\Providers;

use App\Models\Avatar\Avatar;
use App\Models\Avatar\AvatarItem;
use App\Models\Commerce\Equipment;
use App\Models\Companions\Companion;
use App\Models\Companions\UserCompanion;
use App\Models\Creatures\Creature;
use App\Models\Creatures\UserCreature;
use App\Support\AvatarAssetCatalog;
use App\Support\AvatarAssetLocator;
use App\Support\AvatarSvgRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AvatarAssetLocator::class);
        $this->app->singleton(AvatarAssetCatalog::class);
        $this->app->singleton(AvatarSvgRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Relation::enforceMorphMap([
            'avatar' => Avatar::class,
            'avatar_item' => AvatarItem::class,
            'companion' => Companion::class,
            'creature' => Creature::class,
            'equipment' => Equipment::class,
            'user_companion' => UserCompanion::class,
            'user_creature' => UserCreature::class,
        ]);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
