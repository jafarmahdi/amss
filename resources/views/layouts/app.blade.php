<!doctype html>
<html lang="<?= e($currentLocale) ?>" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      :root {
        --app-bg: #eef2f6;
        --app-bg-accent: rgba(21, 101, 192, 0.14);
        --app-bg-accent-2: rgba(11, 31, 58, 0.08);
        --app-surface: rgba(255, 255, 255, 0.82);
        --app-surface-strong: #ffffff;
        --app-surface-muted: rgba(248, 250, 252, 0.9);
        --app-text: #102033;
        --app-muted: #607086;
        --app-border: rgba(148, 163, 184, 0.22);
        --app-border-strong: rgba(100, 116, 139, 0.3);
        --app-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        --app-sidebar: linear-gradient(180deg, rgba(10, 22, 40, 0.96), rgba(18, 42, 69, 0.92));
        --app-sidebar-text: #f8fafc;
        --app-sidebar-muted: rgba(226, 232, 240, 0.7);
        --app-primary: #0f62fe;
        --app-primary-strong: #0b4ed1;
        --app-success: #0f9d77;
        --app-warning: #e88b00;
        --app-danger: #d14343;
        --app-radius: 22px;
      }

      body[data-theme="dark"] {
        --app-bg: #08111f;
        --app-bg-accent: rgba(28, 78, 216, 0.2);
        --app-bg-accent-2: rgba(14, 165, 233, 0.08);
        --app-surface: rgba(15, 23, 42, 0.78);
        --app-surface-strong: #0f172a;
        --app-surface-muted: rgba(15, 23, 42, 0.92);
        --app-text: #e5eef9;
        --app-muted: #94a3b8;
        --app-border: rgba(71, 85, 105, 0.45);
        --app-border-strong: rgba(100, 116, 139, 0.7);
        --app-shadow: 0 28px 65px rgba(2, 6, 23, 0.5);
        --app-sidebar: linear-gradient(180deg, rgba(2, 6, 23, 0.98), rgba(15, 23, 42, 0.95));
        --app-sidebar-text: #f8fafc;
        --app-sidebar-muted: rgba(148, 163, 184, 0.82);
        --app-primary: #60a5fa;
        --app-primary-strong: #3b82f6;
        --app-success: #34d399;
        --app-warning: #f59e0b;
        --app-danger: #f87171;
      }

      * {
        box-sizing: border-box;
      }

      body {
        min-height: 100vh;
        margin: 0;
        font-family: "Manrope", sans-serif;
        color: var(--app-text);
        background:
          radial-gradient(circle at top left, var(--app-bg-accent), transparent 28%),
          radial-gradient(circle at right 12%, var(--app-bg-accent-2), transparent 24%),
          linear-gradient(180deg, rgba(255,255,255,0.34), rgba(255,255,255,0)),
          var(--app-bg);
      }

      a {
        color: inherit;
        text-decoration: none;
      }

      .app-shell {
        padding: 20px;
      }

      .app-login-shell {
        min-height: 100vh;
        display: grid;
        place-items: center;
        padding: 24px;
      }

      .app-login-wrap {
        width: min(1100px, 100%);
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 24px;
        align-items: stretch;
      }

      .app-auth-panel,
      .app-auth-card,
      .app-sidebar,
      .app-topbar,
      .app-main-panel,
      .card,
      .table-wrap,
      .alert {
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        box-shadow: var(--app-shadow);
        border: 1px solid var(--app-border);
      }

      .app-auth-panel {
        border-radius: 32px;
        padding: 36px;
        background: linear-gradient(145deg, rgba(15, 98, 254, 0.95), rgba(13, 35, 66, 0.96));
        color: #f8fbff;
        position: relative;
        overflow: hidden;
      }

      .app-auth-panel::after {
        content: "";
        position: absolute;
        inset: auto -10% -30% auto;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        filter: blur(10px);
      }

      .app-auth-card {
        border-radius: 32px;
        padding: 32px;
        background: var(--app-surface);
      }

      .app-frame {
        display: grid;
        grid-template-columns: 290px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
      }

      .app-sidebar {
        position: sticky;
        top: 20px;
        min-height: calc(100vh - 40px);
        border-radius: 32px;
        padding: 24px 18px;
        background: var(--app-sidebar);
        color: var(--app-sidebar-text);
      }

      .app-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding: 8px 10px 18px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      }

      .app-brand-mark {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: linear-gradient(145deg, rgba(96, 165, 250, 0.9), rgba(14, 165, 233, 0.45));
        color: #eff6ff;
        font-size: 1.2rem;
      }

      .app-brand-subtitle {
        color: var(--app-sidebar-muted);
        font-size: 0.84rem;
      }

      .app-content {
        min-width: 0;
      }

      .app-topbar {
        border-radius: 28px;
        padding: 18px 22px;
        margin-bottom: 20px;
        background: var(--app-surface);
        position: relative;
        z-index: 30;
        overflow: visible;
      }

      .app-topbar .btn,
      .app-topbar .btn-link {
        border-radius: 14px;
      }

      .app-main-panel {
        border-radius: 32px;
        padding: 24px;
        background: rgba(255, 255, 255, 0.18);
        position: relative;
        z-index: 1;
      }

      .card {
        border-radius: var(--app-radius);
        background: var(--app-surface);
        border-color: var(--app-border) !important;
      }

      .card-header {
        background: transparent !important;
        border-bottom: 1px solid var(--app-border) !important;
        padding: 18px 22px 0;
      }

      .card-body {
        padding: 22px;
      }

      .table-wrap {
        overflow: hidden;
        border-radius: var(--app-radius);
        background: var(--app-surface);
      }

      .table {
        margin-bottom: 0;
        --bs-table-bg: transparent;
        --bs-table-color: var(--app-text);
        --bs-table-border-color: var(--app-border);
        --bs-table-striped-bg: rgba(148, 163, 184, 0.06);
        --bs-table-hover-bg: rgba(15, 98, 254, 0.05);
      }

      .table > :not(caption) > * > * {
        padding: 1rem 1.1rem;
      }

      .table thead th {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--app-muted);
        border-bottom-width: 1px;
      }

      .form-control,
      .form-select,
      .btn,
      .alert {
        border-radius: 16px;
      }

      .form-control,
      .form-select {
        min-height: 50px;
        background: var(--app-surface-muted) !important;
        color: var(--app-text) !important;
        border-color: var(--app-border) !important;
      }

      textarea.form-control {
        min-height: 130px;
      }

      .form-control:focus,
      .form-select:focus {
        border-color: rgba(15, 98, 254, 0.55) !important;
        box-shadow: 0 0 0 0.24rem rgba(15, 98, 254, 0.16) !important;
      }

      .btn {
        font-weight: 700;
        border-width: 1px;
        padding: 0.72rem 1rem;
      }

      .btn-primary {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-strong));
        border-color: transparent;
      }

      .btn-outline-secondary,
      .btn-outline-primary,
      .btn-outline-danger {
        border-color: var(--app-border-strong);
      }

      .alert {
        background: var(--app-surface);
        color: var(--app-text);
      }

      .text-muted,
      .form-text,
      .small,
      .navbar-text {
        color: var(--app-muted) !important;
      }

      .badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.4rem 0.72rem;
        border-radius: 999px;
        background: rgba(15, 98, 254, 0.08);
        color: var(--app-primary);
        font-size: 0.82rem;
        font-weight: 700;
      }

      .app-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
      }

      .app-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
      }

      .surface-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: var(--app-surface-muted);
        border: 1px solid var(--app-border);
        font-size: 0.86rem;
      }

      .ops-hero {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 28px;
        background:
          linear-gradient(135deg, rgba(15, 98, 254, 0.12), rgba(15, 157, 119, 0.08)),
          var(--app-surface);
        border: 1px solid var(--app-border);
      }

      .ops-hero::after {
        content: "";
        position: absolute;
        inset: auto -70px -90px auto;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(15, 98, 254, 0.18), rgba(15, 98, 254, 0));
        pointer-events: none;
      }

      .ops-grid {
        display: grid;
        gap: 16px;
      }

      .ops-kpi-card {
        height: 100%;
        border-radius: 24px;
        padding: 20px 22px;
        background: linear-gradient(180deg, rgba(255,255,255,0.34), rgba(255,255,255,0.08));
        border: 1px solid var(--app-border);
      }

      .ops-kpi-label {
        font-size: 0.8rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--app-muted);
        margin-bottom: 10px;
      }

      .ops-kpi-value {
        font-size: clamp(1.8rem, 2.8vw, 2.5rem);
        line-height: 1;
        font-weight: 800;
      }

      .ops-kpi-meta {
        margin-top: 12px;
        color: var(--app-muted);
        font-size: 0.9rem;
      }

      .ops-panel-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
      }

      .ops-panel-title h5,
      .ops-panel-title h6 {
        margin: 0;
      }

      .ops-subtle-list {
        display: grid;
        gap: 12px;
      }

      .ops-subtle-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 12px 14px;
        border-radius: 18px;
        background: rgba(148, 163, 184, 0.08);
      }

      .ops-subtle-item strong {
        font-size: 1rem;
      }

      .ops-table-card .card-header {
        padding-bottom: 16px;
      }

      .ops-tab-nav {
        gap: 10px;
        border-bottom: 1px solid var(--app-border);
        padding-bottom: 14px;
      }

      .ops-tab-nav .nav-link {
        border: 1px solid var(--app-border);
        border-radius: 999px;
        color: var(--app-muted);
        background: var(--app-surface-muted);
        font-weight: 700;
        padding: 10px 14px;
      }

      .ops-tab-nav .nav-link.active {
        color: #fff;
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-strong));
        border-color: transparent;
      }

      .ops-stock-meter {
        min-width: 180px;
      }

      .ops-stock-meter .progress {
        height: 26px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.16);
        overflow: hidden;
      }

      .ops-stock-meter .progress-bar {
        min-width: 46px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.82rem;
        padding-inline: 10px;
      }

      .ops-stock-meter.is-low .progress-bar {
        background: linear-gradient(135deg, #ef4444, #dc2626);
      }

      .ops-stock-meter:not(.is-low) .progress-bar {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-strong));
      }

      .notification-trigger {
        position: relative;
        border: 1px solid var(--app-border);
        background: var(--app-surface-muted);
      }

      .notification-count {
        position: absolute;
        inset: -8px -6px auto auto;
        min-width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: var(--app-danger);
        color: white;
        font-size: 0.72rem;
        font-weight: 800;
      }

      .notification-menu {
        width: min(420px, 92vw);
        padding: 0;
        border: 1px solid var(--app-border);
        background: var(--app-surface-strong);
        box-shadow: var(--app-shadow);
        border-radius: 20px;
        overflow: hidden;
        z-index: 1085;
      }

      .notification-item {
        display: block;
        padding: 14px 16px;
        border-bottom: 1px solid var(--app-border);
      }

      .notification-item:last-child {
        border-bottom: 0;
      }

      .notification-item.unread {
        background: rgba(15, 98, 254, 0.06);
      }

      @media (max-width: 991.98px) {
        .app-shell {
          padding: 12px;
        }

        .app-frame,
        .app-login-wrap {
          grid-template-columns: 1fr;
        }

        .app-sidebar {
          position: static;
          min-height: auto;
        }

        .app-main-panel {
          padding: 16px;
        }
      }

      @media print {
        body {
          background: #ffffff !important;
          color: #000000 !important;
        }

        .app-shell,
        .app-main-panel {
          padding: 0 !important;
          margin: 0 !important;
          background: transparent !important;
          box-shadow: none !important;
          border: 0 !important;
        }

        .app-frame {
          display: block !important;
        }

        .app-sidebar,
        .app-topbar,
        .flash-messages,
        .print-hidden {
          display: none !important;
        }

        .card,
        .card-body {
          box-shadow: none !important;
          border: 0 !important;
          background: #ffffff !important;
        }

        a {
          color: #000000 !important;
          text-decoration: none !important;
        }
      }
    </style>
</head>
<body data-theme="<?= e($currentTheme) ?>">
<?php if ($currentRoute === 'login'): ?>
  <div class="app-login-shell">
    <div class="app-login-wrap">
      <section class="app-auth-panel">
        <div class="badge-soft mb-4"><i class="bi bi-shield-check"></i> <?= e(__('layout.control_center', 'Asset Control Center')) ?></div>
        <h1 class="display-6 fw-bold mb-3"><?= e(__('app.name', 'Asset Management')) ?></h1>
        <p class="mb-4" style="max-width: 36rem; color: rgba(248, 250, 252, 0.85);">
          <?= e(__('layout.login_desc', 'Monitor procurement, assignment, branch movement, and warranty follow-up from one clean workspace built for daily operations.')) ?>
        </p>
        <div class="row g-3">
          <div class="col-sm-6">
              <div class="surface-chip" style="background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.16); color: #fff;">
              <i class="bi bi-hdd-network"></i> <?= e(__('layout.login_chip_assignments', 'Branch-aware assignments')) ?>
              </div>
          </div>
          <div class="col-sm-6">
              <div class="surface-chip" style="background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.16); color: #fff;">
              <i class="bi bi-file-earmark-text"></i> <?= e(__('layout.login_chip_documents', 'Documents and warranty tracking')) ?>
              </div>
          </div>
          <div class="col-sm-6">
              <div class="surface-chip" style="background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.16); color: #fff;">
              <i class="bi bi-people"></i> <?= e(__('layout.login_chip_people', 'Employee and branch visibility')) ?>
              </div>
          </div>
          <div class="col-sm-6">
              <div class="surface-chip" style="background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.16); color: #fff;">
              <i class="bi bi-bar-chart"></i> <?= e(__('layout.login_chip_dashboard', 'Operations dashboard')) ?>
              </div>
          </div>
        </div>
      </section>
      <main class="app-auth-card">
        <?php if ($flashStatus !== null): ?>
          <div class="alert alert-success mb-4"><?= e($flashStatus) ?></div>
        <?php endif; ?>
        <?php if ($flashError !== null): ?>
          <div class="alert alert-danger mb-4"><?= e($flashError) ?></div>
        <?php endif; ?>
        <?= $content ?>
      </main>
    </div>
  </div>
<?php else: ?>
  <div class="app-shell">
    <div class="app-frame">
      <aside class="app-sidebar">
        <?php require base_path('resources/views/layouts/sidebar.blade.php'); ?>
      </aside>
      <div class="app-content">
        <header class="app-topbar">
          <div class="app-toolbar">
            <div>
              <div class="badge-soft mb-2"><i class="bi bi-grid-1x2"></i> <?= e(__('layout.workspace_badge', 'Operations workspace')) ?></div>
              <h2 class="h4 mb-1"><?= e($pageTitle) ?></h2>
              <p class="text-muted mb-0"><?= e($currentUser['name'] ?? '') ?></p>
            </div>
            <div class="app-toolbar-actions">
              <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'en'])) ?>"><?= e(__('lang.en', 'English')) ?></a>
              <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'ar'])) ?>"><?= e(__('lang.ar', 'Arabic')) ?></a>
              <?php if ($currentUser !== null): ?>
                <div class="dropdown">
                  <button class="surface-chip notification-trigger dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell"></i>
                    <?= e(__('notifications.title', 'Notifications')) ?>
                    <?php if (($unreadNotifications ?? 0) > 0): ?>
                      <span class="notification-count"><?= e((string) $unreadNotifications) ?></span>
                    <?php endif; ?>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="d-flex justify-content-between align-items-center px-3 py-3 border-bottom" style="border-color: var(--app-border) !important;">
                      <div class="fw-semibold"><?= e(__('notifications.title', 'Notifications')) ?></div>
                      <?php if (!empty($notifications)): ?>
                        <form method="POST" action="<?= e(route('notifications.read_all')) ?>">
                          <button type="submit" class="btn btn-sm btn-outline-secondary"><?= e(__('notifications.read_all', 'Mark all read')) ?></button>
                        </form>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($notifications)): ?>
                      <?php foreach ($notifications as $notification): ?>
                        <?php $target = $notification['data']['route'] ?? $notification['data']['url'] ?? route('dashboard'); ?>
                        <a href="<?= e($target) ?>" class="notification-item <?= empty($notification['read_at']) ? 'unread' : '' ?>">
                          <div class="d-flex justify-content-between gap-3">
                            <div>
                              <div class="fw-semibold"><?= e((string) ($notification['data']['title'] ?? __('notifications.title', 'Notification'))) ?></div>
                              <div class="small text-muted mt-1"><?= e((string) ($notification['data']['message'] ?? '')) ?></div>
                            </div>
                            <div class="small text-muted text-nowrap"><?= e((string) $notification['created_at']) ?></div>
                          </div>
                        </a>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <div class="px-3 py-4 text-muted"><?= e(__('notifications.empty', 'No notifications right now.')) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <form method="POST" action="<?= e(route('theme.toggle')) ?>" class="d-inline">
                  <button type="submit" class="surface-chip" style="cursor:pointer;">
                    <i class="bi bi-circle-half"></i>
                    <?= e(__('theme.toggle', 'Theme')) ?>: <?= e($currentTheme === 'dark' ? __('theme.dark', 'Dark') : __('theme.light', 'Light')) ?>
                  </button>
                </form>
                <form method="POST" action="<?= e(route('logout')) ?>" class="d-inline">
                  <button type="submit" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> <?= e(__('auth.logout', 'Logout')) ?></button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </header>
        <main class="app-main-panel">
          <?php if ($flashStatus !== null): ?>
            <div class="alert alert-success mb-4"><?= e($flashStatus) ?></div>
          <?php endif; ?>
          <?php if ($flashError !== null): ?>
            <div class="alert alert-danger mb-4"><?= e($flashError) ?></div>
          <?php endif; ?>
          <?= $content ?>
        </main>
      </div>
    </div>
  </div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
