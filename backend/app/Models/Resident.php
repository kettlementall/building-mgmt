<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Resident extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'unit_id', 'name', 'phone', 'email',
        'move_in_date', 'move_out_date', 'type', 'is_active', 'note',
    ];

    protected $casts = [
        'move_in_date'  => 'date',
        'move_out_date' => 'date',
        'is_active'     => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
