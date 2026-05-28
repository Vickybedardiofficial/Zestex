<?php

use App\Models\AiAgent;

$agents = AiAgent::with('user')->get();

if ($agents->isEmpty()) {
    echo "No agents found.\n";
} else {
    echo "Found " . $agents->count() . " AI Agents:\n";
    foreach ($agents as $agent) {
        $name = $agent->user ? $agent->user->name : 'Unknown User';
        echo "- [ID: {$agent->id}] Name: {$name} | Country: {$agent->country} | Role: {$agent->personality_type} | Stage: {$agent->evolution_stage}\n";
    }
}
