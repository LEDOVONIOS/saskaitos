<section class="bg-white rounded-2xl shadow-md p-6">
  <h1 class="text-2xl font-semibold mb-4">Paieška</h1>
  <form action="/paieska" method="get" class="flex items-center max-w-xl mb-4">
    <input type="text" name="q" value="<?php echo esc($_GET['q'] ?? ''); ?>" class="flex-1 border rounded-l-xl px-4 py-3" placeholder="Pvz. +37061234567 arba 061234567">
    <button class="bg-blue-600 text-white px-5 py-3 rounded-r-xl">Paieška</button>
  </form>
  <?php if (!empty($error)): ?>
    <div class="p-3 rounded-md bg-yellow-50 text-yellow-800"><?php echo esc($error); ?></div>
  <?php endif; ?>
</section>