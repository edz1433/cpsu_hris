<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Campus;
use App\Models\Status;
use App\Models\Employee;
use App\Models\EventLog;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function getGuard()
    {
        if(\Auth::guard('web')->check()) {
            return 'web';
        } elseif(\Auth::guard('employee')->check()) {
            return 'employee';
        }
    }
    
    public function eventIndex(){
        $guard = $this->getGuard();
        $campus = Campus::all();
        $status = Status::all();
        return view("events.event-read", compact('guard', 'campus', 'status'));
    }

    public function eventShow() 
    {
        $events = Event::all();
        $events = Event::select('title', 'start', 'end', 'bg_color')
        ->where('event_stat', 1) // Optional: only active events
        ->get();

        return response()->json($events);
    }

    public function eventCreate(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'venue' => 'required',
            'start' => 'required|date',
            'end' => 'nullable|date', 
            'campus_id' => 'required',
            'emp_status' => 'required',
            'bg_color' => 'required', 
        ]);

        $event = Event::create([
            'title' => $request->input('title'),
            'venue' => $request->input('venue'),
            'start' => $request->input('start'),
            'end' => $request->input('end'),
            'campus_id' => $request->input('campus_id'),
            'emp_status' => $request->input('emp_status'),
            'bg_color' => $request->input('bg_color'),
            'remember_token' => Str::random(60),
            'event_stat' => 1,
        ]);

        $employeeQuery = Employee::query();

        if ($request->campus_id != 0) {
            $employeeQuery->where('camp_id', $request->campus_id);
        }

        if ($request->emp_status != 0) {
            $employeeQuery->where('emp_status', $request->emp_status);
        }

        $employees = $employeeQuery->pluck('emp_ID');

        foreach ($employees as $empid) {
            EventLog::create([
                'event_id' => $event->id,
                'empid' => $empid,
            ]);
        }

        \DB::commit();

        return redirect()->back()->with('success', 'Event stored successfully!');

    }

    public function showReport(){
        $guard = $this->getGuard();
        $events = Event::all();
        $campus = Campus::all();
        $status = Status::all();

        return view("events.report", compact('guard', 'events', 'campus', 'status'));
    }

    public function searchReport(Request $request){
        $request->validate([
            'eventid' => 'required',
            'campusid' => 'required',
            'statusid' => 'required',
        ]);

        $guard = $this->getGuard();
        $events = Event::all();
        $campus = Campus::all();
        $status = Status::all();

        $eventid = $request->input('eventid');
        $campusid = $request->input('campusid');
        $statusid = $request->input('statusid');

        return view("events.report", compact('guard', 'events', 'campus', 'status', 'eventid', 'campusid', 'statusid'));
    }
    
    public function reportGenrate(Request $request)
    {
        $eventid = $request->eventid;
        $campusid = $request->campusid;
        $statusid = $request->statusid;

        // dd($eventid, $campusid, $statusid);
    
        $events = EventLog::join('employees', 'event_logs.empid', '=', 'employees.emp_ID')
            ->join('campuses', 'employees.camp_id', '=', 'campuses.id')
            ->when($eventid, function ($query) use ($eventid) {
                return $query->where('event_logs.event_id', $eventid);
            })
            ->when($campusid != 0, function ($query) use ($campusid) {
                return $query->where('employees.camp_id', $campusid);
            })
            ->when($statusid != 0, function ($query) use ($statusid) {
                return $query->where('employees.emp_status', $statusid);
            })
            ->orderBy('event_logs.updated_at', 'desc')
            ->select(
                'employees.fname',
                'employees.lname',
                'employees.suffix',
                'employees.position',
                'employees.emp_status',
                'campuses.campus_name',
                'event_logs.updated_at',
                'event_logs.in',
                'event_logs.out'
            )
            ->get();
    
        $chunkedEvents = $events->chunk(35);
    
        $customPaper = [0, 0, 612, 792];
    
        $pdf = \PDF::loadView('events.report-generate', compact('chunkedEvents'))
            ->setPaper($customPaper, 'portrait')
            ->setOptions([
                'margin-top' => 10,
                'margin-right' => 10,
                'margin-bottom' => 30,
                'margin-left' => 10,
            ])
            ->setCallbacks([
                'before_render' => function ($domPdf) {
                    $canvas = $domPdf->getCanvas();
                    $canvas->page_text(10, 10, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, [0, 0, 0]);
                },
            ]);
    
        return $pdf->stream();
    }    

}
