<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Exception;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class AppointmentAvailabilityService
{
    /**
     * Generate available time slots between two dates.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function generateSlots(Carbon $startDate, Carbon $endDate)
    {
        $slots = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeek; // 1 (Mon) - 7 (Sun)
            Log::info("Checking date: {$date->toDateString()} (dayOfWeek: {$dayOfWeek})");

            $schedule = Schedule::where('day_of_week', $dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                Log::info("No active schedule for day {$dayOfWeek}");
                continue; // no work scheduled this day
            }

            Log::info("Schedule found: " . json_encode($schedule->toArray()));

            // Check for exception overriding this whole day
            $exception = Exception::where('exception_date', $date->toDateString())->first();
            if ($exception && !$exception->is_available) {
                Log::info("Exception blocking this day: " . $exception->toJson());
                continue; // whole day blocked
            }

            // Extract only the time part from schedule times
            $scheduleStart = $schedule->start_time instanceof Carbon
                ? $schedule->start_time->toTimeString()
                : $schedule->start_time;

            $scheduleEnd = $schedule->end_time instanceof Carbon
                ? $schedule->end_time->toTimeString()
                : $schedule->end_time;

            $start = Carbon::parse($date->toDateString() . ' ' . $scheduleStart);
            $end = Carbon::parse($date->toDateString() . ' ' . $scheduleEnd);

            $duration = $schedule->slot_duration;
            $buffer = $schedule->buffer;

            // If exception provides a specific time range, use that instead
            if ($exception && $exception->start_time && $exception->end_time) {
                $exceptionStart = $exception->start_time instanceof Carbon
                    ? $exception->start_time->toTimeString()
                    : $exception->start_time;

                $exceptionEnd = $exception->end_time instanceof Carbon
                    ? $exception->end_time->toTimeString()
                    : $exception->end_time;

                $start = Carbon::parse($date->toDateString() . ' ' . $exceptionStart);
                $end = Carbon::parse($date->toDateString() . ' ' . $exceptionEnd);
            }

            // Get booked appointments for this day (pending or confirmed)
            $booked = Appointment::whereDate('appointment_date', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('appointment_time')
                ->get()
                ->map(function ($apt) use ($date) {
                    // Extract just the time part (H:i format)
                    $timeString = $apt->appointment_time instanceof Carbon
                        ? $apt->appointment_time->toTimeString()
                        : $apt->appointment_time;

                    $start = Carbon::parse($date->toDateString() . ' ' . $timeString);
                    return [
                        'start' => $start,
                        'end' => $start->copy()->addMinutes($apt->duration ?? 30),
                    ];
                });

            // Generate slots
            $current = $start->copy();
            while ($current->lt($end)) {
                $slotEnd = $current->copy()->addMinutes($duration);
                if ($slotEnd->gt($end)) {
                    break; // slot would exceed working hours
                }

                // Check if slot overlaps any booked appointment (including buffer)
                $overlaps = $booked->contains(function ($b) use ($current, $slotEnd, $buffer) {
                    $bufferedStart = $current->copy()->subMinutes($buffer);
                    $bufferedEnd = $slotEnd->copy()->addMinutes($buffer);
                    return $b['start']->between($bufferedStart, $bufferedEnd) ||
                        $b['end']->between($bufferedStart, $bufferedEnd) ||
                        ($b['start'] <= $bufferedStart && $b['end'] >= $bufferedEnd);
                });

                if (!$overlaps) {
                    $slots[] = [
                        'date' => $date->toDateString(),
                        'time' => $current->format('H:i'),
                        'end_time' => $slotEnd->format('H:i'),
                    ];
                }

                $current->addMinutes($duration + $buffer);
            }
        }

        // Filter out slots that are already in the past (using app timezone)
        $now = Carbon::now();
        return collect($slots)->filter(function ($slot) use ($now) {
            $slotDateTime = Carbon::parse($slot['date'] . ' ' . $slot['time']);
            return $slotDateTime->isFuture();
        })->values()->all();
    }
}