<?php
class HomeController {
    public function index(): void {
        // recent comments (approved)
        $stmt = db()->query("SELECT c.body, c.author, c.created_at, n.e164, n.local06 FROM comments c JOIN numbers n ON n.id=c.number_id WHERE c.status='approved' ORDER BY c.created_at DESC LIMIT 10");
        $recent = $stmt->fetchAll();
        render('home', [
            'recent' => $recent,
        ], [
            'title' => config('site.name'),
            'description' => 'Patikrinkite nepažįstamus Lietuvos mobiliuosius numerius. Paieška, komentarai ir skundų forma.',
        ]);
    }
}