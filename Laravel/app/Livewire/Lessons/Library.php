<?php

namespace App\Livewire\Lessons;

use App\Services\LessonService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Library extends Component
{
    public ?int $selectedDomainId = null;

    public ?int $selectedTopicId = null;

    public ?int $mindmapTopicId = null;

    public function selectDomain(int $domainId): void
    {
        $this->selectedDomainId = $domainId;
        $this->selectedTopicId = null;
        $this->mindmapTopicId = null;
    }

    public function selectTopic(?int $topicId = null): void
    {
        $this->selectedTopicId = $topicId;
        $this->mindmapTopicId = null;
    }

    public function toggleMindmap(int $topicId): void
    {
        $this->mindmapTopicId = $this->mindmapTopicId === $topicId ? null : $topicId;
    }

    public function render(): View
    {
        return view('livewire.lessons.library', app(LessonService::class)->libraryData(
            auth()->user(),
            $this->selectedDomainId,
            $this->selectedTopicId,
        ));
    }
}
