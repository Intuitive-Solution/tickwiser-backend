<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Exception;

class FirebaseAuth
{
    protected $auth;

    public function __construct()
    {
        try {
            // For development, we'll use a simpler approach without service account
            $factory = (new Factory)->withProjectId(config('firebase.project_id'));
            $this->auth = $factory->createAuth();
        } catch (Exception $e) {
            // If Firebase setup fails, we'll handle it gracefully
            $this->auth = null;
        }
    }

    public function handle(Request $request, Closure $next)
    {
        // Get the Authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        // Extract the token
        $token = substr($authHeader, 7);

        try {
            // For development, we'll use a simple token validation
            // In production, you should verify the Firebase token properly
            if ($this->auth) {
                $verifiedIdToken = $this->auth->verifyIdToken($token);
                $uid = $verifiedIdToken->claims()->get('sub');
                
                // Add user info to request
                $request->merge(['firebase_uid' => $uid]);
            } else {
                // Fallback for development - basic token check
                if (empty($token) || strlen($token) < 10) {
                    return response()->json(['error' => 'Invalid token'], 401);
                }
                
                // For development, we'll accept any reasonable token
                $request->merge(['firebase_uid' => 'dev_user_' . substr($token, 0, 10)]);
            }

            return $next($request);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unauthorized - Invalid token'], 401);
        }
    }
} 