@extends('layouts.app')

@section('title', 'KirtniX AI Assistant')
@section('page_title', 'KirtniX AI Copilot')

@section('content')
<div x-data="aiChatApp()" style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 20px; align-items: start;">
  <!-- Left Side: Interactive AI Chat Workspace -->
  <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; height: calc(100vh - 120px);">
    <!-- Chat Header -->
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-card);">
      <div style="display: flex; align-items: center; gap: 10px;">
        <div style="width: 32px; height: 32px; border-radius: 8px; background: #0F172A; color: var(--brand-yellow); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;">
          ⚡
        </div>
        <div>
          <h2 style="font-size: 15px; font-weight: 800; color: var(--text-main); line-height: 1.2;">KirtniX AI Copilot</h2>
          <div style="font-size: 11px; color: var(--text-muted);">Marketing & CRO Specialist · English, Hindi & Hinglish</div>
        </div>
      </div>
      <span class="pill pill-green"><span class="pill-dot"></span> Online</span>
    </div>

    <!-- Quick Prompt Suggestion Chips -->
    <div style="padding: 10px 16px; background: var(--bg-subtle); border-bottom: 1px solid var(--border-subtle); display: flex; gap: 6px; overflow-x: auto; white-space: nowrap;">
      <button type="button" @click="sendPredefined('Which client performed best this week?')" class="pill pill-yellow" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        🏆 Best Client
      </button>
      <button type="button" @click="sendPredefined('Which campaign should I scale?')" class="pill pill-yellow" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        🚀 Scale Campaign
      </button>
      <button type="button" @click="sendPredefined('Why did cost per join increase?')" class="pill pill-gray" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        📈 Cost Analysis
      </button>
      <button type="button" @click="sendPredefined('Show me the weakest conversion step.')" class="pill pill-gray" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        🔍 Weakest Step
      </button>
      <button type="button" @click="sendPredefined('Compare this week with last week.')" class="pill pill-gray" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        📊 WoW Comparison
      </button>
      <button type="button" @click="sendPredefined('Which landing page converts best?')" class="pill pill-gray" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        📄 Best Landing Page
      </button>
      <button type="button" @click="sendPredefined('Any tracking issue today?')" class="pill pill-gray" style="cursor: pointer; border: none; font-size: 11px; font-weight: 600;">
        ✅ Tracking Health
      </button>
    </div>

    <!-- Messages Container -->
    <div id="chatMessages" style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">
      <template x-for="(msg, index) in messages" :key="index">
        <div :style="msg.role === 'user' ? 'align-self: flex-end; max-width: 80%;' : 'align-self: flex-start; max-width: 90%;'">
          <div :style="msg.role === 'user' ? 'background: #0F172A; color: #FFFFFF; border-radius: 12px 12px 2px 12px; padding: 10px 14px; font-size: 13px;' : 'background: var(--bg-subtle); border: 1px solid var(--border-color); color: var(--text-main); border-radius: 12px 12px 12px 2px; padding: 14px 16px; font-size: 13px; line-height: 1.6;'">
            <div x-html="renderMarkdown(msg.text)"></div>
            <div :style="msg.role === 'user' ? 'font-size: 10px; color: #94A3B8; text-align: right; margin-top: 4px;' : 'font-size: 10px; color: var(--text-muted); margin-top: 6px;'" x-text="msg.time"></div>
          </div>
        </div>
      </template>

      <div x-show="loading" style="align-self: flex-start; max-width: 80%;">
        <div style="background: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: 12px; padding: 10px 14px; font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
          <span style="display: inline-block; animation: spin 1s linear infinite;">⚡</span>
          <span>Analyzing funnel metrics across Meta and Telegram...</span>
        </div>
      </div>
    </div>

    <!-- Chat Input Form -->
    <div style="padding: 14px 20px; border-top: 1px solid var(--border-color); background: var(--bg-card);">
      <form @submit.prevent="sendMessage()" style="display: flex; gap: 10px;">
        <input type="text" x-model="userInput" class="form-input" placeholder="Ask in English or Hindi (e.g. 'Kaunsa campaign scale karna chahiye?')..." style="padding: 10px 14px; font-size: 13px;" :disabled="loading" />
        <button type="submit" class="btn btn-primary" style="padding: 10px 18px; font-size: 13px;" :disabled="loading">
          <span>Send ↗</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Right Side: Live Funnel Health & Diagnostics -->
  <div style="display: flex; flex-direction: column; gap: 16px;">
    <!-- Live Snapshot Metrics -->
    <div class="card">
      <h2 class="card-title" style="margin-bottom: 12px;">Live Agency Metrics (7d)</h2>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
        <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Landing Views</div>
          <div style="font-size: 16px; font-weight: 800; margin-top: 2px;">{{ number_format($metrics['total_views']) }}</div>
        </div>
        <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">CTA Clicks</div>
          <div style="font-size: 16px; font-weight: 800; color: var(--brand-yellow-hover); margin-top: 2px;">{{ number_format($metrics['total_clicks']) }}</div>
        </div>
        <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Telegram Joins</div>
          <div style="font-size: 16px; font-weight: 800; color: #B45309; margin-top: 2px;">{{ number_format($metrics['joins']) }}</div>
        </div>
        <div style="padding: 10px; background: var(--bg-subtle); border-radius: 8px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--text-muted);">Cost Per Join</div>
          <div style="font-size: 16px; font-weight: 800; color: var(--accent-green); margin-top: 2px;">$0.96</div>
        </div>
      </div>
    </div>

    <!-- Active Clients Snapshot -->
    <div class="card" style="padding: 0; overflow: hidden;">
      <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color);">
        <h2 class="card-title">Client Roster</h2>
      </div>
      <div style="padding: 8px 16px; display: flex; flex-direction: column; gap: 8px;">
        @foreach($clients as $c)
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding: 6px 0; border-bottom: 1px solid var(--border-subtle);">
          <div>
            <strong>{{ $c->company_name }}</strong>
            <span style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--text-muted);">({{ $c->kx_code ?? 'KX-00' . $c->id }})</span>
          </div>
          <span class="pill pill-green" style="font-size: 9.5px;">Active</span>
        </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  function aiChatApp() {
    return {
      userInput: '',
      loading: false,
      messages: [
        {
          role: 'ai',
          text: '👋 **Hello! I am your KirtniX AI Copilot.**\n\nI monitor your Meta Ads spend, direct deep-links, and verified Telegram joins across all clients. Ask me anything about campaign performance or CRO recommendations!',
          time: 'Just now'
        }
      ],
      sendPredefined(text) {
        this.userInput = text;
        this.sendMessage();
      },
      sendMessage() {
        var query = this.userInput.trim();
        if (!query || this.loading) return;

        var now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        this.messages.push({
          role: 'user',
          text: query,
          time: now
        });

        this.userInput = '';
        this.loading = true;
        this.scrollToBottom();

        fetch("{{ route('ai.chat') }}", {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          },
          body: JSON.stringify({ message: query })
        })
        .then(res => res.json())
        .then(data => {
          this.loading = false;
          this.messages.push({
            role: 'ai',
            text: data.reply,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          });
          this.scrollToBottom();
        })
        .catch(err => {
          this.loading = false;
          this.messages.push({
            role: 'ai',
            text: '⚠️ Network connection timeout. Please check your internet connection or retry.',
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
          });
          this.scrollToBottom();
        });
      },
      renderMarkdown(text) {
        if (!text) return '';
        var html = text
          .replace(/### (.*?)\n/g, '<h4 style="font-weight:800;margin:8px 0 4px;font-size:13px;">$1</h4>')
          .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
          .replace(/`([^`]+)`/g, '<code style="background:rgba(0,0,0,0.05);padding:1px 4px;border-radius:4px;font-family:monospace;font-size:11px;">$1</code>')
          .replace(/\n/g, '<br/>');
        return html;
      },
      scrollToBottom() {
        setTimeout(() => {
          var container = document.getElementById('chatMessages');
          if (container) container.scrollTop = container.scrollHeight;
        }, 50);
      }
    };
  }
</script>
<style>
  @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
@endsection
