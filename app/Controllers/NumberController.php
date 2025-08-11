<?php
class NumberController {
    public function index(): void {
        // pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $totalStmt = db()->query("SELECT COUNT(*) FROM numbers WHERE views > 0 OR id IN (SELECT DISTINCT number_id FROM comments)");
        $total = (int)$totalStmt->fetchColumn();

        $stmt = db()->prepare("SELECT n.*, (SELECT COUNT(*) FROM comments c WHERE c.number_id=n.id AND c.status='approved') AS comments_count
            FROM numbers n
            WHERE n.views > 0 OR n.id IN (SELECT DISTINCT number_id FROM comments)
            ORDER BY (n.last_checked IS NULL), n.last_checked DESC, n.updated_at DESC
            LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $pages = (int)ceil($total / $perPage);

        render('numbers-list', [
            'numbers' => $rows,
            'page' => $page,
            'pages' => $pages,
        ], [
            'title' => 'Numeriai - ' . config('site.name'),
            'description' => 'Populiariausi tikrinti numeriai, peržiūrų skaičius ir paskutinis tikrinimas.',
        ]);
    }

    public function show(string $e164): void {
        // find or create number, increment view
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $num = Number::findByE164($e164);
            if (!$num) {
                // derive local06
                $local06 = '0' . substr($e164, 3);
                $num = Number::create($e164, $local06);
            }
            Number::incrementView($num['id']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            app_log('show increment error: ' . $e->getMessage());
        }

        $num = Number::findByE164($e164);

        // Fetch approved comments newest first
        $comments = Comment::listApprovedByNumberId((int)$num['id']);

        // SEO meta
        $title = $e164 . ' kieno numeris - ' . $num['local06'] . ' kas skambino';
        $desc = 'Nežinote, kas skambino numeriu ' . $num['local06'] . '? Sužinokite, kieno tai numeris ' . $e164 . ', gaukite išsamią informaciją apie skambintoją ir apsisaugokite nuo sukčių!';

        render('number-show', [
            'number' => $num,
            'comments' => $comments,
            'flash' => $_SESSION['flash'] ?? null,
        ], [
            'title' => $title,
            'description' => $desc,
        ]);
        unset($_SESSION['flash']);
    }

    public function storeComment(string $e164): void {
        if (rate_limited('comment', 10, 60)) {
            http_response_code(429);
            echo 'Per daug užklausų. Bandykite vėliau.';
            return;
        }
        if (!verify_csrf()) {
            http_response_code(400);
            echo 'Neteisinga užklausa.';
            return;
        }
        // Validate number exists or create lazy
        $num = Number::findByE164($e164);
        if (!$num) {
            $local06 = '0' . substr($e164, 3);
            $num = Number::create($e164, $local06);
        }
        $author = trim((string)($_POST['author'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($author !== '' && mb_strlen($author) > 80) {
            $author = mb_substr($author, 0, 80);
        }
        $errors = [];
        if (mb_strlen($body) < 5 || mb_strlen($body) > 800) {
            $errors[] = 'Komentaras turi būti 5–800 simbolių.';
        }
        // simple token check if recaptcha disabled
        if (!config('security.enable_recaptcha') && !empty(config('security.simple_token'))) {
            $tok = $_POST['captcha_token'] ?? '';
            if ($tok !== config('security.simple_token')) {
                $errors[] = 'Saugumo patikra nepavyko.';
            }
        }
        if ($errors) {
            $_SESSION['flash'] = ['type' => 'error', 'messages' => $errors];
            redirect(canonical_number_path($e164));
        }
        Comment::createPending((int)$num['id'], $author !== '' ? $author : null, $body, get_client_ip_bin(), $_SERVER['HTTP_USER_AGENT'] ?? '');
        $_SESSION['flash'] = ['type' => 'success', 'messages' => ['Komentaras gautas ir bus paskelbtas po patvirtinimo.']];
        redirect(canonical_number_path($e164));
    }

    public function apiShow(string $e164): void {
        header('Content-Type: application/json; charset=utf-8');
        $num = Number::findByE164($e164);
        if (!$num) {
            echo json_encode(['error' => 'Numeris nerastas.']);
            return;
        }
        $count = Comment::countApprovedByNumberId((int)$num['id']);
        echo json_encode([
            'number' => $num['e164'],
            'views' => (int)$num['views'],
            'last_checked' => $num['last_checked'],
            'comments_count' => $count,
        ]);
    }
}