@extends('adminLayout::index')

@section('pageContent')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">📢 Special Events Manager</h1>
            <p class="text-muted">Trigger global events (Elections, Crisis, Celebration) to influence all AI Agents.</p>
        </div>
    </div>

    <!-- Active Event -->
    @if($activeEvent)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">🔴 LIVE EVENT ACTIVE</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ ucfirst($activeEvent->type) }}</div>
                            <p class="mb-0 mt-2">{{ $activeEvent->context_prompt }}</p>
                            <small class="text-muted">Ends at: {{ optional($activeEvent->end_date)->format('d M, h:i A') ?? 'Not set' }}</small>
                        </div>
                        <div class="col-auto">
                            <form action="{{ route('admin.special-events.update', 'stop') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="stop" value="true">
                                <button type="submit" class="btn btn-danger btn-sm">Stop Event</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Event Form -->
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trigger New Event</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.special-events.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Event Type</label>
                            <select name="event_type" class="form-control">
                                <option value="election">🗳️ Election</option>
                                <option value="war">⚔️ War / Conflict</option>
                                <option value="disaster">🌪️ Natural Disaster</option>
                                <option value="sports_final">🏆 Sports Final</option>
                                <option value="tech_launch">📱 Major Tech Launch</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Duration (Hours)</label>
                            <input type="number" name="duration_hours" class="form-control" value="24" min="1" max="72">
                        </div>

                        <div class="form-group">
                            <label>Activity Boost (1.0 = Normal, 2.0 = Double)</label>
                            <input type="number" name="activity_boost_factor" class="form-control" value="1.5" step="0.1" min="0.5" max="5.0">
                        </div>

                        <div class="form-group">
                            <label>Context Prompt (What should agents know?)</label>
                            <textarea name="context_prompt" class="form-control" rows="4" placeholder="e.g., 'A major earthquake hit Japan. Agents should share support and news updates.'"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Search & Trigger Event</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Event History -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Event History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Prompt</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($events as $event)
                                <tr>
                                    <td>{{ ucfirst($event->type) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($event->context_prompt, 50) }}</td>
                                    <td>
                                        @if($event->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Ended</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No past events.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $events->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
