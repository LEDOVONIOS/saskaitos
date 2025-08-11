<?php
class ContactController {
    public function form(): void {
        render('contact', [
            'flash' => $_SESSION['flash'] ?? null,
        ], [
            'title' => 'Numerio pašalinimas - ' . config('site.name'),
            'description' => 'Pateikite prašymą dėl numerio pašalinimo arba kitą žinutę.',
        ]);
        unset($_SESSION['flash']);
    }

    public function submit(): void {
        if (!verify_csrf()) {
            http_response_code(400);
            echo 'Neteisinga užklausa.';
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $number = trim((string)($_POST['number'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));
        if ($name !== '' && mb_strlen($name) > 120) $name = mb_substr($name, 0, 120);
        if ($email !== '' && mb_strlen($email) > 191) $email = mb_substr($email, 0, 191);
        if ($number !== '' && mb_strlen($number) > 32) $number = mb_substr($number, 0, 32);
        if ($message === '') {
            $_SESSION['flash'] = ['type' => 'error', 'messages' => ['Pranešimo tekstas privalomas.']];
            redirect('/pasalinimas');
        }
        $id = Contact::create($name ?: null, $email ?: null, $number ?: null, $message);
        // Email notify
        $adminEmail = config('admin_email');
        if ($adminEmail) {
            @mail($adminEmail, 'Nauja žinutė iš kontaktų formos', "Vardas: $name\nEl. paštas: $email\nNumeris: $number\nŽinutė:\n$message");
        }
        $_SESSION['flash'] = ['type' => 'success', 'messages' => ['Žinutė gauta. Susisieksime jei reikės.']];
        redirect('/pasalinimas');
    }
}