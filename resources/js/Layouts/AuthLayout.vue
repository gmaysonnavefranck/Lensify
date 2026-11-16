<script setup lang="ts">
  import { Link, usePage } from '@inertiajs/vue3';
  import Dropdown from '@/Components/Dropdown.vue';
  import DropdownLink from '@/Components/DropdownLink.vue';
  import NavLink from '@/Components/NavLink.vue';

  const page = usePage();

  function isPageSelected(route: string) {
    return route === page.component;
  }
</script>

<template>
  <Head title="Home" />
  <div class="flex min-h-screen flex-col">
    <header class="flex h-[72px] items-center justify-between border-b-2 border-gray-200 px-3">
      <div class="flex items-center">
        <Link href="/texts" class="ml-5 font-playfair text-3xl italic text-primary">Lensify</Link>
      </div>
      <div class="flex">
        <nav class="mr-12 flex gap-6 self-center">
          <NavLink text="Texts" href="/texts" icon="texts" :selected="isPageSelected('Texts/Texts')" />
          <NavLink text="Authors" href="/authors" icon="author" :selected="isPageSelected('Authors/Authors')" />
          <NavLink text="Quotes" href="/quotes" icon="quotes" :selected="isPageSelected('Quotes/Quotes')" />
          <NavLink
            text="Vocabulary"
            href="/vocabulary"
            icon="bookA"
            :selected="isPageSelected('Vocabulary/Vocabulary')"
          />
        </nav>
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
