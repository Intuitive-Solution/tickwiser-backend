<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $firebaseUid = $request->get('firebase_uid');
        $query = Task::where('user_id', $firebaseUid);
        
        // Filter by project if project_id is provided
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        return $query->with(['comments', 'project'])
            ->orderBy('priority', 'asc')
            ->orderBy('date', 'asc')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'date' => 'required|date',
            'priority' => 'integer|min:1|max:10',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $firebaseUid = $request->get('firebase_uid');

        return Task::create([
            'title' => $request->title,
            'date' => $request->date,
            'status' => $request->status ?? false,
            'priority' => $request->priority ?? 5,
            'user_id' => $firebaseUid,
            'project_id' => $request->project_id,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only update their own tasks
        if ($task->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Validate project_id if provided
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
        ]);
        
        $task->update($request->only('title', 'status', 'date', 'priority', 'project_id'));
        return $task;
    }

    public function destroy(Request $request, Task $task)
    {
        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only delete their own tasks
        if ($task->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }
}
