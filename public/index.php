<?php
require __DIR__ . '/../app/bootstrap.php';
require __DIR__ . '/../app/Router.php';

// Auto-load controllers and models
require __DIR__ . '/../app/Controllers/HomeController.php';
require __DIR__ . '/../app/Controllers/NumberController.php';
require __DIR__ . '/../app/Controllers/SearchController.php';
require __DIR__ . '/../app/Controllers/AdminController.php';
require __DIR__ . '/../app/Controllers/ContactController.php';
require __DIR__ . '/../app/Controllers/SitemapController.php';
require __DIR__ . '/../app/Models/Number.php';
require __DIR__ . '/../app/Models/Comment.php';
require __DIR__ . '/../app/Models/Admin.php';
require __DIR__ . '/../app/Models/Contact.php';
require __DIR__ . '/../app/Models/Throttle.php';

$router = new Router();

// Pretty number variant normalization: if path looks like +3706xxxxxxx or 06xxxxxxx, redirect to canonical /{e164}/
$router->get('~^/([0-9+()\\\-\s]{9,20})$~', function($m) {
    [$ok, $e164] = normalize_lt_mobile($m[1]);
    if ($ok) {
        redirect(canonical_number_path($e164), 301);
    }
    http_response_code(404);
    echo 'Neteisingas numerio formatas.';
});

// Home
$router->get('/', function() { (new HomeController())->index(); });

// Numbers list
$router->get('/numeriai', function() { (new NumberController())->index(); });

// Number show by canonical e164
$router->get('~^/(\d{11})$~', function($m) { (new NumberController())->show($m[1]); });
$router->post('~^/(\d{11})$~', function($m) { (new NumberController())->storeComment($m[1]); });

// Search
$router->get('/paieska', function() { (new SearchController())->search(); });

// Contact / removal request
$router->get('/pasalinimas', function() { (new ContactController())->form(); });
$router->post('/pasalinimas', function() { (new ContactController())->submit(); });

// Admin
$router->get('/admin', function() { (new AdminController())->dashboard(); });
$router->get('/admin/login', function() { (new AdminController())->loginForm(); });
$router->post('/admin/login', function() { (new AdminController())->loginSubmit(); });
$router->get('/admin/logout', function() { (new AdminController())->logout(); });
$router->get('/admin/comments', function() { (new AdminController())->commentsQueue(); });
$router->post('/admin/comments', function() { (new AdminController())->commentsAction(); });
$router->get('/admin/contacts', function() { (new AdminController())->contactsList(); });
$router->post('/admin/delete-number', function() { (new AdminController())->deleteNumberData(); });

// Sitemap and robots
$router->get('/sitemap.xml', function() { (new SitemapController())->sitemap(); });
$router->get('/robots.txt', function() { (new SitemapController())->robots(); });

// API
$router->get('~^/api/number/(\d{11})$~', function($m) { (new NumberController())->apiShow($m[1]); });

$router->dispatch();