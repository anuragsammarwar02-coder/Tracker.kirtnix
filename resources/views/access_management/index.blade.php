@extends('layouts.app')

@section('title', 'Team & Role Permissions')
@section('page_title', 'Access Management')

@section('content')
<div x-data="{
  activeTab: 'members',
  inviteModal: false,
  editModal: false,
  memberToEdit: { id: null, name: '', email: '', role: 'analyst', status: 'active', client_id: '' },
  updatingPerm: false,
  async togglePerm(role, key, currentVal) {
    @if(Auth::user()?->role !== 'owner')
      alert('Only the workspace Owner can modify role permissions.');
      return;
    @endif
    this.updatingPerm = true;
    try {
      let res = await fetch('{{ route('access.updatePermission') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          role: role,
          permission_key: key,
          is_granted: !currentVal
        })
      });
      if (res.ok) {
        window.location.reload();
      } else {
        alert('Failed to update permission. Ensure you are logged in as Owner.');
      }
    } catch(e) {
      alert('Network error while saving permission.');
    } finally {
      this.updatingPerm = false;
    }
  }
}">
  <!-- Header Area -->
  <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
    <div>
      <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Team & Role Permissions</h1>
      <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
        Manage agency members, assign client workspaces, and configure granular role permission access.
      </div>
    </div>

    <div style="display: flex; gap: 10px; align-items: center;">
      @if(Auth::user()?->role === 'owner')
        <span class="pill pill-yellow" style="font-weight: 800; font-size: 11px;">👑 Owner Access Enabled</span>
      @else
        <span class="pill pill-gray" style="font-size: 11px;">Read-Only Mode</span>
      @endif
      <button type="button" class="btn btn-primary" @click="inviteModal = true">
        <span>+ Add Team Member</span>
      </button>
    </div>
  </div>

  <!-- Navigation Tabs -->
  <div class="tabs-nav" style="margin-bottom: 20px;">
    <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'members' }" @click="activeTab = 'members'">
      <span>👥 Active Team Members ({{ count($users) }})</span>
    </button>
    <button type="button" class="tab-btn" :class="{ 'active': activeTab === 'permissions' }" @click="activeTab = 'permissions'">
      <span>🛡️ Team & Role Permissions</span>
    </button>
  </div>

  <!-- TAB 1: ACTIVE TEAM MEMBERS -->
  <div x-show="activeTab === 'members'" class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
    <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-subtle);">
      <div>
        <h2 class="card-title">Team Directory & Client Workspace Mapping</h2>
        <div class="card-subtitle">Active accounts with system authentication rights</div>
      </div>
      <span class="pill pill-green"><span class="pill-dot"></span> {{ $users->where('status', 'active')->count() }} Active</span>
    </div>

    <div class="table-wrap" style="border: none; border-radius: 0;">
      <table class="table">
        <thead>
          <tr>
            <th>Member</th>
            <th>Email</th>
            <th>Role</th>
            <th>Assigned Workspace</th>
            <th>Status</th>
            <th>Joined</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
          <tr>
            <td>
              <div style="display: flex; align-items: center; gap: 9px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #0F172A; color: var(--brand-yellow); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; flex-shrink: 0; border: 1px solid var(--border-color);">
                  {{ strtoupper(substr($u->name, 0, 1)) }}
                </div>
                <div>
                  <strong style="color: var(--text-main); font-size: 13px;">{{ $u->name }}</strong>
                  @if($u->id === Auth::id())
                    <span style="font-size: 10px; color: var(--text-muted); margin-left: 4px;">(You)</span>
                  @endif
                </div>
              </div>
            </td>
            <td style="font-family: 'JetBrains Mono', monospace; font-size: 12px;">{{ $u->email }}</td>
            <td>
              @if($u->role === 'owner')
                <span class="pill pill-yellow" style="font-weight: 800;">👑 Owner</span>
              @elseif($u->role === 'admin')
                <span class="pill pill-blue" style="font-weight: 700;">⚡ Admin</span>
              @elseif($u->role === 'manager')
                <span class="pill pill-green">Manager</span>
              @elseif($u->role === 'analyst')
                <span class="pill pill-gray">Analyst</span>
              @else
                <span class="pill pill-gray">Client</span>
              @endif
            </td>
            <td>
              @if($u->client)
                <span class="pill pill-yellow" style="font-family: 'JetBrains Mono', monospace; font-size: 10.5px;">{{ $u->client->company_name }} ({{ $u->client->kx_code }})</span>
              @else
                <span style="color: var(--text-subtle); font-size: 11.5px;">Agency Global (All Clients)</span>
              @endif
            </td>
            <td>
              @if($u->status === 'active')
                <span class="pill pill-green"><span class="pill-dot"></span> Active</span>
              @else
                <span class="pill pill-red">Inactive</span>
              @endif
            </td>
            <td style="color: var(--text-muted); font-size: 11.5px;">{{ $u->created_at?->format('M d, Y') ?? 'Seeded' }}</td>
            <td style="text-align: right;">
              <div style="display: inline-flex; gap: 6px; align-items: center;">
                @if(Auth::user()?->role === 'owner')
                  <button type="button" @click="memberToEdit = { id: {{ $u->id }}, name: '{{ addslashes($u->name) }}', email: '{{ addslashes($u->email) }}', role: '{{ $u->role }}', status: '{{ $u->status }}', client_id: '{{ $u->client_id ?? '' }}' }; editModal = true;" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">
                    Edit
                  </button>

                  @if($u->id !== Auth::id())
                    <form method="POST" action="{{ route('access.toggleMemberStatus', $u) }}" style="display: inline;">
                      @csrf
                      <button type="submit" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px;">
                        {{ $u->status === 'active' ? 'Suspend' : 'Activate' }}
                      </button>
                    </form>

                    <form method="POST" action="{{ route('access.deleteMember', $u) }}" style="display: inline;" onsubmit="return confirm('Remove team member {{ addslashes($u->name) }}?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px; color: var(--accent-red);" title="Delete Member">
                        🗑
                      </button>
                    </form>
                  @endif
                @else
                  <span style="font-size: 11px; color: var(--text-subtle);">Managed by Owner</span>
                @endif
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No team members found.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 2: TEAM & ROLE PERMISSIONS MATRIX -->
  <div x-show="activeTab === 'permissions'" style="display: none;" class="card" style="padding: 0; overflow: hidden; margin-bottom: 24px;">
    <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-subtle);">
      <div>
        <h2 class="card-title">Granular Role Permission Matrix</h2>
        <div class="card-subtitle">
          @if(Auth::user()?->role === 'owner')
            Click any checkbox to grant or revoke capability for that specific role in real time.
          @else
            Read-only preview. Only the account Owner can modify permissions.
          @endif
        </div>
      </div>
      <span class="pill pill-green">Access Policy Active</span>
    </div>

    <div class="table-wrap" style="border: none; border-radius: 0; overflow-x: auto;">
      <table class="table">
        <thead>
          <tr>
            <th style="width: 320px;">Permission Capability</th>
            <th style="text-align: center;">👑 Owner</th>
            <th style="text-align: center;">⚡ Admin</th>
            <th style="text-align: center;">Manager</th>
            <th style="text-align: center;">Analyst</th>
            <th style="text-align: center;">Client</th>
          </tr>
        </thead>
        <tbody>
          @foreach($categories as $categoryName => $perms)
          <!-- Section Header -->
          <tr style="background: var(--bg-subtle);">
            <td colspan="6" style="font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); padding: 8px 14px;">
              {{ $categoryName }}
            </td>
          </tr>

          @foreach($perms as $permKey => $permLabel)
          <tr>
            <td style="font-weight: 600; color: var(--text-main); font-size: 12.5px;">{{ $permLabel }}</td>
            @foreach($roles as $role)
            @php
              $isGranted = $role === 'owner' ? true : ($permissions->get($role)?->firstWhere('permission_key', $permKey)?->is_granted ?? false);
            @endphp
            <td style="text-align: center;">
              @if($role === 'owner')
                <span style="color: var(--accent-green); font-size: 14px; font-weight: 800;" title="Owner always has all permissions">✔</span>
              @else
                <label style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center; padding: 4px;">
                  <input type="checkbox"
                    {{ $isGranted ? 'checked' : '' }}
                    {{ Auth::user()?->role !== 'owner' ? 'disabled' : '' }}
                    @change="togglePerm('{{ $role }}', '{{ $permKey }}', {{ $isGranted ? 'true' : 'false' }})"
                    style="width: 16px; height: 16px; accent-color: var(--brand-yellow); cursor: pointer;" />
                </label>
              @endif
            </td>
            @endforeach
          </tr>
          @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Invite / Add Member Modal -->
  <div class="modal-backdrop" x-show="inviteModal" style="display: none;" @click.self="inviteModal = false">
    <div class="modal-window" style="max-width: 480px;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 15px; font-weight: 800; color: var(--text-main);">Add Team Member</h3>
        <button type="button" @click="inviteModal = false" class="btn-icon" style="color: var(--text-muted);">✕</button>
      </div>

      <form method="POST" action="{{ route('access.storeMember') }}" style="padding: 20px;">
        @csrf

        <div class="form-group">
          <label class="form-label" for="name">Full Name *</label>
          <input type="text" id="name" name="name" class="form-input" placeholder="e.g. Rahul Sharma" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Work Email *</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="rahul@kirtnix.agency" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="role">Assign Role *</label>
          <select id="role" name="role" class="form-select" required>
            <option value="admin">Admin (Full Operations & Settings)</option>
            <option value="manager">Manager (Campaigns, Landing Pages, Bots)</option>
            <option value="analyst" selected>Analyst (View Analytics, Reports & Logs)</option>
            <option value="client">Client (Restricted to Assigned Workspace)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="client_id">Assign Client Workspace (Optional)</label>
          <select id="client_id" name="client_id" class="form-select">
            <option value="">-- Agency Global (Full Workspace Access) --</option>
            @foreach($clients as $c)
              <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->kx_code }})</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Initial Password (Default: Kirtnix@2026!)</label>
          <input type="password" id="password" name="password" class="form-input" placeholder="Leave blank for Kirtnix@2026!" />
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn-secondary" @click="inviteModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Member Account</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Member Modal -->
  <div class="modal-backdrop" x-show="editModal" style="display: none;" @click.self="editModal = false">
    <div class="modal-window" style="max-width: 480px;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 15px; font-weight: 800; color: var(--text-main);">Edit Team Member</h3>
        <button type="button" @click="editModal = false" class="btn-icon" style="color: var(--text-muted);">✕</button>
      </div>

      <form :action="'/access-management/member/' + memberToEdit.id" method="POST" style="padding: 20px;">
        @csrf
        @method('PUT')

        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-input" x-model="memberToEdit.name" required />
        </div>

        <div class="form-group">
          <label class="form-label">Email (Read Only)</label>
          <input type="email" class="form-input" x-model="memberToEdit.email" disabled style="opacity: 0.7;" />
        </div>

        <div class="form-group">
          <label class="form-label">Role *</label>
          <select name="role" class="form-select" x-model="memberToEdit.role" required>
            <option value="owner">Owner</option>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="analyst">Analyst</option>
            <option value="client">Client</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Status *</label>
          <select name="status" class="form-select" x-model="memberToEdit.status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Assign Client Workspace</label>
          <select name="client_id" class="form-select" x-model="memberToEdit.client_id">
            <option value="">-- Agency Global (Full Access) --</option>
            @foreach($clients as $c)
              <option value="{{ $c->id }}">{{ $c->company_name }} ({{ $c->kx_code }})</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Update Password (Optional)</label>
          <input type="password" name="password" class="form-input" placeholder="Leave empty to keep current password" />
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn-secondary" @click="editModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Member Changes</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
