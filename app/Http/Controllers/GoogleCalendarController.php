<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GoogleCalendarController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $calendarService
    ) {}

    public function index()
    {
        $connection = auth()->user()->googleCalendarConnection;

        return Inertia::render('GoogleCalendar/Index', [
            'connection' => $connection,
        ]);
    }

    public function connect()
    {
        $url = $this->calendarService->getAuthUrl();
        return Inertia::location($url);
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('google-calendar.index')
                ->with('error', 'Google Calendar connection was cancelled.');
        }

        try {
            $this->calendarService->handleCallback(auth()->user(), $request->code);

            return redirect()->route('google-calendar.index')
                ->with('success', 'Google Calendar connected successfully!');
        } catch (\Exception $e) {
            return redirect()->route('google-calendar.index')
                ->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $connection = auth()->user()->googleCalendarConnection;

        if ($connection) {
            $this->calendarService->disconnect($connection);
        }

        return back()->with('success', 'Google Calendar disconnected.');
    }

    public function sync()
    {
        $connection = auth()->user()->googleCalendarConnection;

        if (!$connection) {
            return back()->with('error', 'No Google Calendar connected.');
        }

        try {
            $count = $this->calendarService->syncEvents($connection);
            return back()->with('success', "Synced {$count} new event(s) from Google Calendar.");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
