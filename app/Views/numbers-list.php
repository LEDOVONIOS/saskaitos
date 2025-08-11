<section class="bg-white rounded-2xl shadow-md p-6">
  <h1 class="text-2xl font-semibold mb-4">Numeriai</h1>
  <div class="overflow-x-auto">
    <table class="min-w-full text-left text-sm">
      <thead>
        <tr class="text-gray-600">
          <th class="py-2">Numeris</th>
          <th class="py-2">Peržiūros</th>
          <th class="py-2">Paskutinį kartą tikrintas</th>
          <th class="py-2">Komentarai</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($numbers as $n): ?>
        <tr class="border-t">
          <td class="py-2"><a class="text-blue-600" href="/<?php echo esc($n['e164']); ?>">+<?php echo esc($n['e164']); ?></a> <span class="text-gray-500">(<?php echo esc($n['local06']); ?>)</span></td>
          <td class="py-2"><?php echo (int)$n['views']; ?></td>
          <td class="py-2"><?php echo esc(format_datetime($n['last_checked'])); ?></td>
          <td class="py-2"><?php echo (int)$n['comments_count']; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="mt-4 flex items-center gap-2">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <a class="px-3 py-1 rounded-md border <?php echo $i==$page?'bg-blue-600 text-white border-blue-600':'bg-white'; ?>" href="/numeriai?page=<?php echo $i; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</section>