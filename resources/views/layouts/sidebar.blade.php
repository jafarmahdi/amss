<?php
$assetsParentActive = str_starts_with((string) $currentRoute, 'assets.') && $currentRoute !== 'assets.archived';
?>

<div class="app-brand">
  <div class="app-brand-mark" style="padding: 0; overflow: hidden; background: rgba(255,255,255,0.1);">
    <img src="<?= e(base_url()) ?>/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
  </div>
  <div>
    <div class="fw-bold"><?= e(setting('app_name', __('app.name', 'Asset Management')) ?? __('app.name', 'Asset Management')) ?></div>
    <div class="app-brand-subtitle"><?= e(__('sidebar.brand_subtitle', 'Inventory and movement control')) ?></div>
  </div>
</div>

<div class="small text-uppercase fw-semibold mb-3" style="letter-spacing: 0.12em; color: var(--app-sidebar-muted);"><?= e(__('nav.operations', 'Operations')) ?></div>
<nav class="d-flex flex-column gap-2">
  <?php if (can('dashboard.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'dashboard' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('dashboard')) ?>" aria-current="<?= $currentRoute === 'dashboard' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'dashboard' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-speedometer2"></i><span><?= e(__('nav.dashboard', 'Dashboard')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('assets.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $assetsParentActive ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('assets.index')) ?>" aria-current="<?= $assetsParentActive ? 'page' : 'false' ?>" style="<?= $assetsParentActive ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-laptop"></i><span><?= e(__('nav.assets', 'Assets')) ?></span>
    </a>
    <a class="app-nav-link is-child d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'assets.archived' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('assets.archived')) ?>" aria-current="<?= $currentRoute === 'assets.archived' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'assets.archived' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-archive"></i><span><?= e(__('nav.archived_assets', 'Archived Assets')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('requests.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'requests.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('requests.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'requests.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'requests.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-clipboard-check"></i><span><?= e(__('nav.requests', 'Requests')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('forms.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'administrative-forms.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('administrative-forms.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'administrative-forms.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'administrative-forms.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-folder2-open"></i><span><?= e(__('nav.administrative_forms', 'Administrative Forms')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('reports.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'reports.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('reports.index')) ?>" aria-current="<?= $currentRoute === 'reports.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'reports.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-bar-chart-line"></i><span><?= e(__('nav.reports', 'Reports')) ?></span>
    </a>
  <?php endif; ?>
</nav>

<div class="small text-uppercase fw-semibold mt-4 mb-3" style="letter-spacing: 0.12em; color: var(--app-sidebar-muted);"><?= e(__('nav.inventory', 'Inventory')) ?></div>
<nav class="d-flex flex-column gap-2">
  <?php if (can('employees.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'employees.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('employees.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'employees.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'employees.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-people"></i><span><?= e(__('nav.employees', 'Employees')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('licenses.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'licenses.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('licenses.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'licenses.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'licenses.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-key"></i><span><?= e(__('nav.licenses', 'Licenses')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('spare_parts.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'spare-parts.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('spare-parts.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'spare-parts.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'spare-parts.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-cpu"></i><span><?= e(__('nav.spare_parts', 'Spare Parts')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('branches.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'branches.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('branches.index')) ?>" aria-current="<?= $currentRoute === 'branches.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'branches.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-diagram-3"></i><span><?= e(__('nav.branches', 'Branches')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('categories.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'categories.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('categories.index')) ?>" aria-current="<?= $currentRoute === 'categories.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'categories.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-tags"></i><span><?= e(__('nav.categories', 'Categories')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('storage.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'storage.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('storage.index')) ?>" aria-current="<?= $currentRoute === 'storage.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'storage.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-archive"></i><span><?= e(__('nav.storage', 'Storage')) ?></span>
    </a>
  <?php endif; ?>
</nav>

<div class="small text-uppercase fw-semibold mt-4 mb-3" style="letter-spacing: 0.12em; color: var(--app-sidebar-muted);"><?= e(__('nav.system_section', 'System')) ?></div>
<nav class="d-flex flex-column gap-2">
  <?php if (can('users.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'users.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('users.index')) ?>" aria-current="<?= $currentRoute === 'users.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'users.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-person-gear"></i><span><?= e(__('nav.users', 'Users')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('settings.manage')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'settings' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('settings')) ?>" aria-current="<?= $currentRoute === 'settings' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'settings' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-sliders"></i><span><?= e(__('nav.settings', 'Settings')) ?></span>
    </a>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'tools.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('tools.index')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'tools.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'tools.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-arrow-left-right"></i><span><?= e(__('nav.tools', 'Import / Export')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('system.check')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'system.check' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('system.check')) ?>" aria-current="<?= $currentRoute === 'system.check' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'system.check' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-shield-check"></i><span><?= e(__('nav.system_check', 'System Check')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('api.docs')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'api.') ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('api.docs')) ?>" aria-current="<?= str_starts_with((string) $currentRoute, 'api.') ? 'page' : 'false' ?>" style="<?= str_starts_with((string) $currentRoute, 'api.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-code-square"></i><span><?= e(__('nav.api_docs', 'API Docs')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('audit.view')): ?>
    <a class="app-nav-link d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'audit.index' ? 'is-active fw-semibold' : '' ?>" href="<?= e(route('audit.index')) ?>" aria-current="<?= $currentRoute === 'audit.index' ? 'page' : 'false' ?>" style="<?= $currentRoute === 'audit.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-journal-text"></i><span><?= e(__('nav.audit', 'Audit Logs')) ?></span>
    </a>
  <?php endif; ?>
</nav>
