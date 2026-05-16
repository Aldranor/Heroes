<?php

namespace App\Http\Controllers;

use App\Models\Battle\Battle;
use App\Models\World\AdventureNode;
use App\Services\BuildService;
use App\Services\CombatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use InvalidArgumentException;

class CombatController extends Controller
{
    public function prepare(Request $request, AdventureNode $adventureNode, BuildService $buildService): View
    {
        abort_unless(in_array($adventureNode->node_type, ['combat_normal', 'combat_elite', 'boss'], true), 404);

        return view('combat.prepare', $buildService->preparationData(
            $request->user(),
            $adventureNode,
        ));
    }

    public function store(Request $request, CombatService $combatService, BuildService $buildService): RedirectResponse
    {
        $validated = $request->validate([
            'adventure_node_id' => ['required', 'integer', 'exists:adventure_nodes,id'],
            'user_companion_ids' => ['nullable', 'array', 'max:2'],
            'user_companion_ids.*' => ['integer', 'exists:user_companions,id'],
            'hero_class' => ['required', 'string'],
            'hero_specialization' => ['required', 'string'],
            'hero_active_skills' => ['nullable', 'array', 'max:4'],
            'hero_active_skills.*' => ['string'],
            'hero_passive_skills' => ['nullable', 'array', 'max:3'],
            'hero_passive_skills.*' => ['string'],
            'hero_ultimate_skill' => ['nullable', 'string'],
            'companions' => ['nullable', 'array'],
            'companions.*.specialization_slug' => ['nullable', 'string'],
            'companions.*.active_skills' => ['nullable', 'array', 'max:4'],
            'companions.*.active_skills.*' => ['string'],
            'companions.*.passive_skills' => ['nullable', 'array', 'max:3'],
            'companions.*.passive_skills.*' => ['string'],
            'companions.*.ultimate_skill' => ['nullable', 'string'],
            'companions.*.position' => ['nullable', 'integer', 'between:2,3'],
        ]);

        try {
            $selectedCompanionIds = $buildService->savePreparation(
                $request->user(),
                $validated,
            );

            $battle = $combatService->startForNode(
                $request->user(),
                AdventureNode::query()->findOrFail((int) $validated['adventure_node_id']),
                $selectedCompanionIds,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['combat' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('combat.show', $battle);
    }

    public function show(Request $request, Battle $battle, CombatService $combatService): View
    {
        abort_unless($battle->user_id === $request->user()->id, 404);

        return view('combat.show', $combatService->viewData($battle));
    }

    public function action(Request $request, Battle $battle, CombatService $combatService): RedirectResponse|JsonResponse
    {
        abort_unless($battle->user_id === $request->user()->id, 404);

        $validator = Validator::make($request->all(), [
            'skill' => ['required', 'string'],
            'target' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first('skill') ?: $validator->errors()->first(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            $updatedBattle = $combatService->performPlayerAction(
                $battle,
                $request->user(),
                $validated['skill'],
                $validated['target'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return back()->withErrors(['combat' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->combatUiPayload($updatedBattle, $combatService));
        }

        return redirect()->route('combat.show', $battle);
    }

    protected function combatUiPayload(Battle $battle, CombatService $combatService): array
    {
        $viewData = $combatService->viewData($battle);

        return [
            'status' => $battle->status->value,
            'last_action' => $viewData['lastAction'] ?? null,
            'turn_actions' => $viewData['turnActions'] ?? [],
            'fragments' => [
                'badges' => view('combat.partials.status-badges', $viewData)->render(),
                'stage' => view('combat.partials.stage', $viewData)->render(),
                'actions' => view('combat.partials.action-panel', $viewData)->render(),
                'sidebar' => view('combat.partials.sidebar', $viewData)->render(),
                'errors' => '',
            ],
        ];
    }
}
