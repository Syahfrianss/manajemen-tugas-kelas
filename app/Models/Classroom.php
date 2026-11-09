<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name', 'teacher_id'];

    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function students() { return $this->hasMany(User::class, 'class_id'); }
    public function tasks()    { return $this->hasMany(Task::class, 'class_id'); }
}
