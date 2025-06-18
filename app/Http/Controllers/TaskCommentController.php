<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function index(Request $request, Task $task)
    {
        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only view comments for their own tasks
        if ($task->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return $task->comments;
    }

    public function store(Request $request, Task $task)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only add comments to their own tasks
        if ($task->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $firebaseUid,
            'comment' => $request->comment,
        ]);
    }

    public function update(Request $request, Task $task, TaskComment $comment)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only update their own comments on their own tasks
        if ($task->user_id !== $firebaseUid || $comment->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $comment->update(['comment' => $request->comment]);
        return $comment;
    }

    public function destroy(Request $request, Task $task, TaskComment $comment)
    {
        $firebaseUid = $request->get('firebase_uid');
        
        // Ensure user can only delete their own comments on their own tasks
        if ($task->user_id !== $firebaseUid || $comment->user_id !== $firebaseUid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
