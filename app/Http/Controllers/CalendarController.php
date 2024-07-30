<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }

    public function eventRead() 
    {
        $userId = Auth::id();
        $event = Event::join('users', 'events.user_id', '=', 'users.id')
                    ->select('events.*', 'events.id  as event_id')
                    ->where('events.user_id', '=',  $userId)
                    ->orderBy('events.id', 'desc')
                    ->get();
        return view('calendar.eventList', compact('event'));
    }
 
    public function eventShow() 
    {
        $events = Event::all();
        $eventData = [];
 
        foreach ($events as $event) {
            $eventData[] = [
                'title' => $event->title,
                'start' => $event->start, 
                'end' => $event->end,
            ];
        }
        return response()->json($eventData);
    }
 
    public function eventCreate(Request $request) 
    {
        $request->validate([
            'title' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
            'user_id' => 'required',
        ]);
        try {
            Event::create([
                'title' => $request->input('title'),
                'start' => $request->input('start'),
                'end' => $request->input('end'),
                'user_id' => $request->input('user_id'),
                'remember_token' => Str::random(60),
            ]);
            return redirect()->back()->with('success', 'Event stored successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to store Event!');
        }
    }
 
    public function eventEdit($id) {
        $event = Event::findOrFail($id);
 
        return view('calendar.eventList', compact('event'));
    }
 
    public function eventUpdate(Request $request) 
    {
        $request->validate([
            'id' => 'required',
            'title' => 'required',
            'start' => 'required|date',
            'end' => 'required|date',
        ]);
 
        try {
            $event = Event::findOrFail($request->input('id'));
 
            $event->update([
                'title' => $request->input('title'),
                'start' => $request->input('start'),
                'end' => $request->input('end'),
            ]);
 
            return redirect()->route('eventRead')->with('success', 'Event updated successfully!');
        } catch (\Exception $e) {
            return redirect()->route('eventRead')->with('error', 'Failed to update Event!');
        }
    }
 
    public function eventDelete($id)
    {
        $event = Event::find($id);
        $event->delete();
 
        return response()->json([
            'status'=>200,
            'message'=>'Deleted Successfully',
        ]);
    }
}
