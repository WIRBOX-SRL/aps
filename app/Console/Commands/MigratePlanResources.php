<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plan;

class MigratePlanResources extends Command
{
    protected $signature = 'plans:migrate-resources';
    protected $description = 'Migrates the resources column from plans to a format compatible with Filament repeater';

    public function handle()
    {
        $plans = Plan::all();
        $count = 0;
        foreach ($plans as $plan) {
            $resources = $plan->resources;
            if (is_array($resources) && array_is_list($resources)) {
                // already in the correct format
                continue;
            }
            if (is_string($resources)) {
                $resources = json_decode($resources, true);
            }
            if (!is_array($resources)) {
                continue;
            }
            $newResources = [];
            foreach ($resources as $resourceName => $data) {
                $newResources[] = [
                    'resource' => $resourceName,
                    'permissions' => $data['permissions'] ?? [],
                    'create_limit' => $data['create_limit'] ?? 0,
                ];
            }
            $plan->resources = $newResources;
            $plan->save();
            $count++;
        }
        $this->info("$count plans have been converted.");
    }
}
