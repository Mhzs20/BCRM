<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Salon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, $salonId)
    {
        $salon = Salon::findOrFail($salonId);
        $isRead = $request->query('is_read');
        $notifications = Notification::with(['salons' => function ($q) use ($salonId) {
            $q->where('salon_id', $salonId);
        }])->where('created_at', '>', $salon->created_at)->latest()->get();

        // Add is_read status to each notification
        $notifications->transform(function ($notification) use ($salonId) {
            $pivot = $notification->salons->first();
            $notification->is_read = $pivot ? $pivot->pivot->is_read : false;
            unset($notification->salons); // Remove salons relation from response
            return $notification;
        });

        // Filter by is_read if provided
        if (!is_null($isRead)) {
            $isReadBool = filter_var($isRead, FILTER_VALIDATE_BOOLEAN);
            $notifications = $notifications->filter(function ($notification) use ($isReadBool) {
                return $notification->is_read === $isReadBool;
            })->values();
        }

        return response()->json($notifications);
    }

    public function updateReadStatus(Request $request, $salonId, $id)
    {
        $isRead = $request->boolean('is_read', true);
        $notification = Notification::findOrFail($id);
        $notification->salons()->syncWithoutDetaching([$salonId => ['is_read' => $isRead]]);
        return response()->json(['success' => true]);
    }
}
