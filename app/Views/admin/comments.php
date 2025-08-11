<section class="bg-white rounded-2xl shadow-md p-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">Laukiantys komentarai</h1>
    <a href="/admin" class="text-blue-600">Atgal</a>
  </div>
  <?php if (!$pending): ?>
    <div class="mt-4 text-gray-500">Nėra laukiančių komentarų.</div>
  <?php else: ?>
    <div class="mt-4 space-y-4">
      <?php foreach ($pending as $c): ?>
        <div class="border rounded-xl p-4">
          <div class="text-sm text-gray-500 mb-1">
            Numeris: <a class="text-blue-600" href="/<?php echo esc($c['e164']); ?>">+<?php echo esc($c['e164']); ?></a> (<?php echo esc($c['local06']); ?>) • <?php echo esc(format_datetime($c['created_at'])); ?>
          </div>
          <div class="mb-2 text-gray-800"><?php echo nl2br(esc($c['body'])); ?></div>
          <form action="/admin/comments" method="post" class="flex items-center gap-2">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
            <button name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded-xl">Patvirtinti</button>
            <button name="action" value="reject" class="bg-yellow-600 text-white px-3 py-1 rounded-xl">Atmesti</button>
            <button name="action" value="delete" class="bg-red-600 text-white px-3 py-1 rounded-xl">Ištrinti</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>