<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="badge-soft mb-3"><i class="bi bi-arrow-left-right"></i> <?= e(__('nav.tools', 'Import / Export')) ?></div>
        <h2 class="mb-1"><?= e(__('tools.title', 'Import / Export Tools')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('tools.desc', 'Move structured data in and out of Alnahala AMS with CSV templates and exports.')) ?></p>
    </div>
    <a href="<?= e(route('settings')) ?>" class="btn btn-outline-secondary"><?= e(__('nav.settings', 'Settings')) ?></a>
</div>

<div class="row g-4">
    <?php foreach ($datasets as $key => $dataset): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= e($dataset['title']) ?></h5>
                    <span class="text-muted small"><?= e(implode(', ', $dataset['headers'])) ?></span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="<?= e(route('tools.template', ['dataset' => $key])) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('tools.download_template', 'Download Template')) ?></a>
                        <a href="<?= e(route('tools.export', ['dataset' => $key])) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('tools.export_current', 'Export Current Data')) ?></a>
                    </div>
                    <div class="small text-muted mb-3"><?= e(__('tools.import_help', 'Upload a CSV file with the same header order as the template. Existing rows are updated by their natural key; new rows are created.')) ?></div>
                    <form method="post" action="<?= e(route('tools.import', ['dataset' => $key])) ?>" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-8">
                            <input type="file" name="import_file" accept=".csv,text/csv" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100"><?= e(__('tools.import_now', 'Import Now')) ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
