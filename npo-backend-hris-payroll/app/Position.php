<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Position extends Model
{
    public const VACANCY_FILLED = 0;
    public const VACANCY_UNFILLED = 1;

    protected $table = 'positions';
    protected $fillable = ['position_name', 'department_id', 'salary_grade', 'id',  'item_number'];
    protected $casts = [
        'status' => 'boolean',
        'vacancy' => 'boolean'
    ];
    protected $appends = [];
    protected $dates = ['created_at', 'updated_at'];


    public function department()
    {
        return $this->belongsTo('App\Department');
    }
}
