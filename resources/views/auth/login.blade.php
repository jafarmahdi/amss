<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <div class="mb-3" style="width: 88px; height: 88px; border-radius: 24px; overflow: hidden; border: 1px solid var(--app-border); background: var(--app-surface-muted);">
            <img src="<?= e(base_url()) ?>/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="badge-soft mb-3"><i class="bi bi-door-open"></i> Secure access</div>
        <h1 class="h2 mb-2"><?= e(setting('app_name', __('auth.login', 'Login')) ?? __('auth.login', 'Login')) ?></h1>
        <p class="text-muted mb-0">Sign in to manage assets, employees, branches, and movement records.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'en'])) ?>"><?= e(__('lang.en', 'English')) ?></a>
        <a class="surface-chip" href="<?= e(route('locale.switch', ['locale' => 'ar'])) ?>"><?= e(__('lang.ar', 'Arabic')) ?></a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= e(route('login.attempt')) ?>">
    <div class="mb-3">
        <label class="form-label fw-semibold" for="email"><?= e(__('auth.email', 'Email')) ?></label>
        <input class="form-control <?= has_error('email') ? 'is-invalid' : '' ?>" type="email" id="email" name="email" value="<?= e((string) old('email', '')) ?>" placeholder="name@alnahala.com" required>
        <?php if (has_error('email')): ?><div class="invalid-feedback"><?= e((string) field_error('email')) ?></div><?php endif; ?>
    </div>
    <div class="mb-4">
        <label class="form-label fw-semibold" for="password"><?= e(__('auth.password', 'Password')) ?></label>
        <input class="form-control <?= has_error('password') ? 'is-invalid' : '' ?>" type="password" id="password" name="password" placeholder="••••••••" required>
        <?php if (has_error('password')): ?><div class="invalid-feedback"><?= e((string) field_error('password')) ?></div><?php endif; ?>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-3"><?= e(__('auth.sign_in', 'Sign In')) ?></button>
</form>
