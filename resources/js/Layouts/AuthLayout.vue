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
    <header class="flex select-none items-center justify-between p-4">
      <div class="flex items-center">
        <img src="../Assets/icon.png" alt="Logo" class="h-20 w-20" />
        <span class="ml-5 text-4xl">Lensify</span>
        <input type="text" class="ml-10 w-96 rounded-lg p-2 text-white" placeholder="Search for authors and titles" />
      </div>
      <div>
        <img src="../Assets/blankProfile.png" alt="user photo" class="mr-5 h-12 w-12 rounded-full bg-slate-700" />
      </div>
    </header>

    <div class="flex flex-grow">
      <aside>
        <nav class="mt-10 w-60 px-4">
          <ul>
            <NavLink text="Authors" href="/authors" icon="author" :selected="isPageSelected('Authors/Authors')" />
            <NavLink text="Texts" href="/texts" icon="texts" :selected="isPageSelected('Texts/Texts')" />
            <NavLink text="Books" href="/books" icon="books" :selected="isPageSelected('Books/Books')" />
            <NavLink text="Quotes" href="/quotes" icon="quotes" :selected="isPageSelected('Quotes/Quotes')" />
          </ul>
        </nav>
      </aside>

      <main class="flex-grow rounded-lg rounded-bl-none bg-[#EBEEF2] p-6">
        <div class="h-full w-full rounded-lg bg-white p-4">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>
