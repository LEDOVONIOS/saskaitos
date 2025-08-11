<section class="bg-white rounded-2xl shadow-md p-6">
  <h1 class="text-2xl font-semibold mb-4">Numerio pašalinimas</h1>
  <?php if (!empty($flash) && $flash['type']==='success'): ?>
    <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700"><?php foreach ($flash['messages'] as $m) { echo '<div>'.esc($m).'</div>'; } ?></div>
  <?php elseif (!empty($flash) && $flash['type']==='error'): ?>
    <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700"><?php foreach ($flash['messages'] as $m) { echo '<div>'.esc($m).'</div>'; } ?></div>
  <?php endif; ?>
  <form action="/pasalinimas" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <?php echo csrf_field(); ?>
    <div>
      <label class="block text-sm mb-1">Vardas</label>
      <input type="text" name="name" maxlength="120" class="w-full border rounded-xl px-3 py-2">
    </div>
    <div>
      <label class="block text-sm mb-1">El. paštas</label>
      <input type="email" name="email" maxlength="191" class="w-full border rounded-xl px-3 py-2">
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm mb-1">Numeris</label>
      <input type="text" name="number" maxlength="32" class="w-full border rounded-xl px-3 py-2">
    </div>
    <div class="md:col-span-2">
      <label class="block text-sm mb-1">Žinutė</label>
      <textarea name="message" required class="w-full border rounded-xl px-3 py-2 h-32"></textarea>
    </div>
    <div class="md:col-span-2">
      <button class="bg-blue-600 text-white px-5 py-3 rounded-xl">Siųsti</button>
    </div>
  </form>
</section>