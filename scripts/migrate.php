<?php
// Run DB migrations from database.sql
require __DIR__ . '/../app/bootstrap.php';
$sql = file_get_contents(__DIR__ . '/../database.sql');
try {
    db()->exec($sql);
    echo "Migrations executed successfully.\n";
} catch (Throwable $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}