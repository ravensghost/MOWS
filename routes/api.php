<?php

use App\Models\Configuration;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('engine')->name('api.engine.')->group(function () {
    Route::get('/version', function (): JsonResponse {
        return response()->json([
            'status'   => 'success',
            'message'  => 'MOWS Engine version retrieved.',
            'data'     => [
                'engine'          => 'MOWS Engine',
                'version'         => config('app.version', '1.0.0'),
                'release_notes'   => url('/'),
                'documentation_at' => now()->toIso8601String(),
            ],
            'redirect' => null,
        ], 200);
    })->name('version');

    Route::get('/telemetry', function (): JsonResponse {
        $configs = Configuration::whereIn('configuration_token', [
            'unit_designation',
            'unit_class',
            'era_setting',
            'continuity_timeline',
            'ai_rating',
            'game_setting',
        ])->pluck('configuration_payload', 'configuration_token');

        return response()->json([
            'status'   => 'success',
            'message'  => 'Satellite telemetry retrieved.',
            'data'     => [
                'unit_designation'    => $configs['unit_designation'] ?? config('app.name', 'MOWS Unit'),
                'unit_class'          => $configs['unit_class'] ?? 'Standard',
                'era_setting'         => $configs['era_setting'] ?? ($configs['game_setting'] ?? 'Custom'),
                'continuity_timeline' => $configs['continuity_timeline'] ?? 'Prime',
                'ai_rating'           => $configs['ai_rating'] ?? 'T',
                'active_personae'     => Persona::where('persona_status', 'active')->count(),
            ],
            'redirect' => null,
        ], 200);
    })->name('telemetry');
});