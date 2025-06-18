<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $fillable = ['task_id', 'user_id', 'comment'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
