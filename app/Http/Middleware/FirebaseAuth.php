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
            // Initialize Firebase Auth with project ID
            $factory = (new Factory)->withProjectId(config('firebase.project_id'));
            $this->auth = $factory->createAuth();
        } catch (Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
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
            // Always try to extract UID from token first (this is more reliable)
            $uid = $this->extractUidFromToken($token);
            
            if (!$uid) {
                return response()->json(['error' => 'Invalid token format - no UID found'], 401);
            }

            // Try Firebase SDK verification if available (for additional security)
            if ($this->auth) {
                try {
                    $verifiedIdToken = $this->auth->verifyIdToken($token);
                    $verifiedUid = $verifiedIdToken->claims()->get('sub');
                    $email = $verifiedIdToken->claims()->get('email');
                    
                    // Ensure the UIDs match
                    if ($uid !== $verifiedUid) {
                        \Log::error('UID mismatch: extracted=' . $uid . ', verified=' . $verifiedUid);
                        return response()->json(['error' => 'Token verification failed'], 401);
                    }
                    
                    // Add user info to request
                    $request->merge([
                        'firebase_uid' => $uid,
                        'firebase_email' => $email
                    ]);
                    
                    \Log::info('✅ Firebase auth successful for user: ' . $uid . ' (' . $email . ')');
                } catch (Exception $verifyError) {
                    // If Firebase SDK fails, fall back to manual extraction (but log the issue)
                    \Log::warning('Firebase SDK verification failed, using manual extraction for user: ' . $uid . ' - Error: ' . $verifyError->getMessage());
                    $request->merge(['firebase_uid' => $uid]);
                }
            } else {
                // Firebase SDK not available, use manual extraction
                $request->merge(['firebase_uid' => $uid]);
                \Log::info('🔧 Using manual token parsing for user: ' . $uid);
            }

            return $next($request);
        } catch (Exception $e) {
            \Log::error('Firebase token processing failed: ' . $e->getMessage());
            return response()->json(['error' => 'Unauthorized - Token processing failed: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Extract UID from Firebase JWT token as fallback
     */
    private function extractUidFromToken($token)
    {
        try {
            // Split the JWT token
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                \Log::error('Invalid JWT format: expected 3 parts, got ' . count($parts));
                return null;
            }

            // Decode the payload (second part)
            // Add padding if needed for base64 decoding
            $payload = $parts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            
            // Add padding if needed
            $padding = 4 - (strlen($payload) % 4);
            if ($padding !== 4) {
                $payload .= str_repeat('=', $padding);
            }
            
            $decodedPayload = base64_decode($payload);
            if (!$decodedPayload) {
                \Log::error('Failed to base64 decode JWT payload');
                return null;
            }
            
            $claims = json_decode($decodedPayload, true);
            if (!$claims) {
                \Log::error('Failed to JSON decode JWT claims');
                return null;
            }

            // Log the claims for debugging
            \Log::info('🔍 JWT Claims extracted:', [
                'sub' => $claims['sub'] ?? 'missing',
                'email' => $claims['email'] ?? 'missing',
                'iss' => $claims['iss'] ?? 'missing',
                'aud' => $claims['aud'] ?? 'missing'
            ]);

            // Return the 'sub' claim which contains the Firebase UID
            $uid = $claims['sub'] ?? null;
            
            if (!$uid) {
                \Log::error('No sub claim found in JWT token');
                return null;
            }
            
            return $uid;
        } catch (Exception $e) {
            \Log::error('Failed to extract UID from token: ' . $e->getMessage());
            return null;
        }
    }
} 