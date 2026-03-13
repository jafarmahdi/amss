<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="mb-1"><?= e(__('requests.title', 'Purchase Requests')) ?></h2>
        <p class="text-muted mb-0"><?= e(__('requests.desc', 'Track draft requests, approvals, and full request timelines.')) ?></p>
    </div>
    <a href="<?= e(route('requests.create')) ?>" class="btn btn-primary"><?= e(__('requests.add', 'New Request')) ?></a>
</div>

<div class="row g-3 mb-4">
    <?php foreach (['draft', 'pending_it', 'pending_it_manager', 'pending_finance', 'approved'] as $summaryStatus): ?>
        <div class="col-md-4 col-xl">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= e($statuses[$summaryStatus]['label'] ?? $summaryStatus) ?></div>
                    <div class="fs-3 fw-bold"><?= e((string) ($summary[$summaryStatus] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= e(route('requests.index')) ?>" class="row g-3 align-items-end">
            <input type="hidden" name="route" value="/requests">
            <div class="col-md-6">
                <label class="form-label" for="q"><?= e(__('common.search', 'Search')) ?></label>
                <input type="text" class="form-control" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="<?= e(__('requests.search_placeholder', 'Request number, title, requester, employee')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status"><?= e(__('common.status', 'Status')) ?></label>
                <select class="form-select" id="status" name="status">
                    <option value=""><?= e(__('common.all', 'All')) ?></option>
                    <?php foreach ($statuses as $statusKey => $statusMeta): ?>
                        <option value="<?= e($statusKey) ?>" <?= ($filters['status'] ?? '') === $statusKey ? 'selected' : '' ?>><?= e($statusMeta['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="mine" name="mine" <?= ($filters['mine'] ?? '') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="mine"><?= e(__('requests.mine_only', 'My requests only')) ?></label>
                </div>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-secondary w-100"><?= e(__('common.search', 'Search')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th><?= e(__('requests.number', 'Request No')) ?></th>
                    <th><?= e(__('common.name', 'Name')) ?></th>
                    <th><?= e(__('requests.requested_by', 'Requested By')) ?></th>
                    <th><?= e(__('requests.requested_for', 'Requested For')) ?></th>
                    <th><?= e(__('requests.request_type', 'Request Type')) ?></th>
                    <th><?= e(__('common.status', 'Status')) ?></th>
                    <th><?= e(__('requests.pending_with', 'Pending With')) ?></th>
                    <th><?= e(__('common.date', 'Date')) ?></th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests !== []): ?>
                    <?php foreach ($requests as $request): ?>
                        <?php $statusMeta = $statuses[$request['status']] ?? ['label' => $request['status'], 'badge' => 'secondary']; ?>
                        <tr>
                            <td class="fw-semibold"><?= e($request['request_no']) ?></td>
                            <td>
                                <a href="<?= e(route('requests.show', ['id' => $request['id']])) ?>" class="text-decoration-none fw-semibold"><?= e($request['title']) ?></a>
                                <div class="small text-muted"><?= e($request['branch_name'] ?: __('form.no_branch', 'No Branch')) ?></div>
                            </td>
                            <td><?= e($request['requested_by_name']) ?></td>
                            <td><?= e($request['requested_for_name']) ?></td>
                            <td><?= e($requestTypes[$request['request_type']] ?? $request['request_type']) ?></td>
                            <td><span class="badge text-bg-<?= e($statusMeta['badge']) ?>"><?= e($statusMeta['label']) ?></span></td>
                            <td>
                                <?= e($request['pending_user_name'] ?: ($pendingLabels[$request['current_pending_role']] ?? $request['current_pending_role'])) ?>
                            </td>
                            <td><?= e($request['submitted_at'] ?: $request['created_at']) ?></td>
                            <td class="text-end">
                                <a href="<?= e(route('requests.show', ['id' => $request['id']])) ?>" class="btn btn-sm btn-outline-secondary"><?= e(__('actions.view', 'View')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-muted"><?= e(__('requests.no_results', 'No requests match the current filters.')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
