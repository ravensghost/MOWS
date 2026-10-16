<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'role', 'is_on_loa', 'loa_return_date', 'selected_skin', 'app_status', 'content_shield_bypass', 'twitter_handle', 'facebook_handle'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
            'is_on_loa'         => 'boolean',
            'loa_return_date'   => 'date',
        ];
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
     * Synchronise user LOA status, automatically determine LOA vs ELOA by date (> 30 days),
     * update living characters, and log entries in service_records.
     */
    public function syncLoaStatus(bool $isOnLoa, ?string $returnDateString = null, ?string $reason = null, ?int $issuedById = null): string
    {
        $returnDate = ($isOnLoa && $returnDateString) ? Carbon::parse($returnDateString)->startOfDay() : null;

        $this->update([
            'is_on_loa'       => $isOnLoa,
            'loa_return_date' => $returnDate ? $returnDate->toDateString() : null,
        ]);

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
