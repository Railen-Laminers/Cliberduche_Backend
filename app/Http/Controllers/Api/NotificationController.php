<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\PaginatesResources;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use PaginatesResources;

    /**
     * Get all notifications for the user
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        $notifications = $this->paginateResource($query, $request, 20);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get unread notifications
     */
    public function unread(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);
            
        $notification->markAsRead();
        
        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();
            
        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Request $request, $id)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->findOrFail($id);
            
        $notification->delete();
        
        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }
}

