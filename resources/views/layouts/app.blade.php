<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Dashboard') | Kirtnix — TG TRACKER</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/branding/kirtnix_favicon.png') }}">
  <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">

  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: ['selector', '[data-theme="dark"]'],
      theme: {
        extend: {
          colors: {
            brand: {
              yellow: '#FACC15',
              'yellow-hover': '#EAB308',
              'yellow-dark': '#CA8A04',
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace']
          }
        }
      }
    }
  </script>

  <!-- Alpine.js & Chart.js -->
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    /* ==========================================================
       DESIGN SYSTEM — KIRTNIX TG TRACKER
       Light SaaS Primary Theme with Linear/Notion Density
       ========================================================== */
    :root, :root[data-theme="light"] {
      --bg-body: #F8F9FA;
      --bg-sidebar: #FFFFFF;
      --bg-card: #FFFFFF;
      --bg-card-hover: #F9FAFB;
      --bg-subtle: #F3F4F6;
      --bg-input: #FFFFFF;
      --border-color: #E5E7EB;
      --border-subtle: #F3F4F6;
      --border-focus: #EAB308;
      
      --text-main: #0F172A;
      --text-body: #334155;
      --text-muted: #64748B;
      --text-subtle: #94A3B8;

      --brand-yellow: #EAB308;
      --brand-yellow-hover: #CA8A04;
      --brand-yellow-light: #FEF9C3;
      --brand-yellow-border: #FDE047;
      --brand-yellow-glow: rgba(234, 179, 8, 0.16);

      --accent-green: #10B981;
      --accent-green-light: #ECFDF5;
      --accent-blue: #0284C7;
      --accent-blue-light: #F0F9FF;
      --accent-red: #EF4444;
      --accent-red-light: #FEF2F2;
      --accent-purple: #8B5CF6;
      --accent-purple-light: #F5F3FF;

      --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.04);
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03);
      --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.08);

      --radius-sm: 6px;
      --radius-md: 10px;
      --radius-lg: 14px;
      --radius-full: 9999px;
    }

    :root[data-theme="dark"] {
      --bg-body: #090D14;
      --bg-sidebar: #0D121D;
      --bg-card: #121826;
      --bg-card-hover: #182032;
      --bg-subtle: #161F30;
      --bg-input: #0D121D;
      --border-color: #1E293B;
      --border-subtle: #172033;
      --border-focus: #FACC15;

      --text-main: #F8FAFC;
      --text-body: #CBD5E1;
      --text-muted: #94A3B8;
      --text-subtle: #64748B;

      --brand-yellow: #FACC15;
      --brand-yellow-hover: #EAB308;
      --brand-yellow-light: rgba(250, 204, 21, 0.12);
      --brand-yellow-border: rgba(250, 204, 21, 0.3);
      --brand-yellow-glow: rgba(250, 204, 21, 0.22);

      --accent-green: #34D399;
      --accent-green-light: rgba(52, 211, 153, 0.12);
      --accent-blue: #38BDF8;
      --accent-blue-light: rgba(56, 189, 248, 0.12);
      --accent-red: #F87171;
      --accent-red-light: rgba(248, 113, 113, 0.12);
      --accent-purple: #A78BFA;
      --accent-purple-light: rgba(167, 139, 250, 0.12);

      --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.3);
      --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
      --shadow-md: 0 4px 14px rgba(0, 0, 0, 0.5);
      --shadow-lg: 0 12px 32px rgba(0, 0, 0, 0.6);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    /* Universal SVG & Icon Constraints (prevents giant/broken rendering) */
    svg {
      display: inline-block;
      vertical-align: middle;
      flex-shrink: 0;
      max-width: 100%;
    }
    svg:not([width]):not([style*="width"]):not(.icon-custom) {
      width: 16px;
      height: 16px;
    }
    .w-2\.5 { width: 10px !important; } .h-2\.5 { height: 10px !important; }
    .w-3 { width: 12px !important; } .h-3 { height: 12px !important; }
    .w-3\.5 { width: 14px !important; } .h-3\.5 { height: 14px !important; }
    .w-4 { width: 16px !important; } .h-4 { height: 16px !important; }
    .w-5 { width: 20px !important; } .h-5 { height: 20px !important; }
    .w-6 { width: 24px !important; } .h-6 { height: 24px !important; }
    .w-8 { width: 32px !important; } .h-8 { height: 32px !important; }
    .w-10 { width: 40px !important; } .h-10 { height: 40px !important; }
    .w-12 { width: 48px !important; } .h-12 { height: 48px !important; }
    .w-14 { width: 56px !important; } .h-14 { height: 56px !important; }
    .w-16 { width: 64px !important; } .h-16 { height: 64px !important; }

    body {
      background: var(--bg-body);
      color: var(--text-body);
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      min-height: 100vh;
      display: flex;
      font-size: 13.5px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      transition: background-color 0.2s ease, color 0.2s ease;
    }

    /* Modal System */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      padding: 16px;
      animation: fadeInModal 0.15s ease-out;
    }
    .modal-content {
      background: var(--bg-card);
      color: var(--text-main);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      width: 100%;
      position: relative;
      animation: slideInModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes fadeInModal {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideInModal {
      from { transform: scale(0.96) translateY(8px); opacity: 0; }
      to { transform: scale(1) translateY(0); opacity: 1; }
    }

    /* Global Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-subtle); }

    /* Persistent Layout */
    .app-sidebar {
      width: 256px;
      background: var(--bg-sidebar);
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      z-index: 100;
      user-select: none;
    }
    .app-main {
      margin-left: 256px;
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      min-width: 0;
    }

    /* Sidebar Header / Logo */
    .sidebar-brand {
      padding: 16px 18px 12px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 5px;
      border-bottom: 1px solid var(--border-subtle);
      transition: background-color 0.15s ease;
      text-decoration: none;
    }
    .sidebar-brand:hover {
      background: var(--bg-subtle);
    }
    .sidebar-brand-logo {
      width: 155px;
      max-width: 100%;
      height: auto;
      display: block;
      object-fit: contain;
    }
    :root[data-theme="dark"] .sidebar-brand-logo-dark { display: none !important; }
    :root[data-theme="dark"] .sidebar-brand-logo-light { display: block !important; }
    :root:not([data-theme="dark"]) .sidebar-brand-logo-dark { display: block !important; }
    :root:not([data-theme="dark"]) .sidebar-brand-logo-light { display: none !important; }

    .sidebar-brand .tracker-tag {
      background: var(--brand-yellow-light);
      color: #854D0E;
      padding: 1.5px 6px;
      border-radius: 4px;
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 0.5px;
      border: 1px solid var(--brand-yellow-border);
      text-transform: uppercase;
      line-height: 1.2;
    }
    :root[data-theme="dark"] .sidebar-brand .tracker-tag {
      color: var(--brand-yellow);
    }

    /* Sidebar Navigation Links */
    .sidebar-content {
      padding: 14px 10px;
      display: flex;
      flex-direction: column;
      gap: 2px;
      flex: 1;
      overflow-y: auto;
    }
    .sidebar-section-title {
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.9px;
      color: var(--text-subtle);
      padding: 12px 10px 4px;
    }
    .nav-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 7.5px 10px;
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      text-decoration: none;
      font-size: 12.5px;
      font-weight: 600;
      transition: all 0.15s ease;
      position: relative;
    }
    .nav-item-left {
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .nav-item svg {
      width: 16px;
      height: 16px;
      stroke-width: 2;
      flex-shrink: 0;
      color: var(--text-subtle);
      transition: color 0.15s ease;
    }
    .nav-item:hover {
      background: var(--bg-subtle);
      color: var(--text-main);
    }
    .nav-item:hover svg {
      color: var(--text-main);
    }
    .nav-item.active {
      background: var(--brand-yellow-light);
      color: var(--text-main);
      font-weight: 700;
    }
    .nav-item.active svg {
      color: var(--brand-yellow);
    }
    .nav-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 6px;
      bottom: 6px;
      width: 3px;
      background: var(--brand-yellow);
      border-radius: 0 2px 2px 0;
    }
    .nav-item-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 1px 6px;
      border-radius: 10px;
      background: var(--bg-subtle);
      color: var(--text-muted);
    }
    .nav-item-badge.badge-green {
      background: var(--accent-green-light);
      color: var(--accent-green);
    }

    .nav-sub-menu {
      display: flex;
      flex-direction: column;
      gap: 1px;
      padding-left: 24px;
      margin-top: 1px;
      margin-bottom: 3px;
    }
    .nav-sub-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 4px 8px;
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      text-decoration: none;
      font-size: 11.5px;
      font-weight: 600;
      transition: all 0.15s ease;
    }
    .nav-sub-item:hover {
      color: var(--text-main);
      background: var(--bg-subtle);
    }
    .nav-sub-item.active {
      color: #854D0E;
      background: var(--brand-yellow-light);
      font-weight: 700;
    }
    :root[data-theme="dark"] .nav-sub-item.active {
      color: var(--brand-yellow);
    }

    /* Sidebar Footer / Support & User Profile */
    .sidebar-footer {
      padding: 12px 14px;
      border-top: 1px solid var(--border-subtle);
      background: var(--bg-sidebar);
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .support-info-card {
      background: var(--bg-subtle);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 8px 10px;
      font-size: 11px;
    }
    .support-info-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 9.5px;
      letter-spacing: 0.5px;
      margin-bottom: 3px;
    }
    .support-link {
      display: flex;
      align-items: center;
      gap: 6px;
      color: var(--accent-blue);
      text-decoration: none;
      font-weight: 600;
      font-size: 11.5px;
    }
    .support-hours {
      font-size: 10px;
      color: var(--text-subtle);
      margin-top: 2px;
    }
    .sidebar-user {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-top: 4px;
    }
    .sidebar-user-left {
      display: flex;
      align-items: center;
      gap: 8px;
      min-width: 0;
    }
    .sidebar-user-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #0F172A;
      color: var(--brand-yellow);
      font-weight: 800;
      font-size: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1px solid var(--border-color);
    }
    .sidebar-user-details {
      min-width: 0;
      display: flex;
      flex-direction: column;
    }
    .sidebar-user-name {
      font-size: 12px;
      font-weight: 700;
      color: var(--text-main);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .sidebar-user-email {
      font-size: 10.5px;
      color: var(--text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Top Bar */
    .app-topbar {
      height: 58px;
      background: var(--bg-sidebar);
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .breadcrumb-nav {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12.5px;
      color: var(--text-muted);
    }
    .breadcrumb-nav a {
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 500;
    }
    .breadcrumb-nav a:hover {
      color: var(--text-main);
    }
    .breadcrumb-sep {
      color: var(--text-subtle);
      font-size: 11px;
    }
    .breadcrumb-current {
      font-weight: 700;
      color: var(--text-main);
    }

    /* Global Search Box (Ctrl + K trigger) */
    .search-trigger-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--bg-subtle);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      padding: 6px 12px;
      font-size: 12px;
      color: var(--text-muted);
      cursor: pointer;
      min-width: 220px;
      transition: all 0.15s ease;
    }
    .search-trigger-btn:hover {
      border-color: var(--text-subtle);
      background: var(--bg-body);
      color: var(--text-main);
    }
    .kbd-shortcut {
      margin-left: auto;
      font-family: 'JetBrains Mono', monospace;
      font-size: 10px;
      font-weight: 600;
      padding: 1px 5px;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      color: var(--text-muted);
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Action Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      padding: 7px 13px;
      font-size: 12.5px;
      font-weight: 600;
      border-radius: var(--radius-sm);
      cursor: pointer;
      text-decoration: none;
      border: 1px solid transparent;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    .btn-primary {
      background: var(--brand-yellow);
      color: #000000 !important;
      font-weight: 700;
      border-color: var(--brand-yellow-hover);
    }
    .btn-primary:hover {
      background: var(--brand-yellow-hover);
      color: #000000 !important;
    }
    .btn-secondary {
      background: var(--bg-card);
      border-color: var(--border-color);
      color: var(--text-main);
    }
    .btn-secondary:hover {
      background: var(--bg-subtle);
      border-color: var(--text-subtle);
    }
    .btn-danger {
      background: var(--accent-red-light);
      border-color: rgba(239, 68, 68, 0.3);
      color: var(--accent-red);
    }
    .btn-danger:hover {
      background: var(--accent-red);
      color: #ffffff;
    }
    .btn-icon {
      width: 34px;
      height: 34px;
      padding: 0;
      border-radius: var(--radius-sm);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-muted);
      cursor: pointer;
      position: relative;
    }
    .btn-icon:hover {
      border-color: var(--text-subtle);
      color: var(--text-main);
      background: var(--bg-subtle);
    }
    .btn-icon .indicator-dot {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 6px;
      height: 6px;
      background: var(--brand-yellow);
      border-radius: 50%;
    }

    /* Cards & Containers */
    .app-content {
      padding: 24px 28px 48px;
      flex: 1;
      width: 100%;
      box-sizing: border-box;
    }
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 20px;
      box-shadow: var(--shadow-xs);
    }
    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .card-title {
      font-size: 14.5px;
      font-weight: 700;
      letter-spacing: -0.2px;
      color: var(--text-main);
    }
    .card-subtitle {
      font-size: 12px;
      color: var(--text-muted);
      margin-top: 1px;
    }

    /* Status Pills */
    .pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: var(--radius-full);
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .pill-green {
      background: var(--accent-green-light);
      border: 1px solid rgba(16, 185, 129, 0.25);
      color: #047857;
    }
    .pill-yellow {
      background: var(--brand-yellow-light);
      border: 1px solid var(--brand-yellow-border);
      color: #854D0E;
    }
    .pill-blue {
      background: var(--accent-blue-light);
      border: 1px solid rgba(2, 132, 199, 0.25);
      color: #0369A1;
    }
    .pill-red {
      background: var(--accent-red-light);
      border: 1px solid rgba(239, 68, 68, 0.25);
      color: #B91C1C;
    }
    .pill-gray {
      background: var(--bg-subtle);
      border: 1px solid var(--border-color);
      color: var(--text-muted);
    }
    .pill-dot {
      width: 5px;
      height: 5px;
      border-radius: 50%;
    }
    .pill-green .pill-dot { background: #10B981; }
    .pill-yellow .pill-dot { background: #EAB308; }
    .pill-blue .pill-dot { background: #0284C7; }
    .pill-red .pill-dot { background: #EF4444; }
    .pill-gray .pill-dot { background: #94A3B8; }

    /* Tables */
    .table-wrap {
      overflow-x: auto;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      background: var(--bg-card);
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 12.5px;
    }
    .table th {
      background: var(--bg-subtle);
      padding: 10px 14px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      font-size: 10.5px;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border-color);
      white-space: nowrap;
    }
    .table td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border-subtle);
      color: var(--text-body);
      vertical-align: middle;
    }
    .table tr:last-child td { border-bottom: none; }
    .table tr:hover td { background: var(--bg-card-hover); }

    /* Forms */
    .form-group { margin-bottom: 16px; }
    .form-label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 5px;
    }
    .form-input, .form-select, .form-textarea {
      width: 100%;
      background: var(--bg-input);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-sm);
      padding: 8px 12px;
      font-size: 13px;
      color: var(--text-main);
      font-family: inherit;
      outline: none;
      transition: all 0.15s ease;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      border-color: var(--border-focus);
      box-shadow: 0 0 0 2px var(--brand-yellow-glow);
    }
    .form-hint {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 3px;
    }

    /* Alerts */
    .alert {
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      margin-bottom: 20px;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .alert-success {
      background: var(--accent-green-light);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #065F46;
    }
    .alert-danger {
      background: var(--accent-red-light);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #991B1B;
    }

    /* Modals & Dialogs */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(2px);
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .modal-window {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      width: 100%;
      max-width: 580px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      animation: modalPop 0.15s ease-out;
    }
    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.97); }
      to { opacity: 1; transform: scale(1); }
    }

    @media (max-width: 1024px) {
      .dashboard-grid-2-1, .dashboard-grid-1-4-1 {
        grid-template-columns: 1fr !important;
      }
    }

    @media (max-width: 960px) {
      .app-sidebar { transform: translateX(-100%); }
      .app-sidebar.open { transform: translateX(0); }
      .app-main { margin-left: 0; }
      .app-content { padding: 16px; }
      .app-topbar { padding: 0 16px; }
      .search-trigger-btn { min-width: 140px; }
    }
  </style>
  @yield('styles')
</head>
<body x-data="globalAppShell()">

  <!-- 1. LEFT SIDEBAR -->
  <aside class="app-sidebar" :class="{ 'open': mobileNavOpen }">
    <!-- Logo -->
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
      <img src="{{ asset('assets/branding/kirtnix_agency_dark.png') }}" class="sidebar-brand-logo sidebar-brand-logo-dark" alt="Kirtnix" />
      <img src="{{ asset('assets/branding/kirtnix_agency_light.png') }}" class="sidebar-brand-logo sidebar-brand-logo-light" alt="Kirtnix" style="display: none;" />
      <span class="tracker-tag">TG TRACKER</span>
    </a>

    <!-- Navigation List -->
    <nav class="sidebar-content">
      <!-- WORKSPACE -->
      <div class="sidebar-section-title">WORKSPACE</div>
      
      <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          <span>Dashboard</span>
        </div>
      </a>

      <a href="{{ route('analytics.index') }}" class="nav-item {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <span>Analytics</span>
        </div>
      </a>

      <a href="{{ route('clients.index') }}" class="nav-item {{ request()->routeIs('clients.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          <span>Clients</span>
        </div>
        <span class="nav-item-badge">{{ \App\Models\Client::count() }}</span>
      </a>

      <a href="{{ route('access.index') }}" class="nav-item {{ request()->routeIs('access.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <span>Access management</span>
        </div>
      </a>

      <!-- Landing Pages with Sub-options (Software vs Vercel) -->
      <div>
        <a href="{{ route('landing-pages.index') }}" class="nav-item {{ request()->routeIs('landing-pages.index') && !request('source') ? 'active' : (request()->routeIs('landing-pages.*') ? 'text-slate-900 font-bold' : '') }}">
          <div class="nav-item-left">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Landing pages</span>
          </div>
          <span class="nav-item-badge badge-green">{{ \App\Models\LandingPage::count() }}</span>
        </a>

        <div class="nav-sub-menu">
          <a href="{{ route('landing-pages.index', ['source' => 'native']) }}" class="nav-sub-item {{ request('source') === 'native' ? 'active' : '' }}">
            <span>• Software builder</span>
            <span class="text-[10px] opacity-70">{{ \App\Models\LandingPage::where('page_source', 'native')->count() }}</span>
          </a>

          <a href="{{ route('landing-pages.index', ['source' => 'vercel']) }}" class="nav-sub-item {{ request('source') === 'vercel' || request()->routeIs('landing-pages.import') ? 'active' : '' }}">
            <span class="flex items-center gap-1">
              <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24"><path d="M24 22.525H0l12-21.05 12 21.05z"/></svg>
              <span>Vercel pages</span>
            </span>
            <span class="text-[10px] opacity-70">{{ \App\Models\LandingPage::where('page_source', 'vercel')->count() }}</span>
          </a>
        </div>
      </div>

      <a href="{{ route('telegram.index') }}" class="nav-item {{ request()->routeIs('telegram.*') || request()->routeIs('telegram_bots.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
          <span>Telegram bots</span>
        </div>
      </a>

      <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span>Reports</span>
        </div>
      </a>

      <a href="{{ route('conversion_logs.index') }}" class="nav-item {{ request()->routeIs('conversion_logs.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          <span>Conversion logs</span>
        </div>
      </a>

      <!-- INTELLIGENCE -->
      <div class="sidebar-section-title">INTELLIGENCE</div>

      <a href="{{ route('ai.index') }}" class="nav-item {{ request()->routeIs('ai.*') || request()->routeIs('kirtnix_ai.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
          <span>KirtniX AI</span>
        </div>
        <span class="nav-item-badge" style="background: var(--brand-yellow-light); color: #854D0E;">⚡ Pro</span>
      </a>

      <a href="{{ route('notifications.index') }}" class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span>Notifications</span>
        </div>
        @php $unread = \App\Models\Notification::where('is_read', false)->count(); @endphp
        @if($unread > 0)
          <span class="nav-item-badge badge-green">{{ $unread }}</span>
        @endif
      </a>

      <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span>Settings</span>
        </div>
      </a>

      <!-- HELP -->
      <div class="sidebar-section-title">HELP</div>
      <a href="{{ route('support.index') }}" class="nav-item {{ request()->routeIs('support.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          <span>Support</span>
        </div>
      </a>

      <a href="{{ route('faq.index') }}" class="nav-item {{ request()->routeIs('faq.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>FAQ</span>
        </div>
      </a>

      <!-- SECURITY -->
      <div class="sidebar-section-title">SECURITY</div>
      <a href="{{ route('login_requests.index') }}" class="nav-item {{ request()->routeIs('login_requests.*') ? 'active' : '' }}">
        <div class="nav-item-left">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          <span>Login requests</span>
        </div>
        <span class="pill pill-green" style="font-size: 9px; padding: 1px 5px;">Shield</span>
      </a>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
      <div class="support-info-card">
        <div class="support-info-header">
          <span>Priority Agency Support</span>
          <span style="color: var(--accent-green);">● Active</span>
        </div>
        <a href="https://t.me/kirtnixsupport" target="_blank" class="support-link">
          <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.75-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
          <span>@kirtnixsupport</span>
        </a>
        <div class="support-hours">10 AM – 7 PM IST</div>
      </div>

      <div class="sidebar-user">
        <div class="sidebar-user-left">
          <div class="sidebar-user-avatar">
            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
          </div>
          <div class="sidebar-user-details">
            <span class="sidebar-user-name">{{ Auth::user()->name ?? 'Super Admin' }}</span>
            <span class="sidebar-user-email">{{ Auth::user()->email ?? 'admin@kirtnix.agency' }}</span>
          </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
          @csrf
          <button type="submit" class="btn-icon" title="Sign Out" style="width: 28px; height: 28px;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </button>
        </form>
      </div>
    </div>
  </aside>

  <!-- 2. MAIN APP VIEWPORT -->
  <div class="app-main">
    <!-- TOP BAR -->
    <header class="app-topbar">
      <div class="topbar-left">
        <!-- Mobile Toggle -->
        <button type="button" class="btn-icon" @click="mobileNavOpen = !mobileNavOpen" style="display: none;" class="mobile-toggle">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <!-- Breadcrumbs -->
        <div class="breadcrumb-nav">
          <a href="{{ route('dashboard') }}">Kirtnix</a>
          <span class="breadcrumb-sep">/</span>
          <span class="breadcrumb-current">@yield('page_title', 'Dashboard')</span>
        </div>

        <!-- Global Search Trigger -->
        <div class="search-trigger-btn" @click="searchModalOpen = true">
          <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <span>Search clients, pages, campaigns...</span>
          <span class="kbd-shortcut">Ctrl K</span>
        </div>
      </div>

      <div class="topbar-right">
        <!-- + New Page Quick Action -->
        <div x-data="{ open: false }" style="position: relative;">
          <button type="button" class="btn btn-primary" @click="open = !open">
            <span>+ New page</span>
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
          </button>

          <div x-show="open" @click.outside="open = false" style="position: absolute; right: 0; top: 40px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); width: 220px; box-shadow: var(--shadow-md); padding: 6px; z-index: 120; display: none;">
            <a href="{{ route('landing-pages.create') }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; font-size: 12.5px; font-weight: 600; color: var(--text-main); text-decoration: none; border-radius: 6px;" class="nav-item">
              <span>📄 Dynamic Landing Page</span>
            </a>
            <a href="{{ route('clients.create') }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; font-size: 12.5px; font-weight: 600; color: var(--text-main); text-decoration: none; border-radius: 6px;" class="nav-item">
              <span>👤 New Client Profile</span>
            </a>
            <a href="{{ route('campaigns.create') }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; font-size: 12.5px; font-weight: 600; color: var(--text-main); text-decoration: none; border-radius: 6px;" class="nav-item">
              <span>🎯 Marketing Campaign</span>
            </a>
            <a href="{{ route('telegram.create') }}" style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; font-size: 12.5px; font-weight: 600; color: var(--text-main); text-decoration: none; border-radius: 6px;" class="nav-item">
              <span>🤖 Connect Telegram Bot</span>
            </a>
          </div>
        </div>

        <!-- Notifications Dropdown -->
        <div x-data="{ open: false }" style="position: relative;">
          <button type="button" class="btn-icon" @click="open = !open" title="Notifications">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            @if(\App\Models\Notification::where('is_read', false)->count() > 0)
              <span class="indicator-dot"></span>
            @endif
          </button>

          <div x-show="open" @click.outside="open = false" style="position: absolute; right: 0; top: 44px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); width: 320px; box-shadow: var(--shadow-lg); z-index: 120; display: none; overflow: hidden;">
            <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
              <span style="font-weight: 700; font-size: 13px;">Notifications</span>
              <a href="{{ route('notifications.index') }}" style="font-size: 11px; color: var(--accent-blue); text-decoration: none; font-weight: 600;">View all</a>
            </div>
            <div style="max-height: 280px; overflow-y: auto;">
              @forelse(\App\Models\Notification::latest()->take(4)->get() as $n)
                <div style="padding: 10px 14px; border-bottom: 1px solid var(--border-subtle); font-size: 12px;">
                  <div style="font-weight: 700; color: var(--text-main); margin-bottom: 2px;">{{ $n->title }}</div>
                  <div style="color: var(--text-muted); font-size: 11px; line-height: 1.4;">{{ Str::limit($n->message, 85) }}</div>
                </div>
              @empty
                <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 12px;">No notifications.</div>
              @endforelse
            </div>
          </div>
        </div>

        <!-- Theme Toggle -->
        <button type="button" class="btn-icon" @click="toggleTheme()" title="Toggle Theme">
          <svg x-show="theme === 'light'" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
          <svg x-show="theme === 'dark'" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </button>

        <!-- User Profile Pill -->
        <a href="{{ route('profile.show') }}" class="btn btn-secondary" style="padding: 5px 10px; gap: 8px;" title="Profile & Security">
          <div style="width: 20px; height: 20px; border-radius: 50%; background: #0F172A; color: var(--brand-yellow); font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
            {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
          </div>
          <span style="font-size: 12px; font-weight: 600;">{{ Auth::user()->name ?? 'Admin' }}</span>
        </a>
      </div>
    </header>

    <!-- CONTENT BODY -->
    <main class="app-content">
      @if(session('success'))
        <div class="alert alert-success">
          <span>✔</span>
          <span>{{ session('success') }}</span>
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger">
          <span>⚠</span>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      @yield('content')
    </main>
  </div>

  <!-- 3. GLOBAL COMMAND BAR MODAL (CTRL + K) -->
  <div class="modal-backdrop" x-show="searchModalOpen" @keydown.escape.window="searchModalOpen = false" style="display: none;">
    <div class="modal-window" @click.outside="searchModalOpen = false" style="max-width: 540px;">
      <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 10px;">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="searchQuery" @input.debounce.250ms="performSearch()" placeholder="Search clients, pages, campaigns, reports..." style="width: 100%; border: none; outline: none; background: transparent; font-size: 14px; font-weight: 600; color: var(--text-main);" autofocus />
        <span class="kbd-shortcut">ESC</span>
      </div>

      <div style="max-height: 340px; overflow-y: auto; padding: 8px;">
        <template x-if="searchResults.length > 0">
          <div>
            <template x-for="(res, idx) in searchResults" :key="idx">
              <a :href="res.url" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; text-decoration: none; color: var(--text-main); transition: background 0.15s;" class="nav-item">
                <div>
                  <div style="font-weight: 700; font-size: 13px;" x-text="res.title"></div>
                  <div style="font-size: 11px; color: var(--text-muted);" x-text="res.subtitle"></div>
                </div>
                <span class="pill pill-yellow" x-text="res.badge"></span>
              </a>
            </template>
          </div>
        </template>

        <template x-if="searchResults.length === 0 && searchQuery.length > 0 && !isSearching">
          <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">
            No results found for "<span x-text="searchQuery"></span>".
          </div>
        </template>

        <template x-if="searchQuery.length === 0">
          <div style="padding: 12px 14px; font-size: 11px; color: var(--text-muted);">
            <div style="font-weight: 700; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">Quick Navigation</div>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <a href="{{ route('clients.index') }}" class="pill pill-gray" style="text-decoration: none;">👤 Clients</a>
              <a href="{{ route('analytics.index') }}" class="pill pill-gray" style="text-decoration: none;">📊 Analytics</a>
              <a href="{{ route('landing-pages.index') }}" class="pill pill-gray" style="text-decoration: none;">📄 Landing Pages</a>
              <a href="{{ route('reports.index') }}" class="pill pill-gray" style="text-decoration: none;">📈 Reports</a>
              <a href="{{ route('ai.index') }}" class="pill pill-gray" style="text-decoration: none;">⚡ KirtniX AI</a>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- Global Application Scripts -->
  <script>
    function globalAppShell() {
      return {
        theme: localStorage.getItem('kx_theme') || 'light',
        mobileNavOpen: false,
        searchModalOpen: false,
        searchQuery: '',
        searchResults: [],
        isSearching: false,
        init() {
          document.documentElement.setAttribute('data-theme', this.theme);

          // Keyboard shortcut for Command Bar (Ctrl + K / ⌘K)
          window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
              e.preventDefault();
              this.searchModalOpen = !this.searchModalOpen;
            }
          });
        },
        toggleTheme() {
          this.theme = this.theme === 'light' ? 'dark' : 'light';
          document.documentElement.setAttribute('data-theme', this.theme);
          localStorage.setItem('kx_theme', this.theme);
        },
        async performSearch() {
          if (this.searchQuery.trim().length === 0) {
            this.searchResults = [];
            return;
          }
          this.isSearching = true;
          try {
            const res = await fetch(`/api/search?q=${encodeURIComponent(this.searchQuery)}`);
            const data = await res.json();
            this.searchResults = data.results || [];
          } catch (err) {
            console.error('Search error:', err);
          } finally {
            this.isSearching = false;
          }
        }
      };
    }
  </script>
  @yield('scripts')
</body>
</html>
