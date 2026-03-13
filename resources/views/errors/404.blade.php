<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="text-uppercase small fw-semibold text-muted mb-3"><?= e(__('errors.404_label', 'Page not found')) ?></div>
                    <h1 class="display-6 fw-bold mb-3"><?= e(__('errors.404_title', 'The requested page could not be found.')) ?></h1>
                    <p class="text-muted mb-4"><?= e(__('errors.404_desc', 'The link may be broken, the page may have moved, or the route does not exist in the system.')) ?></p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="<?= e(route('dashboard')) ?>" class="btn btn-primary rounded-pill px-4"><?= e(__('errors.back_dashboard', 'Back to dashboard')) ?></a>
                        <a href="<?= e(route('assets.index')) ?>" class="btn btn-outline-secondary rounded-pill px-4"><?= e(__('nav.assets', 'Assets')) ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
