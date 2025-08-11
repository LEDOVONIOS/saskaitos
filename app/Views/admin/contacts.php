<section class="bg-white rounded-2xl shadow-md p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Kontaktai</h1>
    <a href="/admin" class="text-blue-600">Atgal</a>
  </div>
  <?php if (!$contacts): ?>
    <div class="mt-4 text-gray-500">Nėra duomenų.</div>
  <?php else: ?>
    <div class="mt-4 space-y-4">
      <?php foreach ($contacts as $c): ?>
        <div class="border rounded-xl p-4">
          <div class="text-sm text-gray-500 mb-1"><?php echo esc(format_datetime($c['created_at'])); ?></div>
          <div class="text-sm">Vardas: <?php echo esc($c['name']); ?>, El. paštas: <?php echo esc($c['email']); ?>, Numeris: <?php echo esc($c['number']); ?></div>
          <div class="mt-1 text-gray-800"><?php echo nl2br(esc($c['message'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>