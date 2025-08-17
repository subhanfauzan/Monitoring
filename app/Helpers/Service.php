<?php

namespace App\Helpers;

use Carbon\Carbon;

class Service
{
    public static function convertDate($dates)
    {
        // Convert the input to an integer and separate the fractional part
        $date = (float) $dates;

        // Base date in Carbon (Excel starts from 1899-12-30)
        $baseDate = Carbon::create(1899, 12, 30);

        // Add whole days to the base date
        $convertedDate = $baseDate->addDays(floor($date));

        // Add fractional part (time) in seconds
        $fractionalSeconds = ($date - floor($date)) * 86400; // 86400 seconds in a day
        $convertedDate->addSeconds($fractionalSeconds);

        // Return formatted date
        return $convertedDate->format('Y-m-d H:i:s');
    }
}
