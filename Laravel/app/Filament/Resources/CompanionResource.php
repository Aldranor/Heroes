<?php

namespace App\Filament\Resources;

use App\Enums\CompanionAcquisitionMethod;
use App\Enums\CompanionRole;
use App\Enums\Rarity;
use App\Filament\Resources\CompanionResource\Pages;
use App\Models\Companions\Companion;
use App\Models\Learning\LearningCategory;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CompanionResource extends Resource
{
    protected static ?string $model = Companion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Apparence')
                    ->schema([
                        FileUpload::make('sprite_path')
                            ->label('Sprite')
                            ->image()
                            ->disk('public')
                            ->directory('companions/sprites')
                            ->visibility('public')
                            ->maxSize(5120)
                            ->avatar()
                            ->helperText('Image PNG du sprite du compagnon.'),
                    ])
                    ->collapsible(),

                Section::make('Informations')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Attributs')
                    ->schema([
                        Select::make('specialization_category_id')
                            ->label('Catégorie de spécialisation')
                            ->options(function () {
                                return LearningCategory::query()
                                    ->whereHas('learningDomain', fn ($q) => $q->where('slug', 'driving_license_be'))
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),

                        ToggleButtons::make('role')
                            ->label('Rôle')
                            ->options(CompanionRole::class)
                            ->inline()
                            ->required()
                            ->default(CompanionRole::Balanced),

                        ToggleButtons::make('rarity')
                            ->label('Rareté')
                            ->options(Rarity::class)
                            ->inline()
                            ->required()
                            ->default(Rarity::Common),

                        Select::make('acquisition_method')
                            ->label("Méthode d'obtention")
                            ->options(CompanionAcquisitionMethod::class)
                            ->required()
                            ->default(CompanionAcquisitionMethod::Starter),

                        TextInput::make('unlock_level')
                            ->label('Niveau de déblocage')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(100),
                    ])
                    ->columns(2),

                Section::make('Statistiques de base')
                    ->schema([
                        TextInput::make('base_hp')
                            ->label('PV')
                            ->numeric()
                            ->required()
                            ->default(80)
                            ->minValue(1)
                            ->maxValue(999),

                        TextInput::make('base_attack')
                            ->label('Attaque')
                            ->numeric()
                            ->required()
                            ->default(15)
                            ->minValue(1)
                            ->maxValue(999),

                        TextInput::make('base_defense')
                            ->label('Défense')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(999),

                        TextInput::make('base_speed')
                            ->label('Vitesse')
                            ->numeric()
                            ->required()
                            ->default(15)
                            ->minValue(1)
                            ->maxValue(999),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('sprite_path')
                    ->label('Sprite')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => asset('assets/companions/placeholder.png')),

                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('specializationCategory.name')
                    ->label('Spécialisation')
                    ->searchable()
                    ->limit(20),

                BadgeColumn::make('role')
                    ->label('Rôle')
                    ->colors([
                        'warning' => CompanionRole::Dps,
                        'info' => CompanionRole::Precision,
                        'success' => CompanionRole::Fast,
                        'danger' => CompanionRole::Tank,
                        'primary' => CompanionRole::Support,
                        'gray' => CompanionRole::Balanced,
                    ]),

                BadgeColumn::make('rarity')
                    ->label('Rareté')
                    ->colors([
                        'gray' => Rarity::Common,
                        'info' => Rarity::Rare,
                        'warning' => Rarity::Epic,
                    ]),

                TextColumn::make('base_hp')
                    ->label('PV')
                    ->sortable(),

                TextColumn::make('base_attack')
                    ->label('ATQ')
                    ->sortable(),

                TextColumn::make('base_defense')
                    ->label('DEF')
                    ->sortable(),

                TextColumn::make('base_speed')
                    ->label('VIT')
                    ->sortable(),

                TextColumn::make('unlock_level')
                    ->label('Niveau')
                    ->sortable(),
            ])
            ->filters([])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanions::route('/'),
            'create' => Pages\CreateCompanion::route('/create'),
            'edit' => Pages\EditCompanion::route('/{record}/edit'),
        ];
    }
}
