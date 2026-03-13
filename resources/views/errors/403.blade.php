<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="text-uppercase small fw-semibold text-muted mb-3"><?= e(__('errors.403_label', 'Access denied')) ?></div>
                    <h1 class="display-6 fw-bold mb-3"><?= e(__('errors.403_title', 'You do not have permission to open this page.')) ?></h1>
                    <p class="text-muted mb-4"><?= e(__('errors.403_desc', 'Your account can sign in, but this section is restricted by role permissions.')) ?></p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="<?= e(route('dashboard')) ?>" class="btn btn-primary rounded-pill px-4"><?= e(__('errors.back_dashboard', 'Back to dashboard')) ?></a>
                        <a href="<?= e(route('logout')) ?>" onclick="event.preventDefault(); document.getElementById('logout403').submit();" class="btn btn-outline-secondary rounded-pill px-4"><?= e(__('auth.logout', 'Logout')) ?></a>
                    </div>
                    <form id="logout403" method="POST" action="<?= e(route('logout')) ?>" class="d-none"></form>
                </div>
            </div>
        </div>
    </div>
</div>
