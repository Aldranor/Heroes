<?php

namespace App\Http\Controllers;

use App\Models\Skills\SkillNode;
use App\Services\SkillTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SkillTreeController extends Controller
{
    public function index(Request $request, SkillTreeService $skillTreeService): View
    {
        return view('skills.index', $skillTreeService->pageData(
            $request->user(),
            $request->string('subject')->toString() ?: null,
            $request->string('tree')->toString() ?: null,
            $request->integer('node') ?: null,
        ));
    }

    public function unlock(Request $request, SkillNode $skillNode, SkillTreeService $skillTreeService): RedirectResponse
    {
        try {
            $skillTreeService->unlockNode($request->user(), $skillNode, $request->string('subject')->toString() ?: null);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('skills.index', [
                    'subject' => $request->string('subject')->toString() ?: null,
                    'tree' => $skillNode->skillTree?->slug,
                    'node' => $skillNode->id,
                ])
                ->withErrors(['skills' => $exception->getMessage()]);
        }

        return redirect()
            ->route('skills.index', [
                'subject' => $request->string('subject')->toString() ?: null,
                'tree' => $skillNode->skillTree?->slug,
                'node' => $skillNode->id,
            ])
            ->with('skill_tree_status', 'Compétence débloquée.');
    }

    public function respec(Request $request, SkillTreeService $skillTreeService): RedirectResponse
    {
        $subject = $request->string('subject')->toString() ?: null;

        try {
            $skillTreeService->respec($request->user(), $subject);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('skills.index', [
                    'subject' => $subject,
                    'tree' => $request->string('tree')->toString() ?: null,
                ])
                ->withErrors(['skills' => $exception->getMessage()]);
        }

        return redirect()
            ->route('skills.index', [
                'subject' => $subject,
                'tree' => $request->string('tree')->toString() ?: null,
            ])
            ->with('skill_tree_status', 'Talents réinitialisés.');
    }
}
