<?php

namespace App\Http\Controllers;

use App\Models\LoginRequest;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function loginRequests(Request $request)
    {
        $query = LoginRequest::with('user')->orderBy('requested_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => LoginRequest::count(),
            'approved' => LoginRequest::where('status', 'approved')->count(),
            'pending' => LoginRequest::where('status', 'pending')->count(),
            'rejected' => LoginRequest::where('status', 'rejected')->count(),
        ];

        return view('security.login_requests', compact('requests', 'stats'));
    }

    public function updateStatus(Request $request, LoginRequest $loginRequest)
    {
        $status = $request->input('status', 'approved');
        $loginRequest->update(['status' => $status]);

        return back()->with('success', "Login request for {$loginRequest->email} marked as {$status}.");
    }

    public function revokeAccess(Request $request, LoginRequest $loginRequest)
    {
        $loginRequest->update(['status' => 'revoked']);

        // Invalidate active user sessions if applicable
        if ($loginRequest->user_id) {
            try {
                \Illuminate\Support\Facades\DB::table('sessions')
                    ->where('user_id', $loginRequest->user_id)
                    ->delete();
            } catch (\Exception $e) {}

            $loginRequest->user?->update(['remember_token' => null]);
        }

        return back()->with('success', "Access for {$loginRequest->email} has been revoked and active sessions invalidated.");
    }
}
