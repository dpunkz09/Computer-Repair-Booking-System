<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Requests\UpdateTicketDetailsRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Requests\UpdateTicketStatusRequest;
use App\Http\Resources\TicketCommentResource;
use App\Models\ServiceCategory;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TicketPhotoService;
use App\Support\SiteSettings;
use App\Support\TicketStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(
        private TicketPhotoService $ticketPhotos
    ) {}

    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $filters = array_merge([
            'q' => null,
            'status' => null,
            'priority' => null,
            'date_from' => null,
            'date_to' => null,
            'technician_id' => null,
            'customer_id' => null,
            'sort' => 'priority',
        ], $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => ['nullable', TicketStatus::ruleInFilterable()],
            'priority' => 'nullable|integer|between:1,5',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'technician_id' => 'nullable|integer|exists:users,id',
            'customer_id' => 'nullable|integer|exists:users,id',
            'sort' => 'nullable|in:priority,newest,oldest',
        ]));

        $query = Ticket::with(['customer', 'technician', 'photos']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'technician') {
            $query->where('technician_id', $user->id);
        } else {
            if (! empty($filters['technician_id'])) {
                $query->where('technician_id', $filters['technician_id']);
            }

            if (! empty($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }
        }

        if (! empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($sub) use ($search) {
                $sub->where('issue_summary', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('device_type', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $sub->orWhere('id', (int) $search);
                }
            });
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === TicketStatus::CANCELLED_FILTER) {
                $query->cancelled();
            } elseif ($filters['status'] === TicketStatus::CLOSED) {
                $query->where('status', TicketStatus::CLOSED)->notCancelled();
            } else {
                $query->where('status', $filters['status'])->notCancelled();
            }
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        match ($filters['sort'] ?? 'priority') {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            default => $query->orderByDesc('priority')->latest(),
        };

        $tickets = $query->paginate(10)->withQueryString();

        $technicians = $user->isAdmin()
            ? User::query()->where('role', 'technician')->orderBy('name')->get(['id', 'name'])
            : collect();

        $customers = $user->isAdmin()
            ? User::query()->where('role', 'customer')->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('tickets.index', compact('tickets', 'filters', 'technicians', 'customers'));
    }

    /**
     * Show the form for creating a new ticket (Customer only).
     */
    public function create()
    {
        $this->authorize('create', Ticket::class);

        $categories = ServiceCategory::active()->orderBy('name')->get();

        return view('tickets.create', compact('categories'));
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validate([
            'device_type' => 'required|string',
            'brand' => 'required|string',
            'os' => 'required|string',
            'issue_summary' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|integer|between:1,5',
            'service_category_id' => 'nullable|exists:service_categories,id',
            'photos' => 'nullable|array|max:8',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $ticket = Ticket::create([
            'customer_id' => Auth::id(),
            'service_category_id' => $validated['service_category_id'] ?? null,
            'device_type' => $validated['device_type'],
            'brand' => $validated['brand'],
            'os' => $validated['os'],
            'issue_summary' => $validated['issue_summary'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 3,
            'status' => TicketStatus::NEW,
        ]);

        if ($request->hasFile('photos')) {
            $result = $this->ticketPhotos->storeMany($ticket, $request->file('photos'));

            if ($result['skipped'] > 0 && $result['stored'] > 0) {
                session()->flash('warning', "{$result['stored']} photo(s) uploaded. {$result['skipped']} skipped (8 photo limit per ticket).");
            }
        }

        SiteSettings::applyAutoAssign($ticket);

        NotificationService::handleNewTicket($ticket);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load(['customer', 'technician', 'serviceCategory', 'photos']);

        NotificationService::markReadForTicket(Auth::user(), $ticket);

        $initialComments = $this->commentsForUser($ticket);

        return view('tickets.show', compact('ticket', 'initialComments'));
    }

    /**
     * JSON feed for live ticket conversation polling.
     */
    public function commentsFeed(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'comments' => $this->commentsForUser($ticket),
        ]);
    }

    /**
     * Upload additional device photos to an existing ticket.
     */
    public function storePhotos(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        if (Auth::user()->role !== 'customer' || $ticket->customer_id !== Auth::id()) {
            abort(403, 'Only the ticket owner can upload device photos.');
        }

        if ($ticket->isCancelled()) {
            abort(403, 'This ticket is cancelled.');
        }

        $request->validate([
            'photos' => 'required|array|min:1|max:8',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $result = $this->ticketPhotos->storeMany($ticket, $request->file('photos'));

        return $this->flashPhotoUploadResult($result);
    }

    /**
     * Show the form for editing the specified ticket.
     */
    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', compact('ticket'));
    }

    /**
     * Update the specified ticket in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $validated = $request->validated();

        if (array_key_exists('estimated_completion_at', $validated) && $validated['estimated_completion_at'] === '') {
            $validated['estimated_completion_at'] = null;
        }

        $ticket->update($validated);

        if ($ticket->wasChanged('status')) {
            NotificationService::notifyTicketStatusUpdated($ticket);
        }

        if ($ticket->wasChanged('estimated_completion_at')) {
            NotificationService::notifyTicketEtaUpdated($ticket);
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated successfully.');
    }

    /**
     * Update booking details while the ticket is still new (customer only).
     */
    public function updateDetails(UpdateTicketDetailsRequest $request, Ticket $ticket)
    {
        $this->authorize('updateDetails', $ticket);

        $ticket->update($request->validated());

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Booking details updated successfully.');
    }

    /**
     * Quick status update from the technician dashboard.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $ticket->update(['status' => $request->validated('status')]);

        if ($ticket->wasChanged('status')) {
            NotificationService::notifyTicketStatusUpdated($ticket);
        }

        return back()->with('success', 'Ticket status updated.');
    }

    /**
     * Remove the specified ticket from storage.
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket deleted successfully.');
    }

    /**
     * Add a comment to a ticket.
     */
    public function addComment(StoreTicketCommentRequest $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        if ($ticket->isCancelled()) {
            abort(403, 'This ticket is cancelled.');
        }

        $validated = $request->validated();

        $isInternalNote = $validated['is_internal_note'] ?? false;
        if (Auth::user()->role === 'customer') {
            $isInternalNote = false;
        }

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'comment_text' => $validated['comment_text'],
            'is_internal_note' => $isInternalNote,
        ]);

        NotificationService::notifyNewComment($comment);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Comment added successfully.',
                'comments' => $this->commentsForUser($ticket),
            ]);
        }

        return back()->with('success', 'Comment added successfully.');
    }

    /**
     * Cancel a ticket before repair work begins (customer only).
     */
    public function cancel(Ticket $ticket)
    {
        $this->authorize('cancel', $ticket);

        $ticket->update([
            'status' => TicketStatus::CLOSED,
            'cancelled_at' => now(),
            'estimated_completion_at' => null,
        ]);

        NotificationService::notifyTicketCancelled($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Your repair request has been cancelled.');
    }

    /**
     * Update estimated completion / pickup date.
     */
    public function updateEta(Request $request, Ticket $ticket)
    {
        $this->authorize('setEta', $ticket);

        if ($request->input('estimated_completion_at') === '') {
            $request->merge(['estimated_completion_at' => null]);
        }

        $validated = $request->validate([
            'estimated_completion_at' => 'nullable|date|after:now',
        ]);

        $ticket->update([
            'estimated_completion_at' => $validated['estimated_completion_at'] ?? null,
        ]);

        if ($ticket->wasChanged('estimated_completion_at')) {
            NotificationService::notifyTicketEtaUpdated($ticket);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Estimated completion date updated.',
                'estimated_completion_at' => optional($ticket->estimated_completion_at)?->toIso8601String(),
                'estimated_completion_label' => optional($ticket->estimated_completion_at)?->format('M j, Y g:i A'),
            ]);
        }

        return back()->with('success', 'Estimated completion date updated.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function commentsForUser(Ticket $ticket): array
    {
        $commentsQuery = $ticket->comments()->with('user')->oldest();

        if (Auth::user()->role === 'customer') {
            $commentsQuery->where('is_internal_note', false);
        }

        return TicketCommentResource::collection($commentsQuery->get())->resolve();
    }

    /**
     * @param  array{photos: array<int, mixed>, stored: int, skipped: int}  $result
     */
    private function flashPhotoUploadResult(array $result): RedirectResponse
    {
        if ($result['stored'] === 0) {
            return back()->with('error', 'No photos uploaded. This ticket already has the maximum of 8 photos.');
        }

        if ($result['skipped'] > 0) {
            return back()->with(
                'warning',
                "{$result['stored']} photo(s) uploaded. {$result['skipped']} skipped (8 photo limit per ticket)."
            );
        }

        return back()->with('success', 'Device photos uploaded successfully.');
    }
}
