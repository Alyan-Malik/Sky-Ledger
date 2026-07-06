<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\VerificationToken;
use Laravel\Sanctum\PersonalAccessToken;
use App\Mail\UserAccountVerification;
use App\Mail\PasswordResetEmail;
use App\Mail\PasswordChangedEmail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
        public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'min:7', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'verified' => 0,
            ]);

            if ($user) {
                $vToken = base64_encode(Str::random(64));

                VerificationToken::updateOrCreate(
                    ['email' => $request->email],
                    ['token' => $vToken, 'created_at' => Carbon::now()]
                );

                $this->sendVerificationEmail($user, $vToken);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Registration successful! Please check your email to verify your account.',
                    'email' => $user->email
                ], 201);
            }

            throw new \Exception("User could not be saved.");

        } catch (\Exception $e) {
            \Log::error('Registration Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong during registration.'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ], 401);
        }

        if (!$user->verified || is_null($user->email_verified_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Your account is not verified. Please check your email for the verification link.',
                'needs_verification' => true,
                'email' => $user->email
            ], 403);
        }

        // CRITICAL: Delete ALL existing tokens for this user (logs out all other sessions)
        // This ensures only one active session at a time
        $user->tokens()->delete();
        
        // Generate unique session ID
        $sessionId = Str::random(60);
        
        // Create new token with session_id in the token name
        $token = $user->createToken('auth-token-' . $sessionId)->plainTextToken;
        
        // Get the created token and update its session_id
        $tokenModel = $user->tokens()->latest()->first();
        if ($tokenModel) {
            $tokenModel->session_id = $sessionId;
            $tokenModel->last_activity = Carbon::now();
            $tokenModel->save();
        }

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'session_id' => $sessionId,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        
        // Update last activity
        $token = $user->currentAccessToken();
        if ($token) {
            $token->last_activity = Carbon::now();
            $token->save();
        }
        
        return response()->json($user);
    }

        public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function resendVerificationEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid email address'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user->verified && $user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email is already verified. Please login.'
            ], 400);
        }

        try {
            $vToken = base64_encode(Str::random(64));
            
            VerificationToken::updateOrCreate(
                ['email' => $user->email],
                ['token' => $vToken, 'created_at' => Carbon::now()]
            );

            $this->sendVerificationEmail($user, $vToken);

            return response()->json([
                'status' => 'success',
                'message' => 'Verification email has been resent. Please check your inbox.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Resend Verification Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resend verification email. Please try again later.'
            ], 500);
        }
    }

    private function sendVerificationEmail($user, $token)
    {
        Mail::to($user->email, $user->first_name)->send(new UserAccountVerification($user, $token));
    }

    public function verifyAccount(Request $request, $token) 
    {
        $verifyToken = VerificationToken::where('token', $token)->first();

        if (!$verifyToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification token. Please request a new one.'
            ], 400);
        }

        $createdAt = Carbon::parse($verifyToken->created_at);
        if ($createdAt->diffInHours(Carbon::now()) > 24) {
            $verifyToken->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'Verification link has expired. Please request a new one.'
            ], 400);
        }

        $user = User::where('email', $verifyToken->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.'
            ], 404);
        }

        if (!$user->email_verified_at || $user->verified == 0) {
            $user->verified = 1;
            $user->email_verified_at = Carbon::now();
            $user->save();

            $verifyToken->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Your email has been verified successfully! You can now log in.'
            ], 200);
        } else {
            $verifyToken->delete();
            return response()->json([
                'status' => 'info',
                'message' => 'Your email is already verified. Please proceed to login.'
            ], 200);
        }
    }

    public function sendPasswordResetLink(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email'
    ], [
        'email.exists' => "We can't find a user with that email address."
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $user = User::where('email', $request->email)->first();
        $token = Str::random(64);
        
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        // Send the email natively using Laravel Mail
        Mail::to($user->email, $user->name)->send(new PasswordResetEmail($user, $token));

        return response()->json([
            'status' => 'success',
            'message' => 'We have emailed your password reset link!',
        ], 200);

    } catch (\Exception $e) {
        Log::error('Password Reset Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Unable to send reset link. Please try again later.'
        ], 500);
    }
}

    public function checkResetToken(Request $request, $token = null)
    {
        $get_token = DB::table('password_reset_tokens')
            ->where(['token' => $token])
            ->first();

        if (!$get_token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token. Please request a new password reset link.'
            ], 400);
        }

        $createdAt = Carbon::parse($get_token->created_at);
        $diffMins = $createdAt->diffInMinutes(Carbon::now());

        if ($diffMins > 15) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired. Please request a new link.'
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Token is valid.',
            'token' => $token,
            'email' => $get_token->email
        ], 200);
    }

    public function resetPasswordHandler(Request $request)
{
    $validator = Validator::make($request->all(), [
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|max:45|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $tokenData = DB::table('password_reset_tokens')
            ->where([
                'token' => $request->token,
                'email' => $request->email,
            ])->first();

        if (!$tokenData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token or email address.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete ALL personal access tokens for this user after password reset (force logout all devices)
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where([
            'email' => $request->email,
            'token' => $request->token,
        ])->delete();

        // Send the confirmation email natively using Laravel Mail
        Mail::to($user->email, $user->name)->send(new PasswordChangedEmail($user, $request->password));

        return response()->json([
            'status' => 'success',
            'message' => 'Your password has been changed successfully. You can now log in.'
        ], 200);

    } catch (\Exception $e) {
        Log::error('Reset Password Handler Error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while resetting your password.'
        ], 500);
    }
}
}
