<?php $q = esc($_GET['q'] ?? ''); ?>
<header class="bg-white shadow-sm">
  <div class="container mx-auto max-w-5xl px-4 py-4 flex items-center justify-between">
    <a href="/" class="flex items-center gap-2 text-xl font-semibold">
      <img src="/assets/logo.svg" alt="" class="w-7 h-7">
      <span><?php echo esc(config('site.name')); ?></span>
    </a>
    <nav class="hidden md:flex items-center gap-6">
      <a href="/numeriai" class="hover:text-blue-600">Numeriai</a>
      <a href="/pasalinimas" class="hover:text-blue-600">Numerio pašalinimas</a>
      <form action="/paieska" method="get" class="flex items-center">
        <input type="text" name="q" value="<?php echo $q; ?>" placeholder="Paieška" class="border rounded-l-md px-3 py-2 text-sm focus:outline-none">
        <button class="bg-blue-600 text-white px-3 py-2 rounded-r-md text-sm">Paieška</button>
      </form>
    </nav>
  </div>
</header>
<div class="md:hidden bg-white border-t border-b">
  <div class="container mx-auto max-w-5xl px-4 py-2 flex items-center gap-3">
    <a href="/numeriai" class="text-sm">Numeriai</a>
    <a href="/pasalinimas" class="text-sm">Numerio pašalinimas</a>
    <form action="/paieska" method="get" class="ml-auto flex items-center">
      <input type="text" name="q" value="<?php echo $q; ?>" placeholder="Paieška" class="border rounded-l-md px-2 py-1 text-sm focus:outline-none w-36">
      <button class="bg-blue-600 text-white px-2 py-1 rounded-r-md text-sm">Paieška</button>
    </form>
  </div>
</div>