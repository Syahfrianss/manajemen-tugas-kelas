<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['class_id', 'created_by', 'title', 'description', 'due_date'];
    protected $casts = ['due_date' => 'datetime'];

    public function classroom() { return $this->belongsTo(Classroom::class, 'class_id'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }
    public function completions() { return $this->hasMany(TaskCompletion::class); }
}
