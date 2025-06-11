<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return Task::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
        ]);

        return Task::create([
            'title' => $request->title,
            'date' => $request->date,
            'status' => $request->status ?? false,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $task->update($request->only('title', 'status', 'date'));
        return $task;
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }
}
