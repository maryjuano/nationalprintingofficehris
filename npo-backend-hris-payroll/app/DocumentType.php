<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    protected $table = "document_types";
    protected $fillable = ['id', 'document_type_name', 'sub_types', 'is_active'];
    protected $casts = [
        'is_active' => 'boolean',
        'sub_types' => 'array'
    ];
    protected $dates = ['created_at', 'updated_at'];

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        if (!$date) {
            return NULL;
        }
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}
