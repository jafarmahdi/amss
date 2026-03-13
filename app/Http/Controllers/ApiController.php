<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\DataRepository;
use App\Support\RequestWorkflow;

class ApiController extends Controller
{
    public function authLogin(): void
    {
        if (!$this->mobileApiEnabled()) {
            $this->jsonError('Mobile API is disabled.', 403);
            return;
        }

        $payload = $this->requestPayload();
        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->jsonError('Email and password are required.', 422);
            return;
        }

        $user = DataRepository::findUserByEmail($email);
        if (
            $user === null ||
            ($user['status'] ?? 'active') !== 'active' ||
            !password_verify($password, (string) ($user['password'] ?? ''))
        ) {
            $this->jsonError('Invalid email or password.', 401);
            return;
        }

        $token = DataRepository::issueApiToken((int) $user['id']);

        $this->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $this->serializeUser($user),
            'permissions' => $this->userPermissions($user),
            'unread_notifications' => DataRepository::unreadNotificationsCountForUser((int) $user['id']),
        ]);
    }

    public function authLogout(): void
    {
        $user = $this->requireBearerUser();
        if ($user === null) {
            return;
        }

        DataRepository::revokeApiToken((int) $user['id']);

        $this->json([
            'message' => 'API token revoked successfully.',
        ]);
    }

    public function me(): void
    {
        $user = $this->requireBearerUser();
        if ($user === null) {
            return;
        }

        $this->json([
            'user' => $this->serializeUser($user),
            'permissions' => $this->userPermissions($user),
            'unread_notifications' => DataRepository::unreadNotificationsCountForUser((int) $user['id']),
        ]);
    }

    public function notifications(): void
    {
        $user = $this->requireBearerUser();
        if ($user === null) {
            return;
        }

        $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));

        $this->json([
            'items' => DataRepository::notificationsForUser((int) $user['id'], $limit),
            'unread_count' => DataRepository::unreadNotificationsCountForUser((int) $user['id']),
        ]);
    }

    public function dashboard(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json([
            'stats' => DataRepository::dashboardStats(),
            'overview' => DataRepository::dashboardOverview(),
            'charts' => DataRepository::dashboardCharts(),
        ]);
    }

    public function assets(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json(DataRepository::assets());
    }

    public function asset(string $id): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $asset = DataRepository::findAsset((int) $id);
        if ($asset === null) {
            $this->jsonError('Asset not found.', 404);
            return;
        }

        $asset['assignments'] = DataRepository::assetAssignments((int) $id);
        $asset['movements'] = DataRepository::assetMovements((int) $id);
        $asset['repairs'] = DataRepository::assetRepairs((int) $id);
        $asset['handovers'] = DataRepository::assetHandovers((int) $id);
        $asset['maintenance'] = DataRepository::assetMaintenance((int) $id);
        $this->json($asset);
    }

    public function branches(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json(DataRepository::branches());
    }

    public function branch(string $id): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $branch = DataRepository::branchDetail((int) $id);
        if ($branch === null) {
            $this->jsonError('Branch not found.', 404);
            return;
        }

        $branch['employees'] = DataRepository::branchEmployees((int) $id);
        $branch['assets'] = DataRepository::branchAssets((int) $id);
        $this->json($branch);
    }

    public function categories(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json(DataRepository::categories());
    }

    public function employees(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json(DataRepository::employees());
    }

    public function employee(string $id): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $employee = DataRepository::findEmployee((int) $id);
        if ($employee === null) {
            $this->jsonError('Employee not found.', 404);
            return;
        }

        $employee['assets'] = DataRepository::employeeAssetAssignments((int) $id);
        $employee['licenses'] = DataRepository::employeeLicenseAssignments((int) $id);
        $employee['offboarding'] = DataRepository::employeeOffboardingHistory((int) $id);
        $this->json($employee);
    }

    public function employeeLookup(): void
    {
        if ($this->requireApiAccess(true, false, 'fingerprint') === null) {
            return;
        }

        $fingerprintId = trim((string) ($_GET['fingerprint_id'] ?? ''));
        $employeeCode = trim((string) ($_GET['employee_code'] ?? ''));
        $companyEmail = trim((string) ($_GET['company_email'] ?? ''));

        $employee = $fingerprintId !== ''
            ? DataRepository::findEmployeeByFingerprintId($fingerprintId)
            : ($employeeCode !== ''
                ? DataRepository::findEmployeeByEmployeeCode($employeeCode)
                : ($companyEmail !== '' ? DataRepository::findEmployeeByCompanyEmail($companyEmail) : null));

        if ($employee === null) {
            $this->jsonError('Employee not found for the provided identifier.', 404);
            return;
        }

        $this->json([
            'employee' => $employee,
        ]);
    }

    public function fingerprintEmployees(): void
    {
        if ($this->requireApiAccess(true, false, 'fingerprint') === null) {
            return;
        }

        $items = DataRepository::fingerprintEmployees();

        $this->json([
            'count' => count($items),
            'items' => $items,
        ]);
    }

    public function fingerprintEventStore(): void
    {
        if ($this->requireApiAccess(true, false, 'fingerprint') === null) {
            return;
        }

        $payload = $this->requestPayload();
        $fingerprintId = trim((string) ($payload['fingerprint_id'] ?? ''));
        $employeeCode = trim((string) ($payload['employee_code'] ?? ''));
        $companyEmail = trim((string) ($payload['company_email'] ?? ''));

        if ($fingerprintId === '' && $employeeCode === '' && $companyEmail === '') {
            $this->jsonError('At least one employee identifier is required.', 422);
            return;
        }

        $employee = $fingerprintId !== ''
            ? DataRepository::findEmployeeByFingerprintId($fingerprintId)
            : ($employeeCode !== ''
                ? DataRepository::findEmployeeByEmployeeCode($employeeCode)
                : DataRepository::findEmployeeByCompanyEmail($companyEmail));

        try {
            $event = DataRepository::recordFingerprintEvent([
                'device_id' => trim((string) ($payload['device_id'] ?? '')),
                'device_name' => trim((string) ($payload['device_name'] ?? '')),
                'event_type' => trim((string) ($payload['event_type'] ?? 'check')),
                'event_time' => trim((string) ($payload['event_time'] ?? '')) ?: date('Y-m-d H:i:s'),
                'fingerprint_id' => $fingerprintId,
                'employee_code' => $employeeCode,
                'company_email' => $companyEmail,
                'matched_employee_id' => (int) ($employee['id'] ?? 0),
                'matched_employee_name' => (string) ($employee['name'] ?? ''),
                'raw_payload' => $payload,
            ]);
        } catch (\Throwable $exception) {
            app_log_exception($exception, ['channel' => 'fingerprint_api']);
            $this->jsonError($exception->getMessage(), 500);
            return;
        }

        $this->json([
            'stored' => true,
            'event' => $event,
            'matched' => $employee !== null,
            'employee' => $employee,
        ], 201);
    }

    public function licenses(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json(DataRepository::licenses());
    }

    public function license(string $id): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $license = DataRepository::licenseDetail((int) $id);
        if ($license === null) {
            $this->jsonError('License not found.', 404);
            return;
        }

        $license['renewals'] = DataRepository::licenseRenewals((int) $id);
        $this->json($license);
    }

    public function spareParts(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json([
            'summary' => DataRepository::sparePartsSummary(),
            'items' => DataRepository::spareParts(),
        ]);
    }

    public function reports(): void
    {
        if ($this->requireApiAccess() === null) {
            return;
        }

        $this->json([
            'summary' => DataRepository::reportSummary(),
            'dashboard' => DataRepository::dashboardOverview(),
        ]);
    }

    public function requests(): void
    {
        $access = $this->requireApiAccess(true, true, 'mobile');
        if ($access === null) {
            return;
        }

        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'mine' => trim((string) ($_GET['mine'] ?? '')),
        ];
        $viewer = $this->requestViewerContext($access['user']);

        $this->json([
            'items' => RequestWorkflow::requests($filters, $viewer),
            'summary' => RequestWorkflow::summary($viewer),
        ]);
    }

    public function request(string $id): void
    {
        $access = $this->requireApiAccess(true, true, 'mobile');
        if ($access === null) {
            return;
        }

        $request = RequestWorkflow::find((int) $id);
        if ($request === null) {
            $this->jsonError('Request not found.', 404);
            return;
        }

        if ($access['user'] !== null && !$this->canApiUserViewRequest($access['user'], $request)) {
            $this->jsonError('You do not have permission to view this request.', 403);
            return;
        }

        $request['items'] = RequestWorkflow::requestItems((int) $id);
        $request['timeline'] = RequestWorkflow::timeline((int) $id);
        $request['approvals'] = RequestWorkflow::approvals((int) $id);

        $this->json($request);
    }

    public function createRequest(): void
    {
        $user = $this->requireBearerUser();
        if ($user === null) {
            return;
        }

        if (!$this->mobileApiEnabled()) {
            $this->jsonError('Mobile API is disabled.', 403);
            return;
        }

        if (!$this->userHasPermission($user, 'requests.manage')) {
            $this->jsonError('You do not have permission to create requests.', 403);
            return;
        }

        $payload = $this->requestPayload();
        if (trim((string) ($payload['title'] ?? '')) === '') {
            $this->jsonError('Request title is required.', 422);
            return;
        }

        try {
            $requestId = RequestWorkflow::create($payload, (int) $user['id'], !empty($payload['submit_now']));
            $request = RequestWorkflow::find($requestId);
        } catch (\Throwable $exception) {
            app_log_exception($exception, ['channel' => 'api_requests']);
            $this->jsonError($exception->getMessage(), 500);
            return;
        }

        $this->json([
            'message' => 'Request created successfully.',
            'request_id' => $requestId,
            'request_no' => (string) ($request['request_no'] ?? ''),
            'request' => $request,
        ], 201);
    }

    private function requireApiAccess(bool $allowApiKey = true, bool $allowBearer = true, string $capability = 'general'): ?array
    {
        $settings = DataRepository::systemSettings();
        if (($settings['api_enabled'] ?? '1') !== '1') {
            $this->jsonError('API access is disabled.', 403);
            return null;
        }

        if ($capability === 'mobile' && ($settings['mobile_api_enabled'] ?? '1') !== '1') {
            $this->jsonError('Mobile API access is disabled.', 403);
            return null;
        }

        if ($capability === 'fingerprint' && ($settings['fingerprint_api_enabled'] ?? '1') !== '1') {
            $this->jsonError('Fingerprint API access is disabled.', 403);
            return null;
        }

        $user = $allowBearer ? $this->apiUserFromBearer() : null;
        if ($user !== null) {
            return ['user' => $user, 'via' => 'bearer'];
        }

        if ($allowApiKey && $this->hasValidApiKey()) {
            return ['user' => null, 'via' => 'api_key'];
        }

        $this->jsonError('Unauthorized API request.', 401);
        return null;
    }

    private function requireBearerUser(): ?array
    {
        $settings = DataRepository::systemSettings();
        if (($settings['api_enabled'] ?? '1') !== '1') {
            $this->jsonError('API access is disabled.', 403);
            return null;
        }

        if (($settings['mobile_api_enabled'] ?? '1') !== '1') {
            $this->jsonError('Mobile API access is disabled.', 403);
            return null;
        }

        $user = $this->apiUserFromBearer();
        if ($user === null) {
            $this->jsonError('A valid bearer token is required.', 401);
            return null;
        }

        return $user;
    }

    private function apiUserFromBearer(): ?array
    {
        $token = $this->bearerToken();
        if ($token === '') {
            return null;
        }

        $user = DataRepository::findUserByApiToken($token);
        if ($user === null || ($user['status'] ?? 'active') !== 'active') {
            return null;
        }

        return $user;
    }

    private function hasValidApiKey(): bool
    {
        $provided = trim((string) $this->header('X-API-Key'));
        if ($provided === '') {
            return false;
        }

        return hash_equals(DataRepository::integrationApiKey(), $provided);
    }

    private function bearerToken(): string
    {
        $header = trim((string) $this->header('Authorization'));
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private function header(string $name): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strcasecmp((string) $key, $name) === 0) {
                    return is_array($value) ? implode(', ', $value) : (string) $value;
                }
            }
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$serverKey])) {
            return (string) $_SERVER[$serverKey];
        }

        if ($name === 'Authorization' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return null;
    }

    private function requestPayload(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw !== false ? $raw : '[]', true);
            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private function serializeUser(array $user): array
    {
        return [
            'id' => (int) ($user['id'] ?? 0),
            'name' => (string) ($user['name'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'status' => (string) ($user['status'] ?? ''),
            'locale' => (string) ($user['locale'] ?? ''),
            'theme' => (string) ($user['theme'] ?? ''),
        ];
    }

    private function userPermissions(array $user): array
    {
        $role = (string) ($user['role'] ?? '');
        $matrix = DataRepository::rolePermissions();

        if ($role === 'admin') {
            $permissions = array_fill_keys(array_keys(DataRepository::permissionDefinitions()), true);
        } else {
            $permissions = $matrix[$role] ?? [];
        }

        return array_keys(array_filter($permissions, static fn (bool $allowed): bool => $allowed));
    }

    private function userHasPermission(array $user, string $permission): bool
    {
        if (($user['role'] ?? '') === 'admin') {
            return true;
        }

        $matrix = DataRepository::rolePermissions();
        return (bool) ($matrix[(string) ($user['role'] ?? '')][$permission] ?? false);
    }

    private function requestViewerContext(?array $user): ?array
    {
        if ($user === null) {
            return null;
        }

        if ($this->userHasPermission($user, 'requests.approve')) {
            $user['role'] = 'admin';
        }

        return $user;
    }

    private function canApiUserViewRequest(array $user, array $request): bool
    {
        return $this->userHasPermission($user, 'requests.approve')
            || (int) ($request['requested_by_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    }

    private function mobileApiEnabled(): bool
    {
        return (DataRepository::systemSettings()['mobile_api_enabled'] ?? '1') === '1';
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function jsonError(string $message, int $status): void
    {
        $this->json(['error' => $message], $status);
    }
}
