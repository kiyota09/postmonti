<?php

namespace App\Http\Controllers\HRM\Employee;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use App\Models\Setting;
use Inertia\Inertia; // <-- Added Inertia Facade
use App\Http\Controllers\users\AttendanceController; // <-- For employee attendance toggle


class GeofenceController extends Controller
{
    private function getDefaultLocation()
    {
        return [
            'latitude' => 14.4297, 
            'longitude' => 120.9367, 
            'radius' => 200
        ];
    }

    public function index()
    {
        $locationSetting = Setting::where('key', 'office_location')->first();
        
        return Inertia::render('Admin/Setmapgeolocation', [
            'settings' => $locationSetting ? $locationSetting->value : $this->getDefaultLocation()
        ]);
    }

    public function update(Request $request) 
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:10',
        ]);

        Setting::updateOrCreate(
            ['key' => 'office_location'], 
            ['value' => $request->only('latitude', 'longitude', 'radius')]
        );

        return back()->with('success', 'Geofence updated successfully.');
    }

    public function geofence()
    {
        $locationSetting = Setting::where('key', 'office_location')->first();
        
        return Inertia::render('Dashboard/HRM/Manager/geofence', [
            'settings' => $locationSetting ? $locationSetting->value : $this->getDefaultLocation()
        ]);
    }

    public function employeeAttendance()
    {
        $locationSetting = Setting::where('key', 'office_location')->first();
        
        return Inertia::render('Employee/Attendance', [
            'today_log' => null,
            'assigned_shift' => null,
            'history' => [], 
            'geofence' => $locationSetting ? $locationSetting->value : $this->getDefaultLocation()
        ]);
    }
}














