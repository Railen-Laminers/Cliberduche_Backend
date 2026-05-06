<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exception;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ExceptionController extends Controller
{
    public function index()
    {
        return Exception::orderBy('exception_date', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exception_date' => 'required|date|unique:exceptions,exception_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
            'is_available' => 'boolean',
        ]);

        $exception = Exception::create($validated);

        // Log the creation with creator info
        ActivityLog::log(
            $request->user(),
            'exception_created',
            "Created exception for {$exception->exception_date}",
            [
                'exception' => $exception->toArray(),
                'created_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json($exception, 201);
    }

    public function show(Exception $exception)
    {
        return $exception;
    }

    public function update(Request $request, Exception $exception)
    {
        $validated = $request->validate([
            'exception_date' => 'sometimes|date|unique:exceptions,exception_date,' . $exception->id,
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:255',
            'is_available' => 'boolean',
        ]);

        $old = $exception->toArray();
        $exception->update($validated);

        // Log the update with updater info
        ActivityLog::log(
            $request->user(),
            'exception_updated',
            "Updated exception for {$exception->exception_date}",
            [
                'old' => $old,
                'new' => $exception->toArray(),
                'updated_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json($exception);
    }

    public function destroy(Request $request, Exception $exception)
    {
        $exceptionDate = $exception->exception_date;
        $exceptionData = $exception->toArray();
        $exception->delete();

        // Log the deletion with deleter info
        ActivityLog::log(
            $request->user(),
            'exception_deleted',
            "Deleted exception for {$exceptionDate}",
            [
                'exception' => $exceptionData,
                'deleted_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json(null, 204);
    }
}