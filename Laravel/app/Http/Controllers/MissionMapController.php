<?php

namespace App\Http\Controllers;

use App\Services\StorySceneService;
use App\Services\TrainingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionMapController extends Controller
{
    public function index(Request $request, TrainingService $trainingService, StorySceneService $storySceneService): View|RedirectResponse
    {
        $completeSceneType = $request->string('complete_scene_type')->toString();
        $completeSceneId = $request->integer('complete_scene_id');

        if ($completeSceneType !== '' && $completeSceneId > 0) {
            $storySceneService->markCompleted(
                $request->user(),
                $completeSceneType,
                $completeSceneId,
            );

            return redirect()->route('missions.index', array_filter([
                'world' => $request->integer('world') ?: null,
                'map' => $request->integer('map') ?: null,
                'node' => $request->integer('node') ?: null,
            ]));
        }

        $selection = $trainingService->selectionOptions(
            $request->user(),
            $request->integer('world') ?: null,
            $request->integer('map') ?: null,
            $request->integer('node') ?: null,
        );

        $selection['selectedScene'] = $storySceneService->missionSceneState(
            $request->user(),
            $selection['selectedDialogue'],
            $selection['selectedNode']?->scenario,
        );

        return view('missions.index', $selection);
    }
}
