<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $table = "tax";
    // table does not have created_at & updated_at columns
    public $timestamps = false;
}
