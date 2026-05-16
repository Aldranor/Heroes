<?php

namespace App\Http\Controllers;

use App\Services\StorySceneService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EchoHallController extends Controller
{
    public function index(Request $request, StorySceneService $storySceneService): View
    {
        return view('memories.index', $storySceneService->library(
            $request->user(),
            $request->string('scene_type')->toString() ?: null,
            $request->integer('scene_id') ?: null,
        ));
    }
}
