<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use App\Traits\LogsActivityAndSystem;
use App\Traits\PaginatesResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    use LogsActivityAndSystem;
    use PaginatesResources;

    /**
     * Get all pending clients (admin only)
     */
    public function getPendingClients(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingClients = User::where('role', 'client')
            ->where('account_status', 'pending')
            ->get();

        return response()->json([
            'pending_clients' => $pendingClients,
        ]);
    }

    /**
     * Get all clients (admin only)
     */
    public function getAllClients(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $search = $request->query('search');

        $clientsQuery = User::where('role', 'client')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $clientsQuery->where(function ($query) use ($search) {
                $query->where('firstname', 'like', "%{$search}%")
                    ->orWhere('middlename', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        $clients = $clientsQuery->get();

        return response()->json([
            'clients' => $clients,
        ]);
    }

    /**
     * Approve client (admin only)
     */
    public function approveClient(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client = User::where('role', 'client')->findOrFail($id);

        $client->update([
            'account_status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        ActivityLog::log($request->user(), 'client_approved', "Approved client: {$client->full_name}", [
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
            ],
            'approved_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        try {
            \Mail::to($client->email)->send(new \App\Mail\AccountApprovedMail($client));
        } catch (\Exception $e) {
            $this->logError('Failed to send approval email', [
                'error' => $e->getMessage(),
                'client_id' => $client->id,
            ]);
        }

        return response()->json([
            'message' => 'Client approved successfully!',
            'client' => $client,
        ]);
    }

    /**
     * Reject client (admin only)
     */
    public function rejectClient(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $client = User::where('role', 'client')->findOrFail($id);

        $client->update([
            'account_status' => 'rejected',
            'rejection_reason' => $request->reason,
            'rejected_at' => now(),
            'approved_at' => null,
        ]);

        ActivityLog::log($request->user(), 'client_rejected', "Rejected client: {$client->full_name}", [
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
            ],
            'reason' => $request->reason,
            'rejected_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        try {
            \Mail::to($client->email)->send(new \App\Mail\AccountRejectedMail($client));
        } catch (\Exception $e) {
            $this->logError('Failed to send rejection email', [
                'error' => $e->getMessage(),
                'client_id' => $client->id,
            ]);
        }

        return response()->json([
            'message' => 'Client rejected successfully!',
            'client' => $client,
        ]);
    }

    /**
     * Edit client (admin only)
     */
    public function editClient(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->logInfo('editClient raw input', $request->all());
        $this->logInfo('editClient files', $request->allFiles());

        $client = User::where('role', 'client')->findOrFail($id);

        $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $client->id,
            'contact_number' => 'sometimes|string|max:20',
            'valid_id' => 'sometimes|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'profile_image' => 'sometimes|file|mimes:jpeg,png,jpg|max:2048',
            'account_status' => 'sometimes|in:pending,approved,rejected',
        ]);

        $data = $request->only([
            'firstname',
            'middlename',
            'lastname',
            'email',
            'contact_number'
        ]);

        // Capture original values for scalar fields we are about to update
        $original = $client->only(array_keys($data));

        if ($request->has('account_status')) {
            $data['account_status'] = $request->account_status;
            $original['account_status'] = $client->account_status; // track status change

            switch ($request->account_status) {
                case 'approved':
                    $data['approved_at'] = now();
                    $data['rejected_at'] = null;
                    break;
                case 'rejected':
                    $data['rejected_at'] = now();
                    $data['approved_at'] = null;
                    break;
                case 'pending':
                    $data['approved_at'] = null;
                    $data['rejected_at'] = null;
                    break;
            }
        }

        // Track which files were updated
        $filesUpdated = [];

        // Handle file uploads
        if ($request->hasFile('valid_id')) {
            if ($client->valid_id_path) {
                Storage::disk('public')->delete($client->valid_id_path);
            }
            $data['valid_id_path'] = $request->file('valid_id')->store('valid_ids', 'public');
            $filesUpdated[] = 'valid_id';
        }

        if ($request->hasFile('profile_image')) {
            if ($client->profile_image_path) {
                Storage::disk('public')->delete($client->profile_image_path);
            }
            $data['profile_image_path'] = $request->file('profile_image')->store('profile_images', 'public');
            $filesUpdated[] = 'profile_image';
        }

        $client->update($data);

        // Build changes array: only scalar fields that actually changed
        $changes = [];
        foreach ($data as $field => $newValue) {
            // Skip file paths – we don't need to log those
            if (in_array($field, ['valid_id_path', 'profile_image_path'])) {
                continue;
            }
            // Check if we have an original value and it changed
            if (array_key_exists($field, $original) && $original[$field] != $newValue) {
                $changes[$field] = [
                    'old' => $original[$field],
                    'new' => $newValue,
                ];
            }
        }

        // Prepare metadata
        $metadata = [
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
            ],
            'edited_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ];

        if (!empty($changes)) {
            $metadata['changes'] = $changes;
        }

        if (!empty($filesUpdated)) {
            $metadata['files_updated'] = $filesUpdated;
        }

        ActivityLog::log($request->user(), 'client_edited', "Edited client: {$client->full_name}", $metadata);

        return response()->json([
            'message' => 'Client updated successfully!',
            'client' => $client->fresh(),
        ]);
    }

    /**
     * Delete client (admin only)
     */
    public function deleteClient(Request $request, $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client = User::where('role', 'client')->findOrFail($id);

        // Delete associated files
        if ($client->valid_id_path) {
            Storage::disk('public')->delete($client->valid_id_path);
        }
        if ($client->profile_image_path) {
            Storage::disk('public')->delete($client->profile_image_path);
        }

        ActivityLog::log($request->user(), 'client_deleted', "Deleted client: {$client->full_name}", [
            'client' => [
                'id' => $client->id,
                'name' => $client->full_name,
            ],
            'deleted_by' => [
                'id' => $request->user()->id,
                'name' => $request->user()->full_name,
            ],
        ]);

        $client->delete();

        return response()->json([
            'message' => 'Client deleted successfully!',
        ]);
    }

    /**
     * Get activity logs (admin only) with optional filters and search.
     * 
     * Query parameters:
     * - filter: 'my_actions' | 'client_actions' | 'all' (default 'all')
     * - user_id: specific user ID (optional, overrides filter if provided)
     * - search: search by client name or ID (only applied when filter=client_actions)
     */
    public function getActivityLogs(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by specific user ID if provided
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        // Otherwise apply role-based filter
        elseif ($request->has('filter')) {
            switch ($request->filter) {
                case 'my_actions':
                    $query->where('user_id', $request->user()->id);
                    break;
                case 'client_actions':
                    $query->whereHas('user', function ($q) {
                        $q->where('role', 'client');
                    });
                    break;
                // 'all' – no filter
            }
        }

        // Apply search if provided (search in user's name or ID)
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('firstname', 'LIKE', "%{$search}%")
                        ->orWhere('lastname', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(firstname, ' ', lastname) LIKE ?", ["%{$search}%"]); // full name search
                    if (is_numeric($search)) {
                        $subQ->orWhere('id', $search);
                    }
                });
            });
        }

        $logs = $this->paginateResource($query, $request, 50);

        return response()->json([
            'activity_logs' => $logs,
        ]);
    }

    /**
     * Get all admins (accessible to any authenticated user)
     */
    public function getAdmins(Request $request)
    {
        $admins = User::where('role', 'admin')->get();
        return response()->json($admins);
    }
}