<?php /* @var $metaTitle string @var $metaDescription string @var $metaRobots string */ ?>
<!DOCTYPE html>
<html lang="lt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo esc($metaTitle); ?></title>
  <meta name="description" content="<?php echo esc($metaDescription); ?>">
  <meta name="robots" content="<?php echo esc($metaRobots); ?>">
  <meta property="og:title" content="<?php echo esc($metaTitle); ?>">
  <meta property="og:description" content="<?php echo esc($metaDescription); ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?php echo esc(base_url($_SERVER['REQUEST_URI'] ?? '/')); ?>">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="/assets/logo.svg">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial, "Apple Color Emoji", "Segoe UI Emoji";}</style>
</head>
<body class="bg-gray-50 text-gray-900">
  <?php include __DIR__ . '/../partials/header.php'; ?>
  <main class="container mx-auto max-w-5xl px-4 py-6">
    <?php include __DIR__ . '/../' . $viewPath . '.php'; ?>
  </main>
  <?php include __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>