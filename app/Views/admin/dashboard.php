<section class="bg-white rounded-2xl shadow-md p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Skydelis</h1>
    <a href="/admin/logout" class="text-red-600">Atsijungti</a>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
    <div class="border rounded-2xl p-4">
      <div class="text-gray-600">Laukiantys komentarai</div>
      <div class="text-2xl font-bold"><?php echo (int)$pending; ?></div>
      <a class="text-blue-600 text-sm" href="/admin/comments">Peržiūrėti</a>
    </div>
    <div class="border rounded-2xl p-4 md:col-span-2">
      <div class="text-gray-600 mb-2">Populiariausi šiandien</div>
      <?php if (!$topToday): ?><div class="text-gray-500">Nėra duomenų.</div><?php else: ?>
      <ul class="space-y-1">
        <?php foreach ($topToday as $n): ?>
          <li><a class="text-blue-600" href="/<?php echo esc($n['e164']); ?>">+<?php echo esc($n['e164']); ?></a> (<?php echo esc($n['local06']); ?>) – peržiūros: <?php echo (int)$n['views']; ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="border rounded-2xl p-4 mt-6">
    <div class="flex items-center justify-between mb-2">
      <div class="text-gray-600">Ištrinti numerio duomenis</div>
    </div>
    <form action="/admin/delete-number" method="post" class="flex items-center gap-2">
      <?php echo csrf_field(); ?>
      <input type="text" name="e164" placeholder="Įveskite 3706…" class="border rounded-xl px-3 py-2">
      <button class="bg-red-600 text-white px-4 py-2 rounded-xl">Ištrinti</button>
    </form>
  </div>

  <div class="border rounded-2xl p-4 mt-6">
    <div class="text-gray-600 mb-2">Paskutinės 20 užklausų</div>
    <?php if (!$contacts): ?><div class="text-gray-500">Nėra duomenų.</div><?php else: ?>
    <ul class="space-y-2">
      <?php foreach ($contacts as $c): ?>
        <li class="border rounded-xl p-3">
          <div class="text-sm text-gray-500 mb-1"><?php echo esc(format_datetime($c['created_at'])); ?></div>
          <div class="text-sm">Vardas: <?php echo esc($c['name']); ?>, El. paštas: <?php echo esc($c['email']); ?>, Numeris: <?php echo esc($c['number']); ?></div>
          <div class="mt-1 text-gray-800"><?php echo nl2br(esc($c['message'])); ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>