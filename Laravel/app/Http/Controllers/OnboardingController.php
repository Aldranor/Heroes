<?php

namespace App\Http\Controllers;

use App\Models\Avatar\Avatar;
use App\Models\Companions\Companion;
use App\Services\RewardService;
use App\Support\AvatarAssetCatalog;
use App\Support\AvatarSvgRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class OnboardingController extends Controller
{
    private const STARTER_COMPANION_SLUGS = [
        'stratege',
        'observateur',
        'eclaireur',
    ];

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->avatar()->exists()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.avatar');
    }

    public function hairVariants(string $model, AvatarAssetCatalog $avatarAssets, AvatarSvgRenderer $avatarRenderer): JsonResponse
    {
        $hairModel = $avatarAssets->hairModels()[$model] ?? null;

        abort_if($hairModel === null, 404);

        return response()
            ->json($this->hairVariantPayload($hairModel, $avatarRenderer))
            ->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'private, max-age=3600');
    }

    public function store(Request $request, RewardService $rewardService, AvatarAssetCatalog $avatarAssets): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar()->exists()) {
            return redirect()->route('dashboard');
        }

        $validated = $request->validate([
            'nickname' => ['required', 'string', 'min:3', 'max:24'],
            'body_asset' => ['required', 'string', 'in:'.implode(',', array_keys($avatarAssets->bodyAssets()))],
            'hair_asset' => ['required', 'string', 'in:'.implode(',', array_keys($avatarAssets->hairAssets()))],
            'starter_choice' => ['required', 'string', 'in:'.implode(',', self::STARTER_COMPANION_SLUGS)],
        ]);

        DB::transaction(function () use ($user, $validated, $rewardService, $avatarAssets) {
            $classConfig = config("avatar.class_outfits.{$validated['starter_choice']}");
            $outfitPreset = data_get($classConfig, 'preset', data_get($classConfig, 'outfit'));
            $resolvedPreset = $avatarAssets->resolveOutfitPreset($outfitPreset, [
                'body' => $validated['body_asset'],
            ]);
            $bodyConfig = $avatarAssets->bodyAsset($validated['body_asset']) ?? [];
            $hairConfig = $avatarAssets->hairAsset($validated['hair_asset']) ?? [];

            Avatar::query()->create([
                'user_id' => $user->id,
                'nickname' => $validated['nickname'],
                'style' => $outfitPreset,
                'colors' => [
                    'hair' => $hairConfig['color'] ?? config('avatar.defaults.hair_color'),
                    'skin' => $bodyConfig['skin'] ?? config('avatar.defaults.skin'),
                    'accent' => $classConfig['accent'],
                ],
                'equipped_items' => [
                    'body' => $validated['body_asset'],
                    'hair' => $validated['hair_asset'],
                    'outfit_preset' => $outfitPreset,
                    'equipment' => $resolvedPreset['slots'] ?? [],
                    'starter_class' => $validated['starter_choice'],
                ],
            ]);

            $starters = Companion::query()
                ->whereIn('slug', self::STARTER_COMPANION_SLUGS)
                ->get()
                ->keyBy('slug');

            $missingStarterSlugs = collect(self::STARTER_COMPANION_SLUGS)
                ->diff($starters->keys());

            if ($missingStarterSlugs->isNotEmpty()) {
                throw new RuntimeException(
                    'Missing starter companions: '.$missingStarterSlugs->implode(', '),
                );
            }

            foreach (self::STARTER_COMPANION_SLUGS as $slug) {
                $rewardService->grantCompanion($user, $starters[$slug], [
                    'is_favorite' => $slug === $validated['starter_choice'],
                ]);
            }
        });

        return redirect()->route('dashboard');
    }

    private function hairVariantPayload(array $model, AvatarSvgRenderer $avatarRenderer): array
    {
        $defaultHairColor = config('avatar.defaults.hair_color');

        return [
            'profiles' => collect($model['profiles'] ?? [])->mapWithKeys(function (array $group, string $profile) use ($avatarRenderer, $defaultHairColor): array {
                return [
                    $profile => collect($group['variants'] ?? [])->map(function (array $variant) use ($avatarRenderer, $defaultHairColor): array {
                        return [
                            'key' => $variant['key'],
                            'label' => $variant['variant_label'] ?? $variant['label'],
                            'hair_color' => $variant['color'] ?? $defaultHairColor,
                            'src' => $avatarRenderer->sheetAssetSrc($variant),
                        ];
                    })->values()->all(),
                ];
            })->all(),
        ];
    }
}
