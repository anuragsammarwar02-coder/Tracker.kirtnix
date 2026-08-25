<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\TeamPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessManagementController extends Controller
{
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        $clients = Client::orderBy('company_name')->get();
        
        $roles = ['owner', 'admin', 'manager', 'analyst', 'client'];
        $permissions = TeamPermission::all()->groupBy('role');

        $categories = [
            'GENERAL' => [
                'view_own_client_only' => 'View own client only',
            ],
            'LANDING PAGES' => [
                'create_edit_landing_pages' => 'Create / edit landing pages',
                'publish_unpublish_pages' => 'Publish / unpublish pages',
                'view_page_analytics' => 'View page analytics',
            ],
            'META ADS' => [
                'connect_facebook_meta' => 'Connect Facebook / Meta',
                'assign_ad_accounts' => 'Assign ad accounts to clients',
                'view_spend_budget' => 'View spend & budget',
            ],
            'TELEGRAM' => [
                'manage_agency_bot' => 'Manage agency bot',
                'view_join_history' => 'View join history & members',
            ],
            'AI COPILOT' => [
                'use_kirtnix_copilot' => 'Use Kirtnix Copilot',
                'ai_copywriter' => 'AI landing page copywriter',
            ],
            'AUDIT & REPORTS' => [
                'view_audit_log' => 'View audit log',
                'export_reports' => 'Export reports',
            ],
        ];

        return view('access_management.index', compact('users', 'clients', 'roles', 'permissions', 'categories'));
    }

    public function storeMember(Request $request)
    {
        if (Auth::user()?->role !== 'owner' && Auth::user()?->role !== 'admin') {
            abort(403, 'Unauthorized: Only the account owner or admin can invite team members.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:owner,admin,manager,analyst,client',
            'client_id' => 'nullable|exists:clients,id',
            'password' => 'nullable|string|min:8',
        ]);

        $password = $validated['password'] ?? 'Kirtnix@2026!';

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'client_id' => $validated['client_id'] ?? null,
            'password' => Hash::make($password),
            'status' => 'active',
        ]);

        return redirect()->route('access.index')->with('success', "Team member {$validated['name']} added successfully!");
    }

    public function updateMember(Request $request, User $user)
    {
        if (Auth::user()?->role !== 'owner') {
            abort(403, 'Unauthorized: Only the account owner can modify member roles and permissions.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:owner,admin,manager,analyst,client',
            'status' => 'required|in:active,inactive,suspended',
            'client_id' => 'nullable|exists:clients,id',
            'password' => 'nullable|string|min:8',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'client_id' => $validated['client_id'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('access.index')->with('success', "Team member {$user->name} updated successfully.");
    }

    public function toggleMemberStatus(User $user)
    {
        if (Auth::user()?->role !== 'owner') {
            abort(403, 'Unauthorized: Only the account owner can activate/deactivate members.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own owner account.');
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return back()->with('success', "Member {$user->name} status changed to {$newStatus}.");
    }

    public function deleteMember(User $user)
    {
        if (Auth::user()?->role !== 'owner') {
            abort(403, 'Unauthorized: Only the account owner can remove team members.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete the primary owner account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('access.index')->with('success', "Team member {$name} removed.");
    }

    public function updatePermission(Request $request)
    {
        if (Auth::user()?->role !== 'owner') {
            return response()->json(['error' => 'Unauthorized: Only the account owner can edit role permissions.'], 403);
        }

        $role = $request->input('role');
        $permissionKey = $request->input('permission_key');
        $isGranted = filter_var($request->input('is_granted'), FILTER_VALIDATE_BOOLEAN);

        TeamPermission::updateOrCreate(
            ['role' => $role, 'permission_key' => $permissionKey],
            ['is_granted' => $isGranted]
        );

        return response()->json(['success' => true]);
    }
}
