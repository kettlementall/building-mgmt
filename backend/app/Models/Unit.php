<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['floor', 'number', 'area', 'status', 'note'];

    protected $casts = ['area' => 'decimal:2'];

    public function residents()
    {
        return $this->hasMany(Resident::class);
    }

    public function activeResident()
    {
        return $this->hasOne(Resident::class)->where('is_active', true);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
}
