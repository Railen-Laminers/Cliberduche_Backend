<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\User;
use App\Traits\LogsActivityAndSystem;
use App\Traits\PaginatesResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    use LogsActivityAndSystem;
    use PaginatesResources;

    /**
     * Get all projects (for admin) or user's projects (for client)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $query = Project::with(['client', 'creator', 'images'])
                ->orderBy('created_at', 'desc');
        } else {
            $query = Project::where('client_id', $user->id)
                ->with(['client', 'creator', 'images'])
                ->orderBy('created_at', 'desc');
        }

        $projects = $this->paginateResource($query, $request, 20);

        return response()->json([
            'projects' => $projects,
        ]);
    }

    /**
     * Create a new project (admin only)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'client_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:2100',
            'status' => 'nullable|in:pending,ongoing,completed,cancelled',
            'images.*' => 'sometimes|file|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Ensure the client is approved
        $client = User::find($request->client_id);
        if (!$client || !$client->isApproved()) {
            return response()->json([
                'message' => 'The selected client must be approved.'
            ], 422);
        }

        $project = Project::create([
            'client_id' => $request->client_id,
            'created_by' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'location' => $request->location,
            'year' => $request->year,
            'status' => $request->status ?? 'pending',
        ]);

        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $imagePath = $image->store('project_images', 'public');

                ProjectImage::create([
                    'project_id' => $project->id,
                    'image_path' => $imagePath,
                    'sort_order' => $index,
                ]);
            }
        }

        // Log activity with names
        ActivityLog::log($request->user(), 'project_created', "Created project: {$project->name}", [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
            ],
            'created_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        // Notify client
        Notification::create([
            'user_id' => $request->client_id,
            'type' => 'project',
            'title' => 'New Project Assigned',
            'content' => "A new project '{$project->name}' has been assigned to you.",
            'data' => ['project_id' => $project->id],
        ]);

        return response()->json([
            'message' => 'Project created successfully!',
            'project' => $project->load(['client', 'creator', 'images']),
        ], 201);
    }

    /**
     * Get single project
     */
    public function show(Request $request, $id)
    {
        $project = Project::with(['client', 'creator', 'images'])
            ->findOrFail($id);

        // Check authorization
        $user = $request->user();
        if (!$user->isAdmin() && $project->client_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'project' => $project,
        ]);
    }

    /**
     * Update project (admin only)
     */
    public function update(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project = Project::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:5000',
            'location' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:2100',
            'status' => 'sometimes|in:pending,ongoing,completed,cancelled',
        ]);

        // Capture original values for fields that may be updated
        $original = $project->only(['name', 'description', 'location', 'year', 'status']);

        $oldStatus = $project->status;
        $project->update($request->only(['name', 'description', 'location', 'year', 'status']));

        // Build changes array for scalar fields that actually changed
        $changes = [];
        $updatedFields = $request->only(['name', 'description', 'location', 'year', 'status']);
        foreach ($updatedFields as $field => $newValue) {
            if (array_key_exists($field, $original) && $original[$field] != $newValue) {
                $changes[$field] = [
                    'old' => $original[$field],
                    'new' => $newValue,
                ];
            }
        }

        // Log activity with detailed changes and names
        ActivityLog::log(
            $request->user(),
            'project_updated',
            "Updated project: {$project->name}",
            [
                'changes' => $changes,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'updated_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        // Notify client if status changed
        if ($oldStatus !== $project->status) {
            Notification::create([
                'user_id' => $project->client_id,
                'type' => 'project',
                'title' => 'Project Status Updated',
                'content' => "Your project '{$project->name}' status has been updated to {$project->status}.",
                'data' => ['project_id' => $project->id],
            ]);
        }

        return response()->json([
            'message' => 'Project updated successfully!',
            'project' => $project->fresh(['client', 'creator', 'images']),
        ]);
    }

    /**
     * Add images to project (admin only)
     */
    public function addImages(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project = Project::findOrFail($id);

        $request->validate([
            'images.*' => 'required|file|mimes:jpeg,png,jpg|max:5120',
        ]);

        $lastSortOrder = $project->images()->max('sort_order') ?? 0;

        foreach ($request->file('images') as $index => $image) {
            $imagePath = $image->store('project_images', 'public');

            ProjectImage::create([
                'project_id' => $project->id,
                'image_path' => $imagePath,
                'sort_order' => $lastSortOrder + $index + 1,
            ]);
        }

        // Log activity
        ActivityLog::log($request->user(), 'project_images_added', "Added images to project: {$project->name}", [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'added_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        return response()->json([
            'message' => 'Images added successfully!',
            'project' => $project->fresh(['client', 'creator', 'images']),
        ]);
    }

    /**
     * Delete project image (admin only)
     */
    public function deleteImage(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $image = ProjectImage::findOrFail($id);
        $project = $image->project; // get project for logging

        // Delete file
        Storage::disk('public')->delete($image->image_path);

        $image->delete();

        // Log activity with project name and image id
        ActivityLog::log(
            $request->user(),
            'project_image_deleted',
            "Deleted image from project: {$project->name}",
            [
                'image_id' => $image->id,
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'deleted_by' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->full_name,
                ],
            ]
        );

        return response()->json([
            'message' => 'Image deleted successfully!',
        ]);
    }

    /**
     * Delete project (admin only)
     */
    public function destroy(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $project = Project::findOrFail($id);

        // Delete all images
        foreach ($project->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $project->delete();

        // Log activity
        ActivityLog::log($request->user(), 'project_deleted', "Deleted project: {$project->name}", [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'deleted_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        return response()->json([
            'message' => 'Project deleted successfully!',
        ]);
    }
}