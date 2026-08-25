@extends('layouts.app')

@section('title', 'Frequently Asked Questions')
@section('page_title', 'FAQ')

@section('content')
<div style="max-width: 840px; margin: 0 auto;">
  <!-- Header Area -->
  <div style="margin-bottom: 24px;">
    <h1 style="font-size: 20px; font-weight: 800; color: var(--text-main); letter-spacing: -0.3px;">Frequently Asked Questions</h1>
    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 1px;">
      Quick reference guide on Telegram tracking, Meta CAPI, direct redirects, and Hostinger setup.
    </div>
  </div>

  <div style="display: flex; flex-direction: column; gap: 14px;">
    <!-- FAQ 1 -->
    <div class="card" x-data="{ open: true }">
      <div @click="open = !open" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <h2 style="font-size: 14px; font-weight: 800; color: var(--text-main);">How does direct Telegram redirection work without the preview screen?</h2>
        <span x-text="open ? '−' : '+'" style="font-size: 18px; font-weight: 800; color: var(--text-muted);"></span>
      </div>
      <div x-show="open" style="margin-top: 10px; font-size: 13px; color: var(--text-body); line-height: 1.6; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
        When a visitor clicks a CTA button on <code>/lp/{slug}</code>, the request hits our high-speed endpoint <code>/go/{token}</code>. The server intercepts visitor UUID, IP hash, and UTM parameters in SQLite/MySQL, queues the Meta CAPI <code>Lead</code> event, and immediately executes a native <code>tg://resolve?domain=...</code> or <code>tg://join?invite=...</code> deep link. This directly launches the Telegram app on mobile and desktop without stopping on the slow browser preview page.
      </div>
    </div>

    <!-- FAQ 2 -->
    <div class="card" x-data="{ open: false }">
      <div @click="open = !open" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <h2 style="font-size: 14px; font-weight: 800; color: var(--text-main);">How does Telegram Bot Member Verification work?</h2>
        <span x-text="open ? '−' : '+'" style="font-size: 18px; font-weight: 800; color: var(--text-muted);"></span>
      </div>
      <div x-show="open" style="margin-top: 10px; font-size: 13px; color: var(--text-body); line-height: 1.6; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
        Add your Telegram Bot as an Administrator to your channel with member management permissions. Kirtnix registers a webhook with Telegram's Bot API. Whenever a user joins, requests to join, or leaves the channel, Telegram sends a real-time <code>chat_member</code> payload to <code>/api/telegram/webhook/{secret}</code>, recording true verified conversions.
      </div>
    </div>

    <!-- FAQ 3 -->
    <div class="card" x-data="{ open: false }">
      <div @click="open = !open" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <h2 style="font-size: 14px; font-weight: 800; color: var(--text-main);">How does Meta Conversions API (CAPI) deduplication work?</h2>
        <span x-text="open ? '−' : '+'" style="font-size: 18px; font-weight: 800; color: var(--text-main);"></span>
      </div>
      <div x-show="open" style="margin-top: 10px; font-size: 13px; color: var(--text-body); line-height: 1.6; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
        Every conversion generates a unique deterministic <code>event_id</code>. Both the browser-side Meta Pixel and our server-side CAPI dispatch use this identical <code>event_id</code> along with SHA-256 hashed customer parameters (IP, User Agent, <code>fbc</code>, <code>fbp</code>). Meta Events Manager automatically deduplicates the dual signals to guarantee 100% data fidelity without double counting.
      </div>
    </div>

    <!-- FAQ 4 -->
    <div class="card" x-data="{ open: false }">
      <div @click="open = !open" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <h2 style="font-size: 14px; font-weight: 800; color: var(--text-main);">How do I deploy this to Hostinger on tracker.kirtnix.agency?</h2>
        <span x-text="open ? '−' : '+'" style="font-size: 18px; font-weight: 800; color: var(--text-main);"></span>
      </div>
      <div x-show="open" style="margin-top: 10px; font-size: 13px; color: var(--text-body); line-height: 1.6; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
        Upload the project files to your Hostinger subdomain directory. Point the document root to <code>public/</code> (or keep the included root <code>.htaccess</code>). Set your MySQL database credentials in <code>.env</code> and run <code>php artisan migrate --force</code>.
      </div>
    </div>
  </div>
</div>
@endsection
