<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { Author } from '@/types/index';
  import { Link, router } from '@inertiajs/vue3';

  const props = defineProps<{ author: Author }>();

  defineOptions({ layout: AuthLayout });

  const stats = [
    { label: 'Born', value: props.author.born_year },
    { label: 'Died', value: props.author.death_year },
    { label: 'Nationality', value: props.author.nationality },
    { label: 'Texts', value: String(props.author.texts?.length ?? 0) },
  ];

  function deleteAuthor() {
    if (confirm(`Delete ${props.author.name}? Their texts will be kept, just unlinked.`)) {
      router.delete(`/authors/${props.author.id}`);
    }
  }
</script>

<template>
  <Head :title="author.name" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-8 py-8">
      <div class="flex items-center justify-between">
        <Link href="/authors" class="flex items-center gap-2 text-textSecondary transition-colors hover:text-primary">
          <Icon icon="chevronLeft" class="h-5 w-5" />
          <span class="text-sm font-medium">Back to Authors</span>
        </Link>
        <Button type="danger" text="Delete author" @click="deleteAuthor" />
      </div>

      <header class="card-background relative overflow-hidden rounded-2xl px-8 py-10 text-center shadow-md md:py-12">
        <div
          class="absolute inset-0 opacity-15"
          :style="{
            backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
            backgroundSize: '24px 24px',
          }"
        />
        <div class="relative flex flex-col items-center gap-4">
          <h1
            class="font-playfair text-4xl font-semibold capitalize leading-tight text-textLight md:text-5xl"
            :style="{ textShadow: '0 2px 8px rgba(0,0,0,0.25)' }"
          >
            {{ author.name }}
          </h1>
          <div v-if="author.literary_periods?.length" class="flex flex-wrap justify-center gap-3">
            <span
              v-for="period in author.literary_periods"
              :key="period.id"
              class="rounded-full bg-white/20 px-4 py-1 text-sm tracking-wide text-textLight backdrop-blur-sm"
            >
              {{ period.name }}
            </span>
          </div>
        </div>
      </header>

      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div v-for="stat in stats" :key="stat.label" class="rounded-lg border border-gray-200 bg-surface px-5 py-4">
          <p class="text-xs font-semibold uppercase tracking-widest text-secondary">{{ stat.label }}</p>
          <p class="mt-1 truncate font-playfair text-xl font-semibold text-textPrimary">{{ stat.value || '—' }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
          <section class="card">
            <h2 class="card-title">
              <Icon icon="info" class="h-5 w-5 text-primary" />
              Key Facts
            </h2>
            <ul v-if="author.key_facts?.length" class="space-y-3 text-sm">
              <li v-for="fact in author.key_facts" :key="fact" class="flex gap-4 leading-relaxed text-textSecondary">
                <span class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                {{ fact }}
              </li>
            </ul>
            <p v-else class="empty">No key facts yet.</p>
          </section>

          <section class="card">
            <h2 class="card-title">
              <Icon icon="texts" class="h-5 w-5 text-primary" />
              Texts
            </h2>
            <div v-if="author.texts?.length" class="-mx-3 flex flex-col">
              <Link
                v-for="text in author.texts"
                :key="text.id"
                :href="`/texts/${text.id}`"
                class="flex items-center justify-between rounded-lg px-3 py-3 transition-colors hover:bg-surface"
              >
                <span class="font-medium text-textPrimary">{{ text.title }}</span>
                <span class="text-sm text-secondary">{{ [text.type, text.year].filter(Boolean).join(' · ') }}</span>
              </Link>
            </div>
            <p v-else class="empty">No texts linked to this author yet.</p>
          </section>
        </div>

        <div class="flex flex-col gap-6">
          <section class="card">
            <h2 class="card-title">
              <Icon icon="books" class="h-5 w-5 text-primary" />
              Famous Works
            </h2>
            <ol v-if="author.famous_works?.length" class="space-y-3">
              <li v-for="(work, i) in author.famous_works" :key="work" class="flex items-baseline gap-3">
                <span class="font-playfair text-lg text-secondary">{{ i + 1 }}.</span>
                <span class="font-newsreader text-lg italic text-textPrimary">{{ work }}</span>
              </li>
            </ol>
            <p v-else class="empty">No works listed yet.</p>
          </section>

          <section class="card">
            <h2 class="card-title">Genres</h2>
            <div v-if="author.genres?.length" class="flex flex-wrap gap-2">
              <span v-for="genre in author.genres" :key="genre.id" class="chip">{{ genre.name }}</span>
            </div>
            <p v-else class="empty">None yet.</p>
          </section>

          <section class="card">
            <h2 class="card-title">Themes</h2>
            <div v-if="author.themes?.length" class="flex flex-wrap gap-2">
              <span v-for="theme in author.themes" :key="theme.id" class="chip">{{ theme.name }}</span>
            </div>
            <p v-else class="empty">None yet.</p>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="postcss" scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
  .card {
    @apply rounded-lg border border-gray-200 bg-white p-6;
  }
  .card-title {
    @apply mb-4 flex items-center gap-2 text-base font-semibold text-textPrimary;
  }
  .chip {
    @apply rounded-full bg-surface px-3 py-1 text-xs text-textSecondary ring-1 ring-gray-200;
  }
  .empty {
    @apply text-sm italic text-secondary;
  }
</style>
