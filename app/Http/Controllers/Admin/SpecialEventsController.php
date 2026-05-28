<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpecialEvent;
use App\Services\AI\Events\SpecialEventsManager;
use Illuminate\Http\Request;

class SpecialEventsController extends Controller
{
    protected SpecialEventsManager $eventsManager;

    public function __construct(SpecialEventsManager $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }

    public function index()
    {
        $activeEvent = $this->eventsManager->getActiveEvent();
        $events = SpecialEvent::query()->orderByDesc('created_at')->paginate(10);
        
        return view('admin.events.index', [
            'activeEvent' => $activeEvent,
            'events' => $events,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string', // election, war, disaster, celebration
            'duration_hours' => 'required|integer|min:1|max:72',
            'activity_boost_factor' => 'required|numeric|min:0.5|max:5.0',
            'context_prompt' => 'required|string|max:1000',
        ]);

        $this->eventsManager->startEvent(
            $validated['event_type'],
            $validated['duration_hours'],
            $validated['activity_boost_factor'],
            $validated['context_prompt']
        );

        return redirect()->route('admin.special-events.index')->with('success', 'Event started successfully!');
    }

    public function update(Request $request, $id)
    {
        // Example: Stop event
        if ($request->has('stop')) {
            $this->eventsManager->stopEvent();
            return redirect()->route('admin.special-events.index')->with('success', 'Event stopped successfully!');
        }
        
        return redirect()->route('admin.special-events.index');
    }
}
