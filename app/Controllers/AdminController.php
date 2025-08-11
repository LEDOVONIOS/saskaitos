<?php
class AdminController {
    public function loginForm(): void {
        if (is_admin()) redirect('/admin');
        render('admin/login', [
            'flash' => $_SESSION['flash'] ?? null,
        ], [
            'title' => 'Admin prisijungimas',
            'robots' => 'noindex,nofollow',
        ]);
        unset($_SESSION['flash']);
    }

    public function loginSubmit(): void {
        if (!verify_csrf()) { http_response_code(400); echo 'Bad request'; return; }
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $admin = Admin::findByEmail($email);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $_SESSION['flash'] = ['type' => 'error', 'messages' => ['Neteisingi duomenys.']];
            redirect('/admin/login');
        }
        $_SESSION['admin_id'] = (int)$admin['id'];
        redirect('/admin');
    }

    public function logout(): void {
        unset($_SESSION['admin_id']);
        redirect('/admin/login');
    }

    public function dashboard(): void {
        require_admin();
        $pending = Comment::countByStatus('pending');
        $contacts = Contact::recent(20);
        $topToday = Number::topSearchedToday(10);
        render('admin/dashboard', [
            'pending' => $pending,
            'contacts' => $contacts,
            'topToday' => $topToday,
        ], [
            'title' => 'Administravimas - Skydelis',
            'robots' => 'noindex,nofollow',
        ]);
    }

    public function commentsQueue(): void {
        require_admin();
        $pending = Comment::listByStatus('pending', 50);
        render('admin/comments', [ 'pending' => $pending ], [ 'title' => 'Laukiantys komentarai', 'robots' => 'noindex,nofollow' ]);
    }

    public function commentsAction(): void {
        require_admin();
        if (!verify_csrf()) { http_response_code(400); echo 'Bad request'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($id <= 0) { redirect('/admin/comments'); }
        if ($action === 'approve') Comment::updateStatus($id, 'approved');
        elseif ($action === 'reject') Comment::updateStatus($id, 'rejected');
        elseif ($action === 'delete') Comment::delete($id);
        redirect('/admin/comments');
    }

    public function contactsList(): void {
        require_admin();
        $contacts = Contact::recent(200);
        render('admin/contacts', [ 'contacts' => $contacts ], [ 'title' => 'Kontaktai', 'robots' => 'noindex,nofollow' ]);
    }

    public function deleteNumberData(): void {
        require_admin();
        if (!verify_csrf()) { http_response_code(400); echo 'Bad request'; return; }
        $e164 = trim((string)($_POST['e164'] ?? ''));
        if ($e164 !== '' && preg_match('/^\d{11}$/', $e164)) {
            Number::deleteByE164($e164);
        }
        redirect('/admin');
    }
}