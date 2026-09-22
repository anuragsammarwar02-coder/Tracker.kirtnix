@php
    $pickerId = $selectId ?? 'ad_account_id';
    $pickerName = $selectName ?? 'ad_account_id';
    $selectedVal = (string) ($selectedId ?? old($pickerName, ''));
    
    $accountsData = collect($availableAdAccounts ?? [])->map(function($acc) {
        return [
            'id' => (string) $acc->id,
            'name' => (string) $acc->name,
            'account_id' => (string) $acc->account_id,
            'currency' => (string) ($acc->currency ?? 'INR'),
            'status' => (string) ($acc->status ?? 'Active'),
            'business_name' => (string) ($acc->metaBusiness?->name ?? ''),
        ];
    })->values();
@endphp

<div 
  x-data="{
    isOpen: false,
    search: '',
    selectedId: '{{ $selectedVal }}',
    filterStatus: 'all',
    accounts: {{ Js::from($accountsData) }},
    customOpen: false,
    customInputId: '',
    fetching: false,
    fetchMsg: '',
    fetchErr: '',

    get selectedAccount() {
      return this.accounts.find(a => String(a.id) === String(this.selectedId)) || null;
    },

    get filteredAccounts() {
      let list = this.accounts;
      if (this.filterStatus === 'active') {
        list = list.filter(a => a.status.toLowerCase() === 'active');
      } else if (this.filterStatus === 'inr') {
        list = list.filter(a => (a.currency || '').toUpperCase() === 'INR');
      } else if (this.filterStatus === 'usd') {
        list = list.filter(a => (a.currency || '').toUpperCase() === 'USD');
      }

      if (!this.search.trim()) {
        return list;
      }

      const q = this.search.toLowerCase().trim();
      return list.filter(a => {
        return (a.name && a.name.toLowerCase().includes(q)) ||
               (a.account_id && a.account_id.toLowerCase().includes(q)) ||
               (a.currency && a.currency.toLowerCase().includes(q)) ||
               (a.business_name && a.business_name.toLowerCase().includes(q));
      });
    },

    select(acc) {
      if (acc) {
        this.selectedId = String(acc.id);
      } else {
        this.selectedId = '';
      }
      this.syncSelect();
      this.isOpen = false;
      this.search = '';
    },

    syncSelect() {
      const el = document.getElementById('{{ $pickerId }}');
      if (el) {
        // Ensure option exists
        if (this.selectedId && !Array.from(el.options).some(o => o.value == this.selectedId)) {
          const acc = this.selectedAccount;
          if (acc) {
            const opt = new Option(`${acc.name} (${acc.account_id}) — ${acc.currency} [${acc.status}]`, acc.id);
            el.add(opt);
          }
        }
        el.value = this.selectedId;
        el.dispatchEvent(new Event('change'));
      }
    },

    fetchCustom() {
      if (!this.customInputId.trim()) return;
      this.fetching = true;
      this.fetchMsg = '';
      this.fetchErr = '';

      fetch('{{ route('meta.ad_accounts.quick_fetch') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ account_id: this.customInputId })
      })
      .then(res => res.json())
      .then(data => {
        this.fetching = false;
        if (data.success && data.ad_account) {
          const acc = data.ad_account;
          const newObj = {
            id: String(acc.id),
            name: acc.name,
            account_id: acc.account_id,
            currency: acc.currency || 'INR',
            status: acc.status || 'Active',
            business_name: acc.business_name || ''
          };

          // Check if already in accounts list
          const existingIdx = this.accounts.findIndex(a => String(a.id) === String(newObj.id));
          if (existingIdx >= 0) {
            this.accounts[existingIdx] = newObj;
          } else {
            this.accounts.unshift(newObj);
          }

          this.select(newObj);
          this.fetchMsg = `✓ ${acc.name} (${acc.account_id}) fetched and selected!`;
          this.customInputId = '';
        } else {
          this.fetchErr = data.error || 'Failed to fetch account.';
        }
      })
      .catch(e => {
        this.fetching = false;
        this.fetchErr = 'Connection error: ' + e.message;
      });
    }
  }"
  x-init="syncSelect()"
  @click.outside="isOpen = false"
  style="position: relative; width: 100%;"
>
  <!-- Hidden Synchronized Standard Select for Form Posting & Compatibility -->
  <select id="{{ $pickerId }}" name="{{ $pickerName }}" style="display: none;">
    <option value="">-- No Ad Account Assigned --</option>
    @foreach($availableAdAccounts ?? [] as $acc)
      <option value="{{ $acc->id }}" {{ $selectedVal == $acc->id ? 'selected' : '' }}>
        {{ $acc->name }} ({{ $acc->account_id }}) — {{ $acc->currency }} [{{ $acc->status }}]
      </option>
    @endforeach
  </select>

  <!-- Interactive Trigger Box -->
  <div 
    @click="isOpen = !isOpen"
    tabindex="0"
    @keydown.escape="isOpen = false"
    style="display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; min-height: 42px; padding: 7px 12px; background: var(--bg-card, #ffffff); border: 1.5px solid var(--border-color, #e2e8f0); border-radius: 8px; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.03);"
    :style="isOpen ? 'border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);' : ''"
  >
    <div style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; overflow: hidden;">
      <template x-if="selectedAccount">
        <div style="display: flex; align-items: center; gap: 8px; width: 100%; overflow: hidden;">
          <span style="font-size: 14px; flex-shrink: 0;">📊</span>
          <div style="display: flex; flex-direction: column; min-width: 0; flex: 1; text-align: left;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; overflow: hidden;">
              <span style="font-size: 13px; font-weight: 700; color: var(--text-main, #0f172a); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="selectedAccount.name"></span>
              <span style="font-size: 10.5px; font-weight: 800; padding: 1px 6px; border-radius: 4px; background: rgba(245, 158, 11, 0.12); color: #b45309;" x-text="selectedAccount.currency"></span>
              <span 
                style="font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 4px;"
                :style="selectedAccount.status.toLowerCase() === 'active' ? 'background: rgba(16, 185, 129, 0.12); color: #059669;' : 'background: rgba(239, 68, 68, 0.12); color: #dc2626;'"
                x-text="selectedAccount.status"
              ></span>
            </div>
            <span style="font-size: 11px; color: var(--text-muted, #64748b); font-family: monospace;" x-text="selectedAccount.account_id"></span>
          </div>
        </div>
      </template>

      <template x-if="!selectedAccount">
        <div style="display: flex; align-items: center; gap: 8px; color: var(--text-muted, #94a3b8); font-size: 13px;">
          <span>🔍</span>
          <span>-- Select or Search an Ad Account --</span>
        </div>
      </template>
    </div>

    <!-- Clear & Toggle Icons -->
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
      <template x-if="selectedAccount">
        <button 
          type="button" 
          @click.stop="select(null)"
          title="Clear Selection"
          style="display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; background: var(--bg-subtle, #f1f5f9); border: none; color: var(--text-muted, #64748b); font-size: 11px; cursor: pointer; transition: background 0.15s;"
          @mouseenter="$el.style.background = '#e2e8f0'"
          @mouseleave="$el.style.background = 'var(--bg-subtle, #f1f5f9)'"
        >
          ✕
        </button>
      </template>
      <span style="font-size: 11px; color: var(--text-muted, #94a3b8); transition: transform 0.2s;" :style="isOpen ? 'transform: rotate(180deg);' : ''">▼</span>
    </div>
  </div>

  <!-- Search Dropdown Popover -->
  <div 
    x-show="isOpen" 
    x-cloak
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050; background: var(--bg-card, #ffffff); border: 1.5px solid var(--border-color, #e2e8f0); border-radius: 10px; box-shadow: 0 12px 28px -4px rgba(0,0,0,0.18), 0 4px 10px -2px rgba(0,0,0,0.08); overflow: hidden; max-height: 380px; display: flex; flex-direction: column;"
  >
    <!-- Top Sticky Search Box -->
    <div style="padding: 10px 12px; background: var(--bg-subtle, #f8fafc); border-bottom: 1px solid var(--border-color, #e2e8f0); display: flex; flex-direction: column; gap: 8px;">
      <div style="position: relative; display: flex; align-items: center;">
        <span style="position: absolute; left: 10px; font-size: 13px; color: var(--text-muted, #94a3b8); pointer-events: none;">🔍</span>
        <input 
          type="text" 
          x-model="search"
          x-ref="searchInput"
          @click.stop
          placeholder="Search by account name, act_ ID, INR, USD..." 
          class="form-input" 
          style="width: 100%; padding-left: 32px; padding-right: 28px; font-size: 12.5px; height: 36px; border-radius: 6px; background: var(--bg-card, #ffffff);"
          x-init="$watch('isOpen', value => { if (value) setTimeout(() => $refs.searchInput.focus(), 60); })"
        />
        <button 
          type="button" 
          x-show="search.length > 0" 
          @click.stop="search = ''; $refs.searchInput.focus()"
          style="position: absolute; right: 8px; background: none; border: none; font-size: 11px; color: var(--text-muted, #94a3b8); cursor: pointer;"
        >
          ✕
        </button>
      </div>

      <!-- Quick Filter Chips -->
      <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
        <div style="display: flex; gap: 4px;">
          <button 
            type="button" 
            @click.stop="filterStatus = 'all'"
            style="padding: 2px 7px; border-radius: 4px; font-weight: 600; border: none; cursor: pointer;"
            :style="filterStatus === 'all' ? 'background: #f59e0b; color: #ffffff;' : 'background: rgba(0,0,0,0.05); color: var(--text-muted, #64748b);'"
          >
            All (<span x-text="accounts.length"></span>)
          </button>
          <button 
            type="button" 
            @click.stop="filterStatus = 'active'"
            style="padding: 2px 7px; border-radius: 4px; font-weight: 600; border: none; cursor: pointer;"
            :style="filterStatus === 'active' ? 'background: #10b981; color: #ffffff;' : 'background: rgba(0,0,0,0.05); color: var(--text-muted, #64748b);'"
          >
            Active (<span x-text="accounts.filter(a => a.status.toLowerCase() === 'active').length"></span>)
          </button>
          <button 
            type="button" 
            @click.stop="filterStatus = 'inr'"
            style="padding: 2px 7px; border-radius: 4px; font-weight: 600; border: none; cursor: pointer;"
            :style="filterStatus === 'inr' ? 'background: #3b82f6; color: #ffffff;' : 'background: rgba(0,0,0,0.05); color: var(--text-muted, #64748b);'"
          >
            INR
          </button>
          <button 
            type="button" 
            @click.stop="filterStatus = 'usd'"
            style="padding: 2px 7px; border-radius: 4px; font-weight: 600; border: none; cursor: pointer;"
            :style="filterStatus === 'usd' ? 'background: #8b5cf6; color: #ffffff;' : 'background: rgba(0,0,0,0.05); color: var(--text-muted, #64748b);'"
          >
            USD
          </button>
        </div>

        <span style="color: var(--text-muted, #64748b); font-size: 10.5px;">
          Showing <strong x-text="filteredAccounts.length"></strong> accounts
        </span>
      </div>
    </div>

    <!-- Scrollable Results List -->
    <div style="overflow-y: auto; max-height: 250px; padding: 4px;">
      <!-- "None / Clear" Option -->
      <div 
        @click.stop="select(null)"
        style="padding: 8px 10px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: background 0.12s; border-bottom: 1px dashed var(--border-color, #e2e8f0); margin-bottom: 2px;"
        :style="!selectedId ? 'background: rgba(245, 158, 11, 0.08); font-weight: 700;' : ''"
        @mouseenter="$el.style.background = 'var(--bg-subtle, #f8fafc)'"
        @mouseleave="$el.style.background = (!selectedId ? 'rgba(245, 158, 11, 0.08)' : 'transparent')"
      >
        <span style="font-size: 12px; color: var(--text-muted, #64748b); font-style: italic;">
          -- No Ad Account Assigned (Manual / Detached) --
        </span>
        <span x-show="!selectedId" style="color: #b45309; font-weight: 800; font-size: 12px;">✓</span>
      </div>

      <!-- Account Rows -->
      <template x-for="acc in filteredAccounts" :key="acc.id">
        <div 
          @click.stop="select(acc)"
          style="padding: 8px 10px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; gap: 8px; cursor: pointer; transition: background 0.12s; margin-bottom: 2px;"
          :style="String(selectedId) === String(acc.id) ? 'background: rgba(245, 158, 11, 0.12);' : ''"
          @mouseenter="$el.style.background = 'var(--bg-subtle, #f8fafc)'"
          @mouseleave="$el.style.background = (String(selectedId) === String(acc.id) ? 'rgba(245, 158, 11, 0.12)' : 'transparent')"
        >
          <div style="display: flex; flex-direction: column; min-width: 0; flex: 1;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
              <span style="font-size: 12.5px; font-weight: 700; color: var(--text-main, #0f172a);" x-text="acc.name"></span>
              <span style="font-size: 10px; font-weight: 800; padding: 1px 5px; border-radius: 4px; background: rgba(245, 158, 11, 0.12); color: #b45309;" x-text="acc.currency"></span>
              <span 
                style="font-size: 9px; font-weight: 700; padding: 1px 4px; border-radius: 4px;"
                :style="acc.status.toLowerCase() === 'active' ? 'background: rgba(16, 185, 129, 0.12); color: #059669;' : 'background: rgba(239, 68, 68, 0.12); color: #dc2626;'"
                x-text="acc.status"
              ></span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 1px;">
              <code style="font-size: 11px; color: var(--text-muted, #64748b);" x-text="acc.account_id"></code>
              <template x-if="acc.business_name">
                <span style="font-size: 10.5px; color: var(--text-muted, #94a3b8);" x-text="'· ' + acc.business_name"></span>
              </template>
            </div>
          </div>

          <div style="flex-shrink: 0;">
            <span x-show="String(selectedId) === String(acc.id)" style="color: #b45309; font-weight: 800; font-size: 14px;">✓</span>
          </div>
        </div>
      </template>

      <!-- Empty State if no search matches -->
      <template x-if="filteredAccounts.length === 0">
        <div style="padding: 24px 16px; text-align: center; color: var(--text-muted, #64748b);">
          <div style="font-size: 20px; margin-bottom: 6px;">🔍</div>
          <div style="font-size: 12.5px; font-weight: 600;">No ad accounts match "<span x-text="search"></span>"</div>
          <div style="font-size: 11px; margin-top: 4px;">Can't find it in the list? Fetch it directly using the tool below:</div>
          <button 
            type="button" 
            @click.stop="customOpen = true; customInputId = search; isOpen = false" 
            class="btn btn-secondary" 
            style="margin-top: 8px; font-size: 11px; padding: 4px 10px;"
          >
            ➕ Fetch "<span x-text="search"></span>" via Meta Graph API
          </button>
        </div>
      </template>
    </div>
  </div>

  <!-- Direct ID Fetch Tool (Below Dropdown) -->
  <div style="margin-top: 6px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
      <button 
        type="button" 
        @click="customOpen = !customOpen" 
        style="background: none; border: none; padding: 0; font-size: 11px; color: #b45309; font-weight: 700; cursor: pointer; text-decoration: underline;"
      >
        <span x-text="customOpen ? '▲ Hide Direct Account ID Lookup' : '➕ Can\'t find your account? Enter Ad Account ID directly'"></span>
      </button>

      <a href="{{ route('settings.index', ['tab' => 'meta']) }}" target="_blank" style="font-size: 11px; color: var(--accent-blue, #2563eb); text-decoration: none; font-weight: 600;">
        🔄 Deep Sync All
      </a>
    </div>

    <div x-show="customOpen" x-cloak style="margin-top: 8px; padding: 10px; background: var(--bg-subtle, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px;">
      <label style="font-size: 11px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 4px;">Enter Ad Account ID (e.g. 1049354661209537):</label>
      <div style="display: flex; gap: 8px;">
        <input 
          type="text" 
          name="custom_ad_account_id" 
          x-model="customInputId" 
          placeholder="e.g. 1049354661209537 or act_..." 
          class="form-input" 
          style="font-size: 12px; font-family: monospace;" 
          @keydown.enter.prevent="fetchCustom()"
        />
        <button 
          type="button" 
          class="btn btn-secondary" 
          style="font-size: 11px; padding: 4px 10px; white-space: nowrap;" 
          :disabled="fetching || !customInputId" 
          @click="fetchCustom()"
        >
          <span x-show="!fetching">🔍 Fetch &amp; Select</span>
          <span x-show="fetching">Fetching...</span>
        </button>
      </div>
      <div x-show="fetchMsg" style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 4px;" x-text="fetchMsg"></div>
      <div x-show="fetchErr" style="font-size: 11px; color: #dc2626; font-weight: 600; margin-top: 4px;" x-text="fetchErr"></div>
    </div>
  </div>
</div>
