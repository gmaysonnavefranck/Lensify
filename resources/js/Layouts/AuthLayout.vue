<script setup lang="ts">
  import { Link, usePage } from '@inertiajs/vue3';
  import Dropdown from '@/Components/Dropdown.vue';
  import DropdownLink from '@/Components/DropdownLink.vue';
  import NavLink from '@/Components/NavLink.vue';
  import Icon from '@/Components/Icon.vue';
  import { onMounted, ref } from 'vue';

  const page = usePage();

  // The theme class is set before paint in app.blade.php; this button flips it and remembers the choice.
  // Re-applied on mount too: pages before the app strip it, and logging in doesn't reload the page.
  const isDark = ref(false);
  onMounted(() => {
    let theme: string | null = null;
    try {
      theme = localStorage.getItem('lensify.theme');
    } catch {
      // Private mode: fall back to the system setting
    }
    isDark.value = theme === 'dark' || (!theme && matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', isDark.value);
  });
  function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    try {
      localStorage.setItem('lensify.theme', isDark.value ? 'dark' : 'light');
    } catch {
      // Private mode: the choice just isn't remembered
    }
  }

  function isPageSelected(route: string) {
    return route === page.component;
  }
</script>

<template>
  <Head title="Home" />
  <div class="flex min-h-screen flex-col">
    <header class="flex h-[72px] items-center justify-between border-b-2 border-gray-200 px-3">
      <div class="flex items-center">
        <Link href="/dashboard" class="ml-5 font-playfair text-3xl italic text-primary">Lensify</Link>
      </div>
      <div class="flex">
        <nav class="mr-12 flex gap-6 self-center">
          <NavLink text="Home" href="/dashboard" icon="home" :selected="isPageSelected('Dashboard')" />
          <NavLink text="Texts" href="/texts" icon="texts" :selected="isPageSelected('Texts/Texts')" />
          <NavLink
            text="Books"
            href="/books"
            icon="books"
            :selected="isPageSelected('Books/Books') || isPageSelected('Books/Book')"
          />
          <NavLink text="Authors" href="/authors" icon="author" :selected="isPageSelected('Authors/Authors')" />
          <NavLink text="Quotes" href="/quotes" icon="quotes" :selected="isPageSelected('Quotes/Quotes')" />
          <NavLink
            text="Vocabulary"
            href="/vocabulary"
            icon="bookA"
            :selected="isPageSelected('Vocabulary/Vocabulary')"
          />
        </nav>
        <button
          class="mr-4 self-center rounded-full p-2 text-textSecondary transition-colors hover:bg-gray-100 hover:text-primary"
          :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="toggleTheme"
        >
          <Icon :icon="isDark ? 'sun' : 'moon'" class="h-5 w-5" />
        </button>
        <Dropdown align="right" width="48">
          <template #trigger>
            <button class="rounded-full focus:outline-none focus:ring-2 focus:ring-primary/40">
              <img src="../Assets/blankProfile.png" alt="Account menu" class="h-10 w-10 rounded-full bg-slate-700" />
            </button>
          </template>
          <template #content>
            <p class="truncate border-b border-gray-100 px-4 py-2 text-sm text-textSecondary">
              {{ page.props.auth.user.name }}
            </p>
            <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
            <DropdownLink :href="route('logout')" method="post" as="button">Log out</DropdownLink>
          </template>
        </Dropdown>
      </div>
    </header>

    <main class="flex max-h-[calc(100vh-72px)] flex-1 flex-grow justify-center">
      <div class="w-full">
        <slot />
      </div>
    </main>
  </div>
</template>
