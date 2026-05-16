<?php

namespace Tests\Unit\Services;

use App\Enums\QuestObjectiveType;
use App\Enums\QuestType;
use App\Enums\ShopItemType;
use App\Services\QuestService;
use App\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestAndShopServiceTest extends TestCase
{
    use CreatesGameData;
    use RefreshDatabase;

    public function test_accepting_quest_creates_accepted_user_quest(): void
    {
        $user = $this->createUser();
        $quest = $this->createQuest([
            'type' => QuestType::Training,
            'objective_type' => QuestObjectiveType::CompleteTrainingSessions,
        ]);

        $userQuest = app(QuestService::class)->acceptQuest($user, $quest);

        $this->assertSame('accepted', $userQuest->status->value);
        $this->assertNotNull($userQuest->accepted_at);
    }

    public function test_quest_progress_updates_and_completes(): void
    {
        $user = $this->createUser();
        $quest = $this->createQuest([
            'type' => QuestType::Battle,
            'objective_type' => QuestObjectiveType::DefeatEnemies,
            'objective_target' => 3,
            'objective_payload' => ['zone_id' => 99],
        ]);

        $userQuest = app(QuestService::class)->acceptQuest($user, $quest);
        app(QuestService::class)->updateProgress($user, QuestObjectiveType::DefeatEnemies, 3, ['zone_id' => 99]);

        $this->assertSame(3, $userQuest->fresh()->progress);
        $this->assertSame('completed', $userQuest->fresh()->status->value);
    }

    public function test_shop_purchase_deducts_coins_and_grants_item(): void
    {
        $user = $this->createUser(['coins' => 100]);
        $equipment = $this->createEquipment(['price' => 40]);
        $shopItem = $this->createShopItem($equipment, ShopItemType::Equipment, ['price' => 40]);

        $purchase = app(ShopService::class)->purchase($user, $shopItem);

        $this->assertSame(60, $user->fresh()->coins);
        $this->assertSame($equipment->id, $purchase->equipment_id);
        $this->assertDatabaseHas('user_equipment', [
            'user_id' => $user->id,
            'equipment_id' => $equipment->id,
        ]);
    }
}
