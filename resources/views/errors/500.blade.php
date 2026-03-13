<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <div class="text-uppercase small fw-semibold text-muted mb-3"><?= e(__('errors.500_label', 'System error')) ?></div>
                    <h1 class="display-6 fw-bold mb-3"><?= e(__('errors.500_title', 'Something went wrong while loading this page.')) ?></h1>
                    <p class="text-muted mb-4"><?= e(__('errors.500_desc', 'The request reached the system, but an unexpected error stopped it from completing.')) ?></p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="<?= e(route('dashboard')) ?>" class="btn btn-primary rounded-pill px-4"><?= e(__('errors.back_dashboard', 'Back to dashboard')) ?></a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="window.location.reload()"><?= e(__('errors.try_again', 'Try again')) ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
