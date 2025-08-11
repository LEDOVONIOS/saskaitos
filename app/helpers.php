<?php

function config(string $key, $default = null) {
    global $APP_CONFIG;
    $parts = explode('.', $key);
    $value = $APP_CONFIG;
    foreach ($parts as $part) {
        if (!isset($value[$part])) return $default;
        $value = $value[$part];
    }
    return $value;
}

function db(): PDO {
    global $APP_PDO;
    return $APP_PDO;
}

function app_log(string $message): void {
    $logDir = __DIR__ . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $file = $logDir . '/app.log';
    $entry = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($file, $entry, FILE_APPEND);
}

function esc(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_token" value="' . esc(csrf_token()) . '">';
}

function verify_csrf(): bool {
    return isset($_POST['_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token']);
}

function get_client_ip_bin(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $bin = @inet_pton($ip);
    return $bin !== false ? $bin : inet_pton('0.0.0.0');
}

function rate_limited(string $action, int $limit, int $windowSeconds = 60): bool {
    try {
        $pdo = db();
        $ip = get_client_ip_bin();
        $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM throttles WHERE ip = ? AND action = ? AND occurred_at >= (NOW() - INTERVAL ? SECOND)');
        $stmt->execute([$ip, $action, $windowSeconds]);
        $count = (int)$stmt->fetchColumn();
        if ($count >= $limit) {
            return true;
        }
        $ins = $pdo->prepare('INSERT INTO throttles (ip, action) VALUES (?, ?)');
        $ins->execute([$ip, $action]);
        return false;
    } catch (Throwable $e) {
        app_log('rate_limited error: ' . $e->getMessage());
        return false; // fail-open
    }
}

function redirect(string $url, int $status = 302): void {
    header('Location: ' . $url, true, $status);
    exit;
}

function base_url(string $path = ''): string {
    $base = rtrim(config('site.base_url'), '/');
    return $base . '/' . ltrim($path, '/');
}

function render(string $viewPath, array $data = [], array $meta = []): void {
    extract($data, EXTR_OVERWRITE);
    $metaTitle = $meta['title'] ?? config('site.name');
    $metaDescription = $meta['description'] ?? '';
    $metaRobots = $meta['robots'] ?? 'index,follow';
    include __DIR__ . '/Views/layouts/base.php';
}

// Number normalization helpers
function normalize_lt_mobile(string $input): array {
    // Returns [is_valid(bool), e164(string|null), local06(string|null)]
    $raw = preg_replace('/[\s\-()]/', '', $input);
    if ($raw === null) $raw = '';

    if ($raw === '') return [false, null, null];

    // Accept +3706xxxxxxx or 06xxxxxxx or 3706xxxxxxx
    if (preg_match('/^\+?3706\d{7}$/', $raw)) {
        $digits = ltrim($raw, '+');
        $e164 = $digits; // already without plus
        $local06 = '0' . substr($digits, 3); // 0 + local part after 370
        return [true, $e164, $local06];
    }
    if (preg_match('/^06\d{7}$/', $raw)) {
        $local06 = $raw;
        $e164 = '370' . substr($raw, 1); // replace leading 0 with 370
        return [true, $e164, $local06];
    }

    return [false, null, null];
}

function canonical_number_path(string $e164): string {
    return '/' . $e164 . '/';
}

function is_canonical_path_number(string $path): bool {
    return (bool)preg_match('#^/(\d{11})/?$#', $path);
}

function format_datetime(?string $dt): string {
    if (!$dt) return '-';
    return date('Y-m-d H:i', strtotime($dt));
}

function require_admin(): void {
    if (empty($_SESSION['admin_id'])) {
        redirect('/admin/login');
    }
}

function is_admin(): bool {
    return !empty($_SESSION['admin_id']);
}