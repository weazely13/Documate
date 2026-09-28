<?php

use App\Models\Semester;
use App\Models\Setting;
use Carbon\Carbon;

if (!function_exists('systemSetting')) {
    function systemSetting()
    {
        return Setting::orderByDesc('id')->first();
    }
}

if (!function_exists('currentSemester')) {
    function currentSemester()
    {
        return Semester::current()?->semester_label;
    }
}

if (!function_exists('currentAcademicYear')) {
    function currentAcademicYear()
    {
        return Semester::current()?->school_year;
    }
}

if (!function_exists('isVerificationOpen')) {
    function isVerificationOpen()
    {
        return systemSetting()?->isOpen() ?? false;
    }
}