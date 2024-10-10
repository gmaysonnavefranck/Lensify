<script setup lang="ts">
  import { Head, usePage } from '@inertiajs/vue3';
  import NavLink from '@/Components/NavLink.vue';

  const page = usePage();

  function isPageSelected(route: string) {
    return route === page.component;
  }
</script>

<template>
  <Head title="Home" />
  <div class="flex min-h-screen flex-col">
    <header class="p-4">
      <h1 class="text-lg">Lensify</h1>
    </header>

    <div class="flex flex-grow">
      <aside class="p-4">
        <nav class="w-44">
          <ul>
            <NavLink text="Authors" href="/authors" icon="author" :selected="isPageSelected('Authors/Authors')" />
            <NavLink text="Texts" href="/texts" icon="texts" :selected="isPageSelected('Texts/Texts')" />
            <NavLink text="Books" href="/books" icon="books" :selected="isPageSelected('Books/Books')" />
            <NavLink text="Quotes" href="/quotes" icon="quotes" :selected="isPageSelected('Quotes/Quotes')" />
          </ul>
        </nav>
      </aside>

      <main class="flex-grow bg-[#F0EFEB] p-6">
        <div class="h-full w-full rounded-lg bg-white p-4">
          <slot />
        </div>
      </main>
    </div>

    <footer class="bg-gray-800 p-4 text-white">
      <p>Footer content</p>
    </footer>
  </div>
</template>
