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
    <header class="flex select-none items-center justify-between border-b-2 border-gray-200 px-3 py-6">
      <div class="flex items-center">
        <span class="ml-5 font-playfair text-3xl italic text-primary">Lensify</span>
      </div>
      <div class="flex">
        <nav class="mr-12 flex gap-6 self-center">
          <NavLink text="Authors" href="/authors" icon="author" :selected="isPageSelected('Authors/Authors')" />
          <NavLink text="Texts" href="/texts" icon="texts" :selected="isPageSelected('Texts/Texts')" />
          <NavLink text="Books" href="/books" icon="books" :selected="isPageSelected('Books/Books')" />
          <NavLink text="Quotes" href="/quotes" icon="quotes" :selected="isPageSelected('Quotes/Quotes')" />
        </nav>
        <img src="../Assets/blankProfile.png" alt="user photo" class="h-10 w-10 rounded-full bg-slate-700" />
      </div>
    </header>
    <main class="flex flex-grow justify-center p-10">
      <div class="flex max-w-5xl flex-grow">
        <slot />
      </div>
    </main>
  </div>
</template>
