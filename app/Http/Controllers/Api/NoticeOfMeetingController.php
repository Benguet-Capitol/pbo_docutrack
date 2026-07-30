<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\NoticeOfMeeting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class NoticeOfMeetingController extends Controller
{
    /**
     * CRUD admin page under the Locator Chart module.
     */
    public function index(): Response
    {
        return Inertia::render('LocatorChart/NoticeOfMeetings/Index', [
            'meetings' => NoticeOfMeeting::with('employee:id,name,designation')
                ->orderByDesc('date')
                ->orderBy('time')
                ->get(),
            'employees' => Employee::select('id', 'name', 'designation')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $meeting = NoticeOfMeeting::create($validated);

        if ($request->wantsJson()) {
            return response()->json($meeting);
        }

        return back()->with('success', 'Notice of Meeting added.');
    }

    public function update(Request $request, NoticeOfMeeting $noticeOfMeeting)
    {
        $validated = $this->validated($request);
        $noticeOfMeeting->update($validated);

        if ($request->wantsJson()) {
            return response()->json($noticeOfMeeting);
        }

        return back()->with('success', 'Notice of Meeting updated.');
    }

    public function destroy(Request $request, NoticeOfMeeting $noticeOfMeeting)
    {
        $noticeOfMeeting->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('success', 'Notice of Meeting deleted.');
    }

    /**
     * JSON feed consumed by the Locator Chart's fetch('/api/notice-of-meetings').
     * Returns the full set — the chart itself filters by the selected date
     * client-side, matching how leaves/travel-orders/pass-slips are fetched.
     */
    public function api(): JsonResponse
    {
        return response()->json(
            NoticeOfMeeting::select('id', 'employee_id', 'date', 'time', 'particulars')->get()
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'particulars' => ['required', 'string', 'max:255'],
        ]);
    }
}