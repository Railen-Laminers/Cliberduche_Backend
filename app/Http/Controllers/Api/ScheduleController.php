<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function index()
    {
        return Schedule::orderBy('day_of_week')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|between:0,6|unique:schedules,day_of_week',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5',
            'buffer' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $schedule = Schedule::create($validated);
        $dayName = $this->getDayName($schedule->day_of_week);

        // Log the creation with day name added
        ActivityLog::log(
            $request->user(),
            'schedule_created',
            "Created schedule for {$dayName}",
            [
                'schedule' => array_merge($schedule->toArray(), ['day_name' => $dayName]),
                'created_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json($schedule, 201);
    }

    public function show(Schedule $schedule)
    {
        return $schedule;
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'day_of_week' => [
                'sometimes',
                'integer',
                'between:0,6',
                Rule::unique('schedules')->ignore($schedule->id),
            ],
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'slot_duration' => 'sometimes|integer|min:5',
            'buffer' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $old = $schedule->toArray();
        $oldDayName = $this->getDayName($old['day_of_week']);
        $schedule->update($validated);
        $newDayName = $this->getDayName($schedule->day_of_week);

        // Log the update with day names
        ActivityLog::log(
            $request->user(),
            'schedule_updated',
            "Updated schedule from {$oldDayName} to {$newDayName}",
            [
                'old' => array_merge($old, ['day_name' => $oldDayName]),
                'new' => array_merge($schedule->toArray(), ['day_name' => $newDayName]),
                'updated_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json($schedule);
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        $dayName = $this->getDayName($schedule->day_of_week);
        $scheduleData = $schedule->toArray();
        $schedule->delete();

        // Log the deletion with day name
        ActivityLog::log(
            $request->user(),
            'schedule_deleted',
            "Deleted schedule for {$dayName}",
            [
                'schedule' => array_merge($scheduleData, ['day_name' => $dayName]),
                'deleted_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json(null, 204);
    }

    /**
     * Helper to convert day_of_week integer to day name.
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        return $days[$dayOfWeek] ?? 'Unknown';
    }
}