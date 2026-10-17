<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'is_on_loa',
    'loa_return_date',
    'selected_skin',
    'app_status',
    'content_shield_bypass',
    'twitter_handle',
    'facebook_handle',
    'discord_id',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Eager-load user_attributes and Spatie roles by default to prevent N+1 queries.
     *
     * @var array<int, string>
     */
    protected $with = ['attributes', 'roles'];

    /**
     * Keys stored in the relational `user_attributes` table rather than `users` columns.
     *
     * @var array<int, string>
     */
    public const NORMALIZED_ATTRIBUTES = [
        'twitter_handle',
        'facebook_handle',
        'discord_id',
        'selected_skin',
        'is_on_loa',
        'loa_return_date',
        'app_status',
        'content_shield_bypass',
    ];

    /**
     * Pending normalised attributes queued during fill() / property assignment before save().
     *
     * @var array<string, mixed>
     */
    protected array $pendingNormalizedAttributes = [];

    /**
     * Pending Spatie role queued via legacy $user->role assignment or fill().
     */
    protected ?string $pendingSpatieRole = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Boot model lifecycle hooks so legacy mass-assignment or property assignment
     * automatically routes normalised attributes to `user_attributes` and `roles`.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            foreach (self::NORMALIZED_ATTRIBUTES as $key) {
                if (array_key_exists($key, $user->attributes)) {
                    $user->pendingNormalizedAttributes[$key] = $user->attributes[$key];
                    unset($user->attributes[$key]);
                }
            }

            if (array_key_exists('role', $user->attributes)) {
                $user->pendingSpatieRole = $user->attributes['role'];
                unset($user->attributes['role']);
            }
        });

        static::saved(function (User $user) {
            if (! empty($user->pendingNormalizedAttributes)) {
                foreach ($user->pendingNormalizedAttributes as $key => $val) {
                    $user->setAttr($key, $val);
                }
                $user->pendingNormalizedAttributes = [];
            }

            if ($user->pendingSpatieRole !== null) {
                $roleName = $user->pendingSpatieRole;
                $user->pendingSpatieRole = null;

                $existingRole = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($existingRole) {
                    $user->syncRoles([$existingRole]);
                }
            }
        });
    }

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function posts()
    {
        return $this->hasMany(Chapter::class, 'author_user_id');
    }

    public function oocAwards()
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function applicationVotes()
    {
        return $this->hasMany(ApplicationVote::class, 'target_user_id');
    }

    public function attributes()
    {
        return $this->hasMany(UserAttribute::class);
    }

    /**
     * Retrieve a normalised user attribute from the eager-loaded `attributes` relationship.
     */
    public function getAttr(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->pendingNormalizedAttributes)) {
            return $this->pendingNormalizedAttributes[$key];
        }

        if (! $this->exists) {
            return $default;
        }

        $relations = $this->getRelations();
        if (! isset($relations['attributes'])) {
            $this->load('attributes');
            $relations = $this->getRelations();
        }

        $found = $relations['attributes']->firstWhere('key', $key);

        return $found !== null && $found->value !== null ? $found->value : $default;
    }

    /**
     * Persist a normalised user attribute into `user_attributes` and refresh the in-memory relation.
     */
    public function setAttr(string $key, mixed $value): void
    {
        if ($value instanceof Carbon) {
            $storedValue = $value->toDateString();
        } elseif (is_bool($value)) {
            $storedValue = $value ? '1' : '0';
        } elseif ($value === null) {
            $storedValue = null;
        } else {
            $storedValue = (string)$value;
        }

        if (! $this->exists) {
            $this->pendingNormalizedAttributes[$key] = $storedValue;
            return;
        }

        $record = $this->attributes()->updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue]
        );

        $relations = $this->getRelations();
        if (isset($relations['attributes'])) {
            $collection = $relations['attributes']->reject(fn($item) => $item->key === $key);
            $collection->push($record);
            $this->setRelation('attributes', $collection);
        }
    }

    // -------------------------------------------------------------------------
    // Transparent Eloquent Accessors & Mutators for Normalised Attributes
    // -------------------------------------------------------------------------

    protected function twitterHandle(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAttr('twitter_handle'),
            set: function ($value) {
                $this->pendingNormalizedAttributes['twitter_handle'] = $value;
                return [];
            }
        );
    }

    protected function facebookHandle(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAttr('facebook_handle'),
            set: function ($value) {
                $this->pendingNormalizedAttributes['facebook_handle'] = $value;
                return [];
            }
        );
    }

    protected function discordId(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAttr('discord_id'),
            set: function ($value) {
                $this->pendingNormalizedAttributes['discord_id'] = $value;
                return [];
            }
        );
    }

    protected function selectedSkin(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAttr('selected_skin'),
            set: function ($value) {
                $this->pendingNormalizedAttributes['selected_skin'] = $value;
                return [];
            }
        );
    }

    protected function isOnLoa(): Attribute
    {
        return Attribute::make(
            get: fn() => filter_var($this->getAttr('is_on_loa', false), FILTER_VALIDATE_BOOLEAN),
            set: function ($value) {
                $this->pendingNormalizedAttributes['is_on_loa'] = $value ? '1' : '0';
                return [];
            }
        );
    }

    protected function loaReturnDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $val = $this->getAttr('loa_return_date');
                return $val ? Carbon::parse($val) : null;
            },
            set: function ($value) {
                $this->pendingNormalizedAttributes['loa_return_date'] = $value instanceof Carbon
                    ? $value->toDateString()
                    : ($value ? Carbon::parse($value)->toDateString() : null);
                return [];
            }
        );
    }

    protected function appStatus(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getAttr('app_status', 'pending'),
            set: function ($value) {
                $this->pendingNormalizedAttributes['app_status'] = $value ?? 'pending';
                return [];
            }
        );
    }

    protected function contentShieldBypass(): Attribute
    {
        return Attribute::make(
            get: fn() => filter_var($this->getAttr('content_shield_bypass', false), FILTER_VALIDATE_BOOLEAN),
            set: function ($value) {
                $this->pendingNormalizedAttributes['content_shield_bypass'] = $value ? '1' : '0';
                return [];
            }
        );
    }

    /**
     * Virtual `role` accessor/mutator backed by existing Spatie Roles.
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->pendingSpatieRole !== null) {
                    return $this->pendingSpatieRole;
                }
                return $this->roles->first()?->name ?? 'user';
            },
            set: function ($value) {
                $this->pendingSpatieRole = $value;
                return [];
            }
        );
    }

    /**
     * Synchronise user LOA status, automatically determine LOA vs ELOA by date (> 30 days),
     * update living characters, and log entries in service_records.
     */
    public function syncLoaStatus(bool $isOnLoa, ?string $returnDateString = null, ?string $reason = null, ?int $issuedById = null): string
    {
        $returnDate = ($isOnLoa && $returnDateString) ? Carbon::parse($returnDateString)->startOfDay() : null;

        $this->setAttr('is_on_loa', $isOnLoa);
        $this->setAttr('loa_return_date', $returnDate ? $returnDate->toDateString() : null);

        // Automatically determine LOA vs ELOA based on return date (> 30 days = ELOA)
        $targetCharacterStatus = 'active';
        $statusLabel = 'Active Duty';

        if ($isOnLoa && $returnDate) {
            $daysAway = now()->startOfDay()->diffInDays($returnDate, false);
            if ($daysAway > 30) {
                $targetCharacterStatus = 'eloa';
                $statusLabel = 'Extended Leave of Absence (ELOA)';
            } else {
                $targetCharacterStatus = 'loa';
                $statusLabel = 'Leave of Absence (LOA)';
            }
        } elseif ($isOnLoa) {
            $targetCharacterStatus = 'loa';
            $statusLabel = 'Leave of Absence (LOA)';
        }

        // Only auto-adjust living characters (exclude honoured_dead and memorial)
        $livingCharacters = $this->characters()
            ->whereIn('status', ['active', 'loa', 'eloa'])
            ->get();

        $issuer = $issuedById ?? auth()->id() ?? $this->id;
        $dateNote = $returnDate ? ' (Expected return: ' . $returnDate->format('Y-m-d') . ')' : '';
        $reasonNote = $reason ? ' Reason: ' . $reason : '';

        foreach ($livingCharacters as $character) {
            if ($character->status !== $targetCharacterStatus) {
                $character->update(['status' => $targetCharacterStatus]);

                ServiceRecord::create([
                    'user_id'        => $this->id,
                    'character_id'   => $character->id,
                    'event_type'     => 'status_change',
                    'description'    => "Status automatically updated to {$statusLabel}{$dateNote}.{$reasonNote}",
                    'issued_by'      => $issuer,
                    'display_target' => 'BOTH',
                ]);
            }
        }

        Cache::forget('widget_roster_sections');

        return $statusLabel;
    }
}
