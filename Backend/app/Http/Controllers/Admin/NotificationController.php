<?php

namespace App\Http\Controllers\Admin;

use App\Models\Salon;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::latest()->paginate(10);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $notification = Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'is_important' => $request->has('is_important'),
        ]);

        // Attach notification to all salons
        $salons = Salon::all();
        foreach ($salons as $salon) {
            $notification->salons()->attach($salon->id, ['is_read' => false]);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', 'اعلان با موفقیت ایجاد شد.');
    }

    public function edit(Notification $notification)
    {
        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $notification->update([
            'title' => $request->title,
            'message' => $request->message,
            'is_important' => $request->has('is_important'),
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'اعلان با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'اعلان با موفقیت حذف شد.');
    }
}
