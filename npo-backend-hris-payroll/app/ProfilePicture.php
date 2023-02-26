<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProfilePicture extends Model
{
    protected $table = 'profile_picture';
    protected $fillable = ['employee_id', 'file_location', 'file_type', 'file_name'];
}
