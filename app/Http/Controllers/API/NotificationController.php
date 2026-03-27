<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /api/my/notifications
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()->paginate($request->get('per_page', 15));

        $unreadCount = Notification::where('user_id', $request->user()->id)->unread()->count();

        return response()->json(['success' => true, 'unread_count' => $unreadCount, 'data' => $notifications]);
    }

    // POST /api/notifications
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'nullable|exists:users,id',
            'booking_id' => 'nullable|exists:bookings,booking_id',
            'channel'    => 'required|in:in_app,email,sms',
            'title'      => 'required|string|max:255',
            'message'    => 'required|string',
            'type'       => 'nullable|in:booking_confirmation,payment_success,payment_failed,trip_reminder,cancellation,general',
        ]);

        $notification = Notification::create(array_merge($data, ['status' => 'sent', 'sent_at' => now()]));

        return response()->json(['success' => true, 'message' => 'Notification sent', 'data' => $notification], 201);
    }

    // POST /api/my/notifications/{id}/mark-read
    public function markRead(Request $request, int $id): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)->findOrFail($id)->update(['status' => 'read']);
        return response()->json(['success' => true, 'message' => 'Marked as read']);
    }

    // POST /api/my/notifications/mark-all-read
    public function markAllRead(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)->unread()->update(['status' => 'read']);
        return response()->json(['success' => true, 'message' => "{$count} notifications marked as read"]);
    }

    // GET /api/notifications/logs
    public function logs(Request $request): JsonResponse
    {
        $logs = Notification::with('booking')
            ->when($request->type,    fn($q) => $q->byType($request->type))
            ->when($request->channel, fn($q) => $q->byChannel($request->channel))
            ->when($request->status,  fn($q) => $q->where('status', $request->status))
            ->latest()->paginate($request->get('per_page', 20));

        return response()->json(['success' => true, 'data' => $logs]);
    }

    // GET /api/notifications/templates
    public function templates(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => NotificationTemplate::all()]);
    }

    // POST /api/notifications/templates
// POST /api/notifications/templates
    public function storeTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100|unique:notification_templates,name',
            'type' => 'required|in:booking_confirmation,payment_success,payment_failed,trip_reminder,cancellation,schedule_update,general',
            'subject'   => 'nullable|string|max:255',
            'body'      => 'required|string',
            'channel'   => 'required|in:in_app,email,sms',
            'is_active' => 'boolean',
        ]);

        $template = NotificationTemplate::create($data);
        return response()->json(['success' => true, 'message' => 'Template created', 'data' => $template], 201);
    }


    // PUT /api/notifications/templates/{id}
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $data = $request->validate([
            'name'      => "string|max:100|unique:notification_templates,name,{$id}",
            'type'      => 'in:booking_confirmation,payment_success,payment_failed,trip_reminder,cancellation,schedule_update,general',
            'subject'   => 'nullable|string|max:255',
            'body'      => 'string',
            'channel'   => 'in:in_app,email,sms',
            'is_active' => 'boolean',
        ]);

        $template->update($data);
        return response()->json(['success' => true, 'message' => 'Template updated', 'data' => $template->fresh()]);
    }


    // DELETE /api/notifications/templates/{id}
    public function destroyTemplate(int $id): JsonResponse
    {
        NotificationTemplate::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Template deleted']);
    }
}