<?php $success = isset($flash['type']) && $flash['type']==='success'; $errors = isset($flash['type']) && $flash['type']==='error' ? ($flash['messages']??[]) : []; ?>
<section class="bg-white rounded-2xl shadow-md p-6 mb-6">
  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold">+<?php echo esc($number['e164']); ?> <span class="text-gray-500">(<?php echo esc($number['local06']); ?>)</span></h1>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div class="rounded-2xl border p-4">
      <div class="text-gray-700">Lankytojai šio numerio ieškojo: <strong><?php echo (int)$number['views']; ?></strong> kartą(-us)</div>
    </div>
    <div class="rounded-2xl border p-4">
      <div class="text-gray-700">Paskutinį kartą tikrintas: <strong><?php echo esc(format_datetime($number['last_checked'])); ?></strong></div>
    </div>
  </div>
</section>

<section class="bg-white rounded-2xl shadow-md p-6 mb-6">
  <h2 class="text-xl font-semibold mb-4">Komentarai apie šį numerį</h2>
  <?php if ($success): ?>
    <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">Komentaras gautas ir bus paskelbtas po patvirtinimo.</div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700"><?php foreach ($errors as $e) { echo '<div>'.esc($e).'</div>'; } ?></div>
  <?php endif; ?>
  <?php if (!$comments): ?>
    <p class="text-gray-500">Apie šį numerį komentarų kol kas nėra. Skambino? Parašykite čia!</p>
  <?php else: ?>
    <div class="space-y-4 mb-6">
      <?php foreach ($comments as $c): ?>
        <div class="border rounded-xl p-4">
          <div class="text-sm text-gray-500 mb-1 flex items-center justify-between">
            <span><?php echo esc(format_datetime($c['created_at'])); ?></span>
            <?php if (!empty($c['author'])): ?><span>Autorius: <?php echo esc($c['author']); ?></span><?php endif; ?>
          </div>
          <div class="text-gray-800"><?php echo nl2br(esc($c['body'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form action="/<?php echo esc($number['e164']); ?>" method="post" class="space-y-3">
    <?php echo csrf_field(); ?>
    <div>
      <label class="block text-sm mb-1">Jūsų vardas</label>
      <input type="text" name="author" maxlength="80" class="w-full border rounded-xl px-3 py-2" placeholder="Nebūtina">
    </div>
    <div>
      <label class="block text-sm mb-1">Komentaras</label>
      <textarea name="body" required minlength="5" maxlength="800" class="w-full border rounded-xl px-3 py-2 h-28"></textarea>
    </div>
    <input type="hidden" name="captcha_token" value="<?php echo esc(config('security.simple_token')); ?>">
    <button class="bg-blue-600 text-white px-5 py-3 rounded-xl">Pateikti</button>
  </form>
</section>