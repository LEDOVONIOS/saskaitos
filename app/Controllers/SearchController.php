<?php
class SearchController {
    public function search(): void {
        if (rate_limited('search', 10, 60)) {
            http_response_code(429);
            echo 'Per daug užklausų. Bandykite vėliau.';
            return;
        }
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            render('search', ['error' => null]);
            return;
        }
        [$ok, $e164] = normalize_lt_mobile($q);
        if ($ok) {
            redirect(canonical_number_path($e164), 301);
        }
        render('search', ['error' => 'Neteisingas numerio formatas.']);
    }
}