<?php

namespace App\Services;

use App\Enums\ScenarioActionType;
use App\Enums\ScenarioTriggerType;
use App\Models\World\Scenario;
use App\Models\World\ScenarioStep;
use Illuminate\Support\Collection;

class ScenarioService
{
    public function findByTrigger(ScenarioTriggerType|string $triggerType, array $filters = []): ?Scenario
    {
        $triggerType = is_string($triggerType)
            ? ScenarioTriggerType::from($triggerType)
            : $triggerType;

        return Scenario::query()
            ->with('steps.npc')
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->when(
                isset($filters['learning_domain_id']),
                fn ($query) => $query->where('learning_domain_id', $filters['learning_domain_id']),
            )
            ->when(
                isset($filters['slug']),
                fn ($query) => $query->where('slug', $filters['slug']),
            )
            ->first();
    }

    public function getOrderedSteps(Scenario $scenario): Collection
    {
        return $scenario->steps()
            ->with('npc')
            ->orderBy('sort_order')
            ->get();
    }

    public function resolveStepAction(ScenarioStep $scenarioStep): ?array
    {
        if (! $scenarioStep->action_type || $scenarioStep->action_type === ScenarioActionType::None) {
            return null;
        }

        return [
            'type' => $scenarioStep->action_type->value,
            'payload' => $scenarioStep->action_payload ?? [],
        ];
    }
}
