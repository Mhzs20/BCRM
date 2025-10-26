<?php
namespace App\Http\Controllers\Traits;

use App\Models\StaffBreak;
use Carbon\Carbon;

trait StaffBreakTrait
{
    /**
     * @param int $staffId
     * @param string $date YYYY-MM-DD
     * @param string $startTime HH:MM
     * @param string $endTime HH:MM
     * @return array  
     */
    public function getStaffBreakConflicts($staffId, $date, $startTime, $endTime)
    {
        $carbonWeekday = Carbon::parse($date)->dayOfWeek; // 0=Sunday, 6=Saturday
        // mapping مشابه schedules: شنبه=0، یکشنبه=1، ... جمعه=6
        $weekday = $carbonWeekday == 6 ? 0 : $carbonWeekday + 1;
        $conflicts = StaffBreak::where('staff_id', $staffId)
            ->where('weekday', $weekday)
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->with('staff')
            ->get();

        return $conflicts->map(function ($break) {
            return [
                'type' => 'staff_break',
                'id' => $break->id,
                'staff_id' => $break->staff_id,
                'staff_name' => $break->staff->full_name ?? 'نامشخص',
                'weekday' => $break->weekday,
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
                'conflict_reason' => 'تداخل با زمان استراحت پرسنل',
            ];
        })->toArray();
    }

    /**
     * @param int $staffId
     * @param string $date YYYY-MM-DD
     * @param string $startTime HH:MM
     * @param string $endTime HH:MM
     * @return bool
     */
    public function isStaffBreakConflict($staffId, $date, $startTime, $endTime)
    {
        return !empty($this->getStaffBreakConflicts($staffId, $date, $startTime, $endTime));
    }
}
