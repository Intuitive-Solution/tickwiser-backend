<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $firebaseUid = $request->get('firebase_uid');
            
            $projects = Project::where('user_id', $firebaseUid)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'status' => 'sometimes|in:active,inactive',
            ]);

            $firebaseUid = $request->get('firebase_uid');

            if (!$firebaseUid) {
                return response()->json([
                    'message' => 'Authentication failed - no user ID found'
                ], 401);
            }

            $project = Project::create([
                'name' => $validated['name'],
                'user_id' => $firebaseUid,
                'status' => $validated['status'] ?? 'active',
            ]);

            return response()->json($project, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        try {
            $firebaseUid = $request->get('firebase_uid');
            
            // Check if the project belongs to the authenticated user
            if ($project->user_id !== $firebaseUid) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json($project);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        try {
            $firebaseUid = $request->get('firebase_uid');
            
            // Check if the project belongs to the authenticated user
            if ($project->user_id !== $firebaseUid) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'status' => 'sometimes|required|in:active,inactive',
            ]);

            $project->update($validated);

            return response()->json($project);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        try {
            $firebaseUid = $request->get('firebase_uid');
            
            // Check if the project belongs to the authenticated user
            if ($project->user_id !== $firebaseUid) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $project->delete();

            return response()->json([
                'message' => 'Project deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting project',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
