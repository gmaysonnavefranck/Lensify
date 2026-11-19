<script setup lang="ts">
  import Icon from '@/Components/Icon.vue';
  import PublicHeader from '@/Components/PublicHeader.vue';
  import { Link } from '@inertiajs/vue3';
  import { onMounted } from 'vue';

  // Always light, like the login pages; logging out lands here without a page reload
  onMounted(() => document.documentElement.classList.remove('dark'));

  const features = [
    {
      icon: 'message',
      title: 'Footnotes that stay',
      text: 'Every note is anchored to the exact passage it explains. Come back in a month and it is still there, right where you left it.',
    },
    {
      icon: 'sparkles',
      title: 'AI analysis on demand',
      text: 'Select a line you don’t understand and get context, references and meaning in seconds. Edit it until it reads like your own.',
    },
    {
      icon: 'author',
      title: 'Your library of authors',
      text: 'Authors, periods, genres and themes are organized for you as you read, so every text connects to the bigger picture.',
    },
    {
      icon: 'globe',
      title: 'Read across languages',
      text: 'Keep translations next to the original and annotate both, perfect for reading literature in the language it was written in.',
    },
  ];

  const steps = [
    { title: 'Paste a text', text: 'A poem, a chapter, an essay. Lensify identifies the title, author and language for you.' },
    { title: 'Select and annotate', text: 'Highlight any passage and write a note, or let the AI draft an analysis for you to refine.' },
    { title: 'Read your edition', text: 'The text and its footnotes side by side, like a critical edition you built yourself.' },
  ];
</script>

<template>
  <Head title="Your own annotated editions" />
  <div class="min-h-screen bg-white text-textPrimary">
    <PublicHeader links />

    <!-- Hero -->
    <section class="relative overflow-hidden">
      <div
        class="absolute inset-0 -z-10 opacity-60"
        :style="{
          backgroundImage: 'radial-gradient(circle at 1px 1px, #d6dce6 1px, transparent 0)',
          backgroundSize: '28px 28px',
        }"
      />
      <div class="mx-auto grid max-w-6xl items-center gap-16 px-6 py-20 lg:grid-cols-2 lg:py-28">
        <div>
          <p class="mb-5 inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-sm font-medium text-primary">
            <Icon icon="sparkles" class="h-4 w-4" />
            AI-assisted close reading
          </p>
          <h1 class="font-playfair text-5xl font-semibold leading-tight md:text-6xl">
            Build your own <span class="italic text-primary">annotated edition</span> of anything you read
          </h1>
          <p class="mt-6 max-w-lg text-lg leading-relaxed text-textSecondary">
            Paste a poem, a chapter or an essay. Select any passage to add footnotes, by hand or with AI, and keep them
            next to the text forever. No more explanations lost in a chat history.
          </p>
          <div class="mt-10 flex flex-wrap items-center gap-4">
            <Link
              href="/register"
              class="rounded-md bg-primary px-6 py-3 font-medium text-textLight shadow-sm hover:bg-primaryHover"
            >
              Start annotating, free
            </Link>
            <a href="#how-it-works" class="px-2 py-3 font-medium text-textSecondary hover:text-primary">
              See how it works →
            </a>
          </div>
        </div>

        <!-- Product demo -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-xl">
          <div class="flex items-center gap-1.5 border-b border-gray-100 px-4 py-3">
            <span class="h-3 w-3 rounded-full bg-gray-200" />
            <span class="h-3 w-3 rounded-full bg-gray-200" />
            <span class="h-3 w-3 rounded-full bg-gray-200" />
          </div>
          <div class="grid sm:grid-cols-5">
            <div class="p-6 sm:col-span-3">
              <p class="text-lg font-medium">“Hope” is the thing with feathers</p>
              <p class="mb-5 text-sm text-textSecondary">by Emily Dickinson</p>
              <p class="font-newsreader text-lg leading-loose">
                “Hope” is
                <mark class="demo-mark bg-primary/15 decoration-primary" data-n="1">the thing with feathers</mark> -<br />
                That perches in the soul -<br />
                And sings the tune without the words -<br />
                And <mark class="demo-mark bg-transparent decoration-primary/40" data-n="2">never stops - at all -</mark>
              </p>
            </div>
            <div class="space-y-3 border-t border-gray-100 bg-surface p-4 sm:col-span-2 sm:border-l sm:border-t-0">
              <div class="rounded-lg border border-primary bg-white p-3 shadow-md">
                <p class="mb-1 text-xs font-semibold text-primary">1</p>
                <p class="text-xs leading-relaxed text-textSecondary">
                  Hope is pictured as a small bird, an extended metaphor that runs through the whole poem.
                </p>
              </div>
              <div class="rounded-lg border border-gray-200 bg-white p-3">
                <p class="mb-1 text-xs font-semibold text-primary">2</p>
                <p class="text-xs leading-relaxed text-textSecondary">
                  Dickinson’s signature dashes force a pause, making the bird’s song feel endless.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="scroll-mt-20 border-t border-gray-100 bg-surface py-24">
      <div class="mx-auto max-w-6xl px-6">
        <div class="mx-auto mb-16 max-w-2xl text-center">
          <h2 class="font-playfair text-4xl font-semibold">Read slowly. Understand deeply.</h2>
          <p class="mt-4 text-lg text-textSecondary">
            Everything you need to study a text closely, kept in one place.
          </p>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
          <div v-for="feature in features" :key="feature.title" class="rounded-xl border border-gray-200 bg-white p-8">
            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-primary/10">
              <Icon :icon="feature.icon" class="h-5 w-5 text-primary" />
            </div>
            <h3 class="mb-2 text-xl font-semibold">{{ feature.title }}</h3>
            <p class="leading-relaxed text-textSecondary">{{ feature.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="scroll-mt-20 py-24">
      <div class="mx-auto max-w-6xl px-6">
        <h2 class="mb-16 text-center font-playfair text-4xl font-semibold">How it works</h2>
        <div class="grid gap-12 md:grid-cols-3">
          <div v-for="(step, i) in steps" :key="step.title" class="text-center">
            <div
              class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-primary font-playfair text-2xl text-textLight"
            >
              {{ i + 1 }}
            </div>
            <h3 class="mb-2 text-xl font-semibold">{{ step.title }}</h3>
            <p class="leading-relaxed text-textSecondary">{{ step.text }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Final call to action -->
    <section class="px-6 pb-24">
      <div class="cta-background mx-auto max-w-6xl rounded-2xl px-8 py-16 text-center shadow-lg">
        <h2 class="font-playfair text-4xl font-semibold text-textLight">Start your first annotated text</h2>
        <p class="mx-auto mt-4 max-w-xl text-lg text-textLight opacity-90">
          Free to start. Paste something you love and see it come alive.
        </p>
        <Link
          href="/register"
          class="mt-8 inline-block rounded-md bg-white px-6 py-3 font-medium text-primary shadow-sm hover:bg-surface"
        >
          Create your free account
        </Link>
      </div>
    </section>

    <footer class="border-t border-gray-100 py-10">
      <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-6 text-sm text-textSecondary sm:flex-row">
        <span class="font-playfair text-xl italic text-primary">Lensify</span>
        <span>© {{ new Date().getFullYear() }} Lensify</span>
      </div>
    </footer>
  </div>
</template>

<style lang="postcss" scoped>
  .demo-mark {
    @apply rounded-sm text-inherit underline decoration-2 underline-offset-4;

    &::after {
      @apply ml-0.5 align-super text-xs font-semibold text-primary;
      content: attr(data-n);
    }
  }
  .cta-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
