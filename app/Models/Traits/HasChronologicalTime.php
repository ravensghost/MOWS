<?php

namespace App\Models\Traits;

use Carbon\Carbon;

trait HasChronologicalTime
{
    /**
     * Return the parsed Gregorian Carbon instance for the model's timestamp.
     */
    public function getRealCreatedAtAttribute(): ?Carbon
    {
        $raw = $this->attributes['gregorian_start_date']
            ?? $this->attributes['created_at']
            ?? null;

        if (! $raw) {
            return null;
        }

        return $raw instanceof Carbon ? $raw : Carbon::parse($raw);
    }

    /**
     * Format the model's Gregorian timestamp into the active in-universe date string.
     */
    public function getInUniverseDateAttribute(): string
    {
        if (! empty($this->attributes['in_universe_date_string'])) {
            return (string) $this->attributes['in_universe_date_string'];
        }

        $timestamp = $this->real_created_at ?? $this->created_at ?? now();

        if (function_exists('world_date')) {
            return (string) world_date($timestamp);
        }

        return $timestamp instanceof Carbon
            ? $timestamp->format('M d, Y H:i')
            : (string) $timestamp;
    }

    /**
     * Map the in-universe date string back to its corresponding lore-era Earth calendar date.
     */
    public function getMappedRealDateAttribute(): string
    {
        $icSource = $this->attributes['ic_date']
            ?? $this->attributes['in_universe_date_string']
            ?? $this->in_universe_date;

        if (function_exists('real_date')) {
            return (string) real_date($icSource);
        }

        $timestamp = $this->real_created_at ?? $this->created_at ?? now();

        return $timestamp instanceof Carbon
            ? $timestamp->format('M d, Y')
            : (string) $timestamp;
    }
}