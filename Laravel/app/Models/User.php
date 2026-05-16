<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Avatar\Avatar;
use App\Models\Avatar\AvatarItem;
use App\Models\Avatar\UserAvatarItem;
use App\Models\Battle\Battle;
use App\Models\Commerce\UserEquipment;
use App\Models\Companions\UserCompanion;
use App\Models\Creatures\UserCreature;
use App\Models\Lessons\UserFlashcardProgress;
use App\Models\Lessons\UserLessonProgress;
use App\Models\Quests\UserQuest;
use App\Models\Skills\UserSkill;
use App\Models\Skills\UserSkillTreeProgress;
use App\Models\Training\TrainingSession;
use App\Models\World\ArenaRun;
use App\Models\World\UserLevelProgress;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'combat_loadout' => 'array',
        ];
    }

    public function avatar(): HasOne
    {
        return $this->hasOne(Avatar::class);
    }

    public function hasCompletedOnboarding(): bool
    {
        if ($this->relationLoaded('avatar')) {
            return $this->avatar !== null;
        }

        return $this->avatar()->exists();
    }

    public function userAvatarItems(): HasMany
    {
        return $this->hasMany(UserAvatarItem::class);
    }

    public function avatarItems(): BelongsToMany
    {
        return $this->belongsToMany(AvatarItem::class, 'user_avatar_items')
            ->withPivot('acquired_at')
            ->withTimestamps();
    }

    public function userCreatures(): HasMany
    {
        return $this->hasMany(UserCreature::class);
    }

    public function userCompanions(): HasMany
    {
        return $this->hasMany(UserCompanion::class);
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(UserLessonProgress::class);
    }

    public function flashcardProgress(): HasMany
    {
        return $this->hasMany(UserFlashcardProgress::class);
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function levelProgress(): HasMany
    {
        return $this->hasMany(UserLevelProgress::class);
    }

    public function userQuests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }

    public function userEquipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }

    public function arenaRuns(): HasMany
    {
        return $this->hasMany(ArenaRun::class);
    }

    public function userSkills(): HasMany
    {
        return $this->hasMany(UserSkill::class);
    }

    public function unlockedSkills(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Creatures\Skill::class, 'user_skills')
            ->wherePivot('owner_type', 'hero')
            ->wherePivot('owner_reference_id', 0)
            ->withPivot(['skill_node_id', 'source', 'unlocked_at'])
            ->withTimestamps();
    }

    public function skillTreeProgress(): HasMany
    {
        return $this->hasMany(UserSkillTreeProgress::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
