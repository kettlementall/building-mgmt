<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExpenseAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['expense_id', 'filename', 'path', 'mime_type'];

    protected $appends = ['url'];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }
}
