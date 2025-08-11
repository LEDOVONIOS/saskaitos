<section class="bg-white rounded-2xl shadow-md p-6 max-w-md mx-auto">
  <h1 class="text-2xl font-semibold mb-4">Administratoriaus prisijungimas</h1>
  <?php if (!empty($flash) && $flash['type']==='error'): ?>
    <div class="mb-4 p-3 rounded-md bg-red-50 text-red-700"><?php foreach ($flash['messages'] as $m) { echo '<div>'.esc($m).'</div>'; } ?></div>
  <?php endif; ?>
  <form action="/admin/login" method="post" class="space-y-3">
    <?php echo csrf_field(); ?>
    <div>
      <label class="block text-sm mb-1">El. paštas</label>
      <input type="email" name="email" required class="w-full border rounded-xl px-3 py-2">
    </div>
    <div>
      <label class="block text-sm mb-1">Slaptažodis</label>
      <input type="password" name="password" required class="w-full border rounded-xl px-3 py-2">
    </div>
    <button class="bg-blue-600 text-white px-5 py-3 rounded-xl w-full">Prisijungti</button>
  </form>
</section>