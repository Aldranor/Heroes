<?php

namespace Database\Seeders\RoadSpirits;

use App\Enums\AvatarItemType;
use App\Enums\Currency;
use App\Enums\EquipmentTargetType;
use App\Enums\EquipmentType;
use App\Enums\Rarity;
use App\Enums\ShopItemType;
use App\Models\Avatar\AvatarItem;
use App\Models\Commerce\Equipment;
use App\Models\Commerce\ShopItem;
use App\Models\Creatures\Creature;
use Illuminate\Database\Seeder;

class RoadSpiritsCommerceSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = collect([
            ['name' => 'Sifflet de Guide', 'slug' => 'sifflet-de-guide', 'type' => EquipmentType::Weapon, 'rarity' => Rarity::Common, 'price' => 60, 'stat_bonus' => ['attack' => 3], 'description' => 'Augmente la précision offensive des créatures.'],
            ['name' => 'Veste Réfléchissante', 'slug' => 'veste-reflechissante', 'type' => EquipmentType::Armor, 'rarity' => Rarity::Common, 'price' => 70, 'stat_bonus' => ['defense' => 4], 'description' => 'Une couche protectrice pour les sorties urbaines.'],
            ['name' => 'Boussole Civique', 'slug' => 'boussole-civique', 'type' => EquipmentType::Charm, 'rarity' => Rarity::Rare, 'price' => 95, 'stat_bonus' => ['speed' => 3], 'description' => 'Garde le cap dans les zones agitées.'],
            ['name' => 'Badge Radar', 'slug' => 'badge-radar', 'type' => EquipmentType::Accessory, 'rarity' => Rarity::Rare, 'price' => 110, 'stat_bonus' => ['attack' => 2, 'speed' => 2], 'description' => 'Idéal pour les profils rapides.'],
            ['name' => 'Talisman du Moniteur', 'slug' => 'talisman-du-moniteur', 'type' => EquipmentType::Charm, 'rarity' => Rarity::Epic, 'price' => 150, 'stat_bonus' => ['hp' => 8, 'defense' => 3], 'description' => 'Favorise les combats longs et maîtrisés.'],
        ])->mapWithKeys(function (array $item) {
            $equipment = Equipment::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'name_translations' => ['fr' => $item['name']],
                    'description_translations' => ['fr' => $item['description']],
                    'target_type' => EquipmentTargetType::Creature,
                    'image' => null,
                ]),
            );

            return [$equipment->slug => $equipment];
        });

        $avatarItems = collect([
            ['name' => 'Coupe Vent Dorée', 'slug' => 'coupe-vent-doree', 'type' => AvatarItemType::Hair, 'rarity' => Rarity::Common, 'price' => 35],
            ['name' => 'Tenue Éco Pilote', 'slug' => 'tenue-eco-pilote', 'type' => AvatarItemType::Outfit, 'rarity' => Rarity::Common, 'price' => 50],
            ['name' => 'Foulard Ambre', 'slug' => 'foulard-ambre', 'type' => AvatarItemType::Accessory, 'rarity' => Rarity::Rare, 'price' => 75],
            ['name' => 'Fond Ville Douce', 'slug' => 'fond-ville-douce', 'type' => AvatarItemType::Background, 'rarity' => Rarity::Rare, 'price' => 90],
            ['name' => 'Lunettes du Hub', 'slug' => 'lunettes-du-hub', 'type' => AvatarItemType::Accessory, 'rarity' => Rarity::Epic, 'price' => 120],
        ])->mapWithKeys(function (array $item) {
            $avatarItem = AvatarItem::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, [
                    'name_translations' => ['fr' => $item['name']],
                    'is_cosmetic_only' => true,
                    'image' => null,
                ]),
            );

            return [$avatarItem->slug => $avatarItem];
        });

        $shopItems = [
            ['type' => ShopItemType::Equipment, 'item' => $equipment['sifflet-de-guide'], 'price' => 60],
            ['type' => ShopItemType::Equipment, 'item' => $equipment['veste-reflechissante'], 'price' => 70],
            ['type' => ShopItemType::Equipment, 'item' => $equipment['boussole-civique'], 'price' => 95],
            ['type' => ShopItemType::AvatarItem, 'item' => $avatarItems['tenue-eco-pilote'], 'price' => 50],
            ['type' => ShopItemType::AvatarItem, 'item' => $avatarItems['foulard-ambre'], 'price' => 75],
            ['type' => ShopItemType::Creature, 'item' => Creature::query()->where('slug', 'chat-reflexe')->firstOrFail(), 'price' => 180],
        ];

        foreach ($shopItems as $shopItemData) {
            ShopItem::query()->updateOrCreate(
                [
                    'item_type' => $shopItemData['type'],
                    'item_id' => $shopItemData['item']->id,
                ],
                [
                    'price' => $shopItemData['price'],
                    'currency' => Currency::Coins,
                    'is_active' => true,
                ],
            );
        }
    }
}
