<?php

namespace App\Services\AI\Events;

use App\Models\SpecialEvent;
use App\Models\AiAgent;
use Illuminate\Support\Collection;

class SpecialEventsManager
{
    /**
     * Get latest active global event for admin UI.
     */
    public function getActiveEvent(): ?SpecialEvent
    {
        return SpecialEvent::active()
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->latest('start_date')
            ->first();
    }

    /**
     * Start a new event and stop existing active events.
     */
    public function startEvent(string $eventType, int $durationHours, float $activityBoostFactor, string $contextPrompt): SpecialEvent
    {
        // Keep single active event for deterministic behavior in admin controls.
        SpecialEvent::active()->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);

        $normalizedType = $this->normalizeEventType($eventType);

        return SpecialEvent::create([
            'title' => ucfirst(str_replace('_', ' ', $eventType)) . ' Event',
            'type' => $normalizedType,
            'country' => null,
            'keywords' => [$eventType],
            'start_date' => now(),
            'end_date' => now()->addHours($durationHours),
            'status' => 'active',
            'boost_factor' => $activityBoostFactor,
            'context_prompt' => $contextPrompt,
        ]);
    }

    /**
     * Stop currently active events.
     */
    public function stopEvent(): int
    {
        return SpecialEvent::active()->update([
            'status' => 'completed',
            'end_date' => now(),
        ]);
    }

    /**
     * Get active events relevant to an agent
     */
    public function getActiveEvents(AiAgent $agent): Collection
    {
        // Cache could be added here
        return SpecialEvent::active()
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', now());
            })
            ->forCountry($agent->country)
            ->get();
    }

    /**
     * Get the highest boost factor from active events
     */
    public function getActivityBoostFactor(AiAgent $agent): float
    {
        $events = $this->getActiveEvents($agent);
        if ($events->isEmpty()) {
            return 1.0;
        }

        return $events->max('boost_factor');
    }

    /**
     * Get context prompt for active events
     */
    public function getEventContext(AiAgent $agent): string
    {
        $events = $this->getActiveEvents($agent);
        if ($events->isEmpty()) {
            return "";
        }

        $context = "\n[SPECIAL EVENT ACTIVE]\n";
        foreach ($events as $event) {
            $context .= "Event: {$event->title} ({$event->type}).\n";
            $context .= "Instruction: {$event->context_prompt}\n";
            $context .= "Keywords: " . implode(", ", $event->keywords ?? []) . "\n";
        }
        $context .= "Prioritize this event in your posts.\n";

        return $context;
    }

    private function normalizeEventType(string $eventType): string
    {
        return match ($eventType) {
            'election' => 'election',
            'war' => 'war',
            'disaster' => 'disaster',
            'sports_final' => 'sports',
            'tech_launch' => 'other',
            default => 'other',
        };
    }
}
