<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use App\Models\Schedule;
use App\Services\AppointmentAvailabilityService;
use App\Traits\PaginatesResources;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    use PaginatesResources;

    /**
     * Ensure the user is either an admin or an approved client.
     */
    private function ensureClientApproved($user)
    {
        if (!$user->isAdmin() && !$user->isApproved()) {
            abort(403, 'Your account must be approved to perform this action.');
        }
    }

    /**
     * Get all appointments (for admin) or user's appointments (for client)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $this->ensureClientApproved($user);

        if ($user->isAdmin()) {
            $query = Appointment::with(['client', 'creator']);
        } else {
            $query = Appointment::where('client_id', $user->id)
                ->with(['client', 'creator']);
        }

        // Apply filters
        if ($request->has('date') && $request->date) {
            $query->whereDate('appointment_date', $request->date);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('service_type') && $request->service_type) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('firstname', 'LIKE', "%{$search}%")
                            ->orWhere('lastname', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Always order by date & time desc
        $query->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc');

        $appointments = $this->paginateResource($query, $request, 20);

        return response()->json([
            'appointments' => $appointments,
        ]);
    }

    /**
     * Create a new appointment
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->ensureClientApproved($user);

        $request->validate([
            'service_type' => 'required|string|max:255',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Additional check: requested datetime must be in the future
        $requestedDateTime = Carbon::parse($request->appointment_date . ' ' . $request->appointment_time);
        if ($requestedDateTime->isPast()) {
            return response()->json(['message' => 'Cannot book an appointment in the past.'], 422);
        }

        $clientId = $user->isAdmin()
            ? $request->client_id
            : $user->id;

        if (!$clientId) {
            return response()->json(['message' => 'Client ID is required'], 422);
        }

        // Admin only: validate client exists
        if ($user->isAdmin()) {
            $client = User::where('id', $clientId)->where('role', 'client')->first();
            if (!$client) {
                return response()->json(['message' => 'Selected client does not exist or is not a client.'], 422);
            }
        }

        // Non-admin: enforce one active appointment
        if (!$user->isAdmin()) {
            $activeExists = Appointment::where('client_id', $clientId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($activeExists) {
                return response()->json([
                    'message' => 'You already have an active appointment. Please complete or cancel it before booking a new one.'
                ], 422);
            }
        }

        // Check availability (skip for admin if they want to override, but we can still warn)
        $service = new AppointmentAvailabilityService();
        $slots = $service->generateSlots(
            Carbon::parse($request->appointment_date),
            Carbon::parse($request->appointment_date)
        );

        $isAvailable = collect($slots)->contains(function ($slot) use ($request) {
            return $slot['date'] === $request->appointment_date &&
                $slot['time'] === $request->appointment_time;
        });

        if (!$isAvailable && !$user->isAdmin()) {
            return response()->json(['message' => 'Selected time slot is no longer available.'], 422);
        }

        // Get duration from schedule for that day
        $dayOfWeek = Carbon::parse($request->appointment_date)->dayOfWeek;
        $schedule = Schedule::where('day_of_week', $dayOfWeek)->first();
        $duration = $schedule->slot_duration ?? 30; // fallback

        // Create appointment with status 'confirmed' for clients, 'pending' for admins
        $appointment = Appointment::create([
            'client_id' => $clientId,
            'created_by' => $user->id,
            'service_type' => $request->service_type,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'duration' => $duration,
            'notes' => $request->notes,
            'status' => $user->isAdmin() ? 'pending' : 'confirmed',
        ]);

        // Get client and creator for logging
        $client = User::find($clientId);
        $creator = $user;

        // Enhanced log: include appointment details with names
        ActivityLog::log(
            $user,
            'appointment_created',
            "Created an appointment (ID: {$appointment->id})",
            [
                'appointment_id' => $appointment->id,
                'client' => [
                    'id' => $clientId,
                    'name' => $client->full_name,
                ],
                'service_type' => $appointment->service_type,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'created_by' => [
                    'id' => $creator->id,
                    'name' => $creator->full_name,
                ],
            ]
        );

        // Notify client
        if ($client) {
            Notification::create([
                'user_id' => $client->id,
                'type' => 'appointment',
                'title' => 'New Appointment',
                'content' => $user->isAdmin()
                    ? "Your appointment for {$request->service_type} has been scheduled and is pending confirmation."
                    : "Your appointment for {$request->service_type} has been confirmed.",
                'data' => ['appointment_id' => $appointment->id],
            ]);
        }

        // Notify all admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'appointment',
                'title' => 'New Appointment Created',
                'content' => "A new appointment has been booked by {$appointment->client->full_name}.",
                'data' => ['appointment_id' => $appointment->id],
            ]);
        }

        return response()->json([
            'message' => 'Appointment created successfully!',
            'appointment' => $appointment->load(['client', 'creator']),
        ], 201);
    }

    /**
     * Get single appointment
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $this->ensureClientApproved($user);

        $appointment = Appointment::with(['client', 'creator'])->findOrFail($id);

        if (!$user->isAdmin() && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'appointment' => $appointment,
        ]);
    }

    /**
     * Update appointment status (admin only)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string|max:1000',
        ]);

        $appointment = Appointment::findOrFail($id);
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // If marking as completed, ensure the appointment time has passed
        if ($request->status === 'completed') {
            $appointmentDateTime = Carbon::parse($appointment->appointment_date)
                ->setTimeFromTimeString($appointment->appointment_time);
            if ($appointmentDateTime->isFuture()) {
                return response()->json([
                    'message' => 'Cannot mark an appointment as completed before its scheduled time.'
                ], 422);
            }
        }

        $oldStatus = $appointment->status;
        $appointment->update([
            'status' => $request->status,
            'cancellation_reason' => $request->cancellation_reason ?? null,
        ]);

        // Enhanced log: include old and new status, and who updated
        ActivityLog::log(
            $user,
            'appointment_updated',
            "Updated appointment ID: {$id} status from {$oldStatus} to {$request->status}",
            [
                'appointment_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
                'cancellation_reason' => $request->cancellation_reason,
                'updated_by' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ],
            ]
        );

        $notificationTitle = match ($request->status) {
            'confirmed' => 'Appointment Confirmed',
            'cancelled' => 'Appointment Cancelled',
            'completed' => 'Appointment Completed',
            default => 'Appointment Updated'
        };

        $notificationContent = match ($request->status) {
            'confirmed' => "Your appointment for {$appointment->service_type} has been confirmed.",
            'cancelled' => "Your appointment for {$appointment->service_type} has been cancelled. Reason: {$request->cancellation_reason}",
            'completed' => "Your appointment for {$appointment->service_type} has been completed.",
            default => "Your appointment has been updated."
        };

        Notification::create([
            'user_id' => $appointment->client_id,
            'type' => 'appointment',
            'title' => $notificationTitle,
            'content' => $notificationContent,
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully!',
            'appointment' => $appointment->fresh(['client', 'creator']),
        ]);
    }

    /**
     * Cancel appointment (client or admin)
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $appointment = Appointment::findOrFail($id);
        $user = $request->user();
        $this->ensureClientApproved($user);

        if (!$user->isAdmin() && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->cancel($request->reason);

        // Enhanced log: include reason and appointment details with names
        ActivityLog::log(
            $user,
            'appointment_cancelled',
            "Cancelled appointment ID: {$id}",
            [
                'appointment_id' => $id,
                'client' => [
                    'id' => $appointment->client_id,
                    'name' => $appointment->client->full_name,
                ],
                'service_type' => $appointment->service_type,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
                'reason' => $request->reason,
                'cancelled_by' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ],
            ]
        );

        Notification::create([
            'user_id' => $appointment->client_id,
            'type' => 'appointment',
            'title' => 'Appointment Cancelled',
            'content' => "Your appointment for {$appointment->service_type} has been cancelled.",
            'data' => ['appointment_id' => $appointment->id],
        ]);

        return response()->json([
            'message' => 'Appointment cancelled successfully!',
            'appointment' => $appointment->fresh(['client', 'creator']),
        ]);
    }

    /**
     * Get available services (mock)
     */
    public function services()
    {
        $services = [
            'General Construction Consultation',
            'Project Management Discussion',
            'Design & Build Consultation',
            'Renovation Assessment',
            'Site Inspection',
            'Contract Discussion',
            'Other',
        ];

        return response()->json([
            'services' => $services,
        ]);
    }

    /**
     * Get available time slots for a given date range.
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $service = new AppointmentAvailabilityService();
        $slots = $service->generateSlots(
            Carbon::parse($request->start_date),
            Carbon::parse($request->end_date)
        );

        return response()->json(['slots' => $slots]);
    }
}