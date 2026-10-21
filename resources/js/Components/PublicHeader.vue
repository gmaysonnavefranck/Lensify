<script setup lang="ts">
  import { Link } from '@inertiajs/vue3';

  defineProps<{ links?: boolean }>();
</script>

<template>
  <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/80 backdrop-blur">
    <div class="mx-auto flex h-[72px] max-w-6xl items-center justify-between px-6">
      <Link href="/" class="font-playfair text-3xl italic text-primary">Lensify</Link>
      <nav v-if="links" class="hidden gap-8 text-sm font-medium text-textSecondary md:flex">
        <a href="#features" class="hover:text-primary">Features</a>
        <a href="#how-it-works" class="hover:text-primary">How it works</a>
      </nav>
      <div class="flex items-center gap-3">
        <Link
          v-if="$page.props.auth.user"
          href="/texts"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-textLight hover:bg-primaryHover"
        >
          Open app
        </Link>
        <template v-else>
          <Link href="/login" class="rounded-md px-4 py-2 text-sm font-medium text-textSecondary hover:bg-gray-100">
            Log in
          </Link>
          <Link
            href="/register"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-textLight hover:bg-primaryHover"
          >
            Get started
          </Link>
        </template>
      </div>
    </div>
  </header>
</template>
