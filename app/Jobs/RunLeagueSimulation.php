<?php

namespace App\Jobs;

use App\Models\LeagueSimulation;
use App\Services\LeagueSimulationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RunLeagueSimulation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(public int $simulationId) {}

    public function handle(LeagueSimulationService $service): void
    {
        $simulation = LeagueSimulation::findOrFail($this->simulationId);
        try {
            $service->run($simulation);
        } catch (Throwable) {
            // The service records the failure; the owner can retry from the league table.
        }
    }
}
