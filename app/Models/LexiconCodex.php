<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LexiconCodex  extends Model
{
    protected $table = 'lexicon_codex'; // Must match the table name in database.sql

    protected $fillable = [
        'semantic_token',         // Matches semantic_token in database.sql
        'prefix_group_title',     // Matches prefix_group_title in database.sql
        'group_title',            // Matches group_title in database.sql
        'default_lexicon_string', // Matches default_lexicon_string in database.sql
        'translation_payloads'    // Matches translation_payloads in database.sql
    ];

    protected function casts(): array
    {
        return [
            'translation_payloads' => 'array', // Cast the JSON column properly
        ];
    }
}
