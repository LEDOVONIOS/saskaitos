<section class="bg-white rounded-2xl shadow-md p-6 mb-6">
  <h1 class="text-2xl font-semibold mb-3">Kieno numeris?</h1>
  <p class="text-gray-600 mb-4">Patikrinkite nepažįstamus Lietuvos mobiliuosius numerius. Įveskite +3706… arba 06…</p>
  <form action="/paieska" method="get" class="flex items-center max-w-xl">
    <input type="text" name="q" class="flex-1 border rounded-l-xl px-4 py-3" placeholder="Pvz. +37061234567 arba 061234567">
    <button class="bg-blue-600 text-white px-5 py-3 rounded-r-xl">Paieška</button>
  </form>
</section>

<section class="bg-white rounded-2xl shadow-md p-6">
  <h2 class="text-xl font-semibold mb-4">Naujausi komentarai</h2>
  <?php if (!$recent): ?>
    <p class="text-gray-500">Kol kas komentarų nėra.</p>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($recent as $c): ?>
        <div class="border rounded-xl p-4">
          <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
            <a class="text-blue-600" href="/<?php echo esc($c['e164']); ?>">+<?php echo esc($c['e164']); ?> (<?php echo esc($c['local06']); ?>)</a>
            <span><?php echo esc(format_datetime($c['created_at'])); ?></span>
          </div>
          <div class="prose max-w-none text-gray-800"><?php echo nl2br(esc($c['body'])); ?></div>
          <?php if (!empty($c['author'])): ?><div class="text-xs text-gray-500 mt-1">Autorius: <?php echo esc($c['author']); ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>