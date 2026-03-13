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
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'dashboard' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('dashboard')) ?>" style="<?= $currentRoute === 'dashboard' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-speedometer2"></i><span><?= e(__('nav.dashboard', 'Dashboard')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('assets.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'assets.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('assets.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'assets.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-laptop"></i><span><?= e(__('nav.assets', 'Assets')) ?></span>
    </a>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'assets.archived' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('assets.archived')) ?>" style="<?= $currentRoute === 'assets.archived' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04); margin-inline-start: 1.5rem;' ?>">
      <i class="bi bi-archive"></i><span><?= e(__('nav.archived_assets', 'Archived Assets')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('requests.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'requests.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('requests.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'requests.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-clipboard-check"></i><span><?= e(__('nav.requests', 'Requests')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('forms.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'administrative-forms.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('administrative-forms.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'administrative-forms.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-folder2-open"></i><span><?= e(__('nav.administrative_forms', 'Administrative Forms')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('reports.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'reports.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('reports.index')) ?>" style="<?= $currentRoute === 'reports.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-bar-chart-line"></i><span><?= e(__('nav.reports', 'Reports')) ?></span>
    </a>
  <?php endif; ?>
</nav>

<div class="small text-uppercase fw-semibold mt-4 mb-3" style="letter-spacing: 0.12em; color: var(--app-sidebar-muted);"><?= e(__('nav.inventory', 'Inventory')) ?></div>
<nav class="d-flex flex-column gap-2">
  <?php if (can('employees.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'employees.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('employees.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'employees.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-people"></i><span><?= e(__('nav.employees', 'Employees')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('licenses.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'licenses.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('licenses.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'licenses.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-key"></i><span><?= e(__('nav.licenses', 'Licenses')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('spare_parts.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'spare-parts.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('spare-parts.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'spare-parts.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-cpu"></i><span><?= e(__('nav.spare_parts', 'Spare Parts')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('branches.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'branches.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('branches.index')) ?>" style="<?= $currentRoute === 'branches.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-diagram-3"></i><span><?= e(__('nav.branches', 'Branches')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('categories.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'categories.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('categories.index')) ?>" style="<?= $currentRoute === 'categories.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-tags"></i><span><?= e(__('nav.categories', 'Categories')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('storage.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'storage.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('storage.index')) ?>" style="<?= $currentRoute === 'storage.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-archive"></i><span><?= e(__('nav.storage', 'Storage')) ?></span>
    </a>
  <?php endif; ?>
</nav>

<div class="small text-uppercase fw-semibold mt-4 mb-3" style="letter-spacing: 0.12em; color: var(--app-sidebar-muted);"><?= e(__('nav.system_section', 'System')) ?></div>
<nav class="d-flex flex-column gap-2">
  <?php if (can('users.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'users.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('users.index')) ?>" style="<?= $currentRoute === 'users.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-person-gear"></i><span><?= e(__('nav.users', 'Users')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('settings.manage')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'settings' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('settings')) ?>" style="<?= $currentRoute === 'settings' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-sliders"></i><span><?= e(__('nav.settings', 'Settings')) ?></span>
    </a>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'tools.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('tools.index')) ?>" style="<?= str_starts_with((string) $currentRoute, 'tools.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-arrow-left-right"></i><span><?= e(__('nav.tools', 'Import / Export')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('system.check')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'system.check' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('system.check')) ?>" style="<?= $currentRoute === 'system.check' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-shield-check"></i><span><?= e(__('nav.system_check', 'System Check')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('api.docs')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= str_starts_with((string) $currentRoute, 'api.') ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('api.docs')) ?>" style="<?= str_starts_with((string) $currentRoute, 'api.') ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-code-square"></i><span><?= e(__('nav.api_docs', 'API Docs')) ?></span>
    </a>
  <?php endif; ?>
  <?php if (can('audit.view')): ?>
    <a class="d-flex align-items-center gap-3 px-3 py-2 rounded-4 <?= $currentRoute === 'audit.index' ? 'bg-white text-dark fw-semibold' : '' ?>" href="<?= e(route('audit.index')) ?>" style="<?= $currentRoute === 'audit.index' ? '' : 'color: var(--app-sidebar-text); background: rgba(255,255,255,0.04);' ?>">
      <i class="bi bi-journal-text"></i><span><?= e(__('nav.audit', 'Audit Logs')) ?></span>
    </a>
  <?php endif; ?>
</nav>
