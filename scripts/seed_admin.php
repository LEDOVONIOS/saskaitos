<?php
require __DIR__ . '/../app/bootstrap.php';

$email = $argv[1] ?? 'you@example.com';
$password = $argv[2] ?? 'password';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db()->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash)');
$stmt->execute([$email, $hash]);

echo "Seeded admin: $email\n";