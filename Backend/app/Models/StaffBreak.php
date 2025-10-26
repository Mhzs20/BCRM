<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffBreak extends Model
{
    protected $fillable = ['staff_id', 'weekday', 'start_time', 'end_time'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
