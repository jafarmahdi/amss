<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <div class="badge-soft mb-3"><i class="bi bi-code-square"></i> <?= e(__('api.docs', 'API Documentation')) ?></div>
        <h2 class="mb-1"><?= e(__('api.docs', 'API Documentation')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('api.docs_desc', 'Interactive Swagger UI for the asset management routes and payloads.')) ?></p>
    </div>
    <a href="<?= e($specUrl) ?>" class="btn btn-outline-secondary" target="_blank" rel="noreferrer">Open JSON</a>
</div>

<?php if ($integrationKey !== null): ?>
    <div class="alert alert-info mb-4">
        <div class="fw-semibold mb-2">Integration API Key</div>
        <div class="small mb-2">Use this value in the Swagger Authorize dialog as `X-API-Key` for external apps and fingerprint devices.</div>
        <code><?= e($integrationKey) ?></code>
    </div>
<?php endif; ?>

<div class="card p-0 overflow-hidden">
    <div id="swagger-ui"></div>
</div>

<link rel="stylesheet" href="<?= e(base_url()) ?>/swagger-ui-5.32.0/dist/swagger-ui.css">
<script src="<?= e(base_url()) ?>/swagger-ui-5.32.0/dist/swagger-ui-bundle.js"></script>
<script src="<?= e(base_url()) ?>/swagger-ui-5.32.0/dist/swagger-ui-standalone-preset.js"></script>
<script>
window.onload = function () {
    window.ui = SwaggerUIBundle({
        url: <?= json_encode($specUrl) ?>,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        layout: 'BaseLayout'
    });
};
</script>
