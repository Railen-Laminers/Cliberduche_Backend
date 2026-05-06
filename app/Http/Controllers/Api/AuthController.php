<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationPendingMail;
use App\Mail\NewRegistrantAlertMail;
use App\Models\User;
use App\Models\ActivityLog;
use App\Traits\LogsActivityAndSystem;
use App\Traits\PaginatesResources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use LogsActivityAndSystem;
    use PaginatesResources;

    /**
     * Register a new client
     */
    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'required|string|max:20',
            'valid_id' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        // Handle valid_id upload
        $validIdPath = null;
        if ($request->hasFile('valid_id')) {
            $validIdPath = $request->file('valid_id')->store('valid_ids', 'public');
        }

        $user = User::create([
            'firstname' => $request->firstname,
            'middlename' => $request->middlename,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'contact_number' => $request->contact_number,
            'role' => 'client',
            'account_status' => 'pending',
            'valid_id_path' => $validIdPath,
        ]);

        // Log registration activity with user info
        ActivityLog::log($user, 'register', 'New client registration', [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
            ],
        ]);

        // Send welcome/pending email to client
        try {
            Mail::to($user->email)->send(new RegistrationPendingMail($user));
        } catch (\Exception $e) {
            $this->logError('Failed to send registration email', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);
        }

        // Send notification to admin
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            try {
                // Mail::to($admin->email)->send(new NewRegistrantAlertMail($user));
            } catch (\Exception $e) {
                $this->logError('Failed to send admin notification email', [
                    'error' => $e->getMessage(),
                    'admin_id' => $admin->id,
                    'new_user_id' => $user->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Registration successful! Please wait for admin approval before accessing full features.',
            'user' => $user,
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Log login activity (no extra metadata needed)
        ActivityLog::log($user, 'login', 'User logged in');

        // Revoke existing tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful!',
            'user' => $user,
            'token' => $token,
            'account_status' => $user->account_status,
            'rejection_reason' => $user->rejection_reason,
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Log logout activity
        ActivityLog::log($request->user(), 'logout', 'User logged out');

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully!',
        ]);
    }

    /**
     * Get current user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update user profile (for clients AND admins)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'firstname' => 'sometimes|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'sometimes|string|max:255',
            'contact_number' => 'sometimes|string|max:20',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'valid_id' => 'sometimes|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'profile_image' => 'sometimes|file|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['firstname', 'middlename', 'lastname', 'contact_number', 'email']);

        // Capture original values for scalar fields (for logging)
        $original = $user->only(array_keys($data));

        // Track which files were updated
        $filesUpdated = [];

        // Handle valid_id upload
        if ($request->hasFile('valid_id')) {
            // Delete old valid_id if exists
            if ($user->valid_id_path) {
                Storage::disk('public')->delete($user->valid_id_path);
            }
            $data['valid_id_path'] = $request->file('valid_id')->store('valid_ids', 'public');
            $filesUpdated[] = 'valid_id';

            // If user was rejected, reset to pending for re‑review (only on ID upload)
            if ($user->account_status === 'rejected') {
                $data['account_status'] = 'pending';
            }
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }
            $data['profile_image_path'] = $request->file('profile_image')->store('profile_images', 'public');
            $filesUpdated[] = 'profile_image';
        }

        $user->update($data);

        // Build changes array for scalar fields (skip file paths)
        $changes = [];
        foreach ($data as $field => $newValue) {
            if (in_array($field, ['valid_id_path', 'profile_image_path'])) {
                continue;
            }
            if (array_key_exists($field, $original) && $original[$field] != $newValue) {
                $changes[$field] = [
                    'old' => $original[$field],
                    'new' => $newValue,
                ];
            }
        }

        // Also handle account_status change if it was reset
        if (isset($data['account_status']) && $data['account_status'] !== $user->getOriginal('account_status')) {
            $changes['account_status'] = [
                'old' => $user->getOriginal('account_status'),
                'new' => $data['account_status'],
            ];
        }

        // Prepare metadata
        $metadata = [
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
            ],
        ];

        if (!empty($changes)) {
            $metadata['changes'] = $changes;
        }

        if (!empty($filesUpdated)) {
            $metadata['files_updated'] = $filesUpdated;
        }

        ActivityLog::log($user, 'profile_update', 'Profile updated', $metadata);

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Get user's own activity logs
     */
    public function getMyActivityLogs(Request $request)
    {
        $query = ActivityLog::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        $logs = $this->paginateResource($query, $request, 50);

        return response()->json([
            'activity_logs' => $logs,
        ]);
    }
}