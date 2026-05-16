<?php

namespace App\Http\Controllers;

use App\Services\StorySceneService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StorySceneController extends Controller
{
    public function store(Request $request, StorySceneService $storySceneService): Response
    {
        $payload = $request->validate([
            'scene_type' => ['required', 'string'],
            'scene_id' => ['required', 'integer'],
        ]);

        $storySceneService->markCompleted(
            $request->user(),
            $payload['scene_type'],
            (int) $payload['scene_id'],
        );

        return response()->noContent();
    }
}
