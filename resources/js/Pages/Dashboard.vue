<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Icon from '@/Components/Icon.vue';
  import { Link } from '@inertiajs/vue3';
  import { computed, ref } from 'vue';

  type Day = { date: string; pages: number; seconds: number };
  type Recent = {
    id: number;
    title: string;
    author: string | null;
    book: { id: number; title: string; chapter: number; chapters: number } | null;
    percent: number;
  };

  const props = defineProps<{
    days: Day[];
    recent: Recent[];
    quote: { content: string; text: { id: number; title: string } | null } | null;
    word: { word: string; definition: string | null; context: string | null } | null;
    totals: Record<'books' | 'texts' | 'footnotes' | 'highlights' | 'quotes' | 'words', number>;
  }>();
  defineOptions({ layout: AuthLayout });

  // Dates are the reader's local days, matching what the reader page records
  const dayKey = (date: Date) => date.toLocaleDateString('en-CA');
  const byDate = new Map(props.days.map((day) => [day.date, day]));
  const daysAgo = (n: number) => {
    const date = new Date();
    date.setDate(date.getDate() - n);
    return date;
  };
  const lastDays = (count: number) =>
    Array.from({ length: count }, (_, i) => {
      const date = daysAgo(count - 1 - i);
      const day = byDate.get(dayKey(date));
      return { date, key: dayKey(date), pages: day?.pages ?? 0, seconds: day?.seconds ?? 0 };
    });

  // Consecutive days read, ending today, or yesterday if today's reading hasn't happened yet
  const streak = computed(() => {
    let n = byDate.has(dayKey(new Date())) ? 0 : 1;
    let count = 0;
    while (byDate.has(dayKey(daysAgo(n)))) {
      count++;
      n++;
    }
    return count;
  });
  const readToday = computed(() => byDate.has(dayKey(new Date())));

  // This calendar week, Sunday to Saturday; days after today are shown but empty
  const week = computed(() => {
    const sinceSunday = new Date().getDay();
    return Array.from({ length: 7 }, (_, i) => {
      const date = daysAgo(sinceSunday - i);
      const day = byDate.get(dayKey(date));
      return { date, key: dayKey(date), pages: day?.pages ?? 0, seconds: day?.seconds ?? 0, offset: i - sinceSunday };
    });
  });
  const chart = computed(() => lastDays(14));
  const today = computed(() => week.value.find((day) => day.offset === 0)!);
  const weekTotals = computed(() => ({
    pages: week.value.reduce((sum, day) => sum + day.pages, 0),
    seconds: week.value.reduce((sum, day) => sum + day.seconds, 0),
  }));
  const chartMax = computed(() => Math.max(...chart.value.map((day) => day.pages), 1));
  const hoveredBar = ref<number | null>(null);

  function duration(seconds: number) {
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes} min`;
    return `${Math.floor(minutes / 60)} h ${minutes % 60 ? `${minutes % 60} min` : ''}`.trim();
  }
  const weekday = (date: Date, style: 'narrow' | 'short' = 'short') => date.toLocaleDateString(undefined, { weekday: style });
  const shortDate = (date: Date) => date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });

  const hour = new Date().getHours();
  const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';

  const current = computed(() => props.recent[0] ?? null);
  const others = computed(() => props.recent.slice(1));
  const progressLabel = (item: Recent) =>
    item.book ? `Chapter ${item.book.chapter} of ${item.book.chapters}` : `${item.percent}% read`;
  const progressPercent = (item: Recent) =>
    item.book ? Math.round((100 * (item.book.chapter - 1 + item.percent / 100)) / item.book.chapters) : item.percent;

  const totalsList = computed(() => [
    { label: 'Books', value: props.totals.books, href: '/books' },
    { label: 'Texts', value: props.totals.texts, href: '/texts' },
    { label: 'Footnotes', value: props.totals.footnotes },
    { label: 'Highlights', value: props.totals.highlights },
    { label: 'Quotes', value: props.totals.quotes, href: '/quotes' },
    { label: 'Words', value: props.totals.words, href: '/vocabulary' },
  ]);
</script>

<template>
  <Head title="Home" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-8 py-8">
      <div>
        <h1 class="font-playfair text-3xl font-semibold text-textPrimary">{{ greeting }}</h1>
        <p class="mt-1 text-textSecondary">
          {{ new Date().toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' }) }}
        </p>
      </div>

      <div class="grid gap-6 md:grid-cols-3">
        <!-- Continue reading -->
        <Link
          v-if="current"
          :href="`/texts/${current.id}`"
          class="card-background group relative flex flex-col justify-between gap-6 overflow-hidden rounded-2xl p-7 text-textLight shadow-md md:col-span-2"
        >
          <div class="relative">
            <p class="text-sm font-medium uppercase tracking-wide text-textLight/70">Continue reading</p>
            <p class="mt-2 font-playfair text-2xl font-semibold leading-tight">{{ current.book?.title ?? current.title }}</p>
            <p class="mt-1 text-textLight/80">
              {{ current.book ? current.title : (current.author ?? 'Unknown author') }}
            </p>
          </div>
          <div class="relative">
            <div class="mb-2 flex items-center justify-between text-sm text-textLight/80">
              <span>{{ progressLabel(current) }}</span>
              <span class="flex items-center gap-1 font-medium text-textLight group-hover:underline">
                Open <Icon icon="chevronRight" class="h-4 w-4" />
              </span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-[rgb(255_255_255/0.2)]">
              <div class="h-full rounded-full bg-[#fff]" :style="{ width: `${progressPercent(current)}%` }" />
            </div>
          </div>
        </Link>
        <div
          v-else
          class="flex flex-col justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-200 p-7 md:col-span-2"
        >
          <p class="font-medium text-textPrimary">Nothing in progress yet</p>
          <p class="text-sm text-textSecondary">Open a text or a book and it shows up here, ready to continue.</p>
          <div class="flex gap-4 text-sm font-medium">
            <Link href="/books" class="text-primary hover:underline">Your books →</Link>
            <Link href="/texts" class="text-primary hover:underline">Your texts →</Link>
          </div>
        </div>

        <!-- Streak -->
        <div class="flex flex-col justify-between gap-5 rounded-2xl border border-gray-200 bg-white p-6">
          <div class="flex items-center gap-3">
            <div
              class="flex h-11 w-11 items-center justify-center rounded-full"
              :class="readToday ? 'bg-orange-100 text-orange-500 dark:bg-orange-500/20' : 'bg-gray-100 text-textSecondary'"
            >
              <Icon icon="flame" class="h-6 w-6" />
            </div>
            <div>
              <p class="text-3xl font-semibold tabular-nums text-textPrimary">{{ streak }}</p>
              <p class="text-sm text-textSecondary">day{{ streak === 1 ? '' : 's' }} in a row</p>
            </div>
          </div>
          <div class="flex justify-between">
            <div v-for="day in week" :key="day.key" class="flex flex-col items-center gap-1.5">
              <div
                class="flex h-7 w-7 items-center justify-center rounded-full"
                :class="
                  day.pages || day.seconds
                    ? 'bg-orange-400 text-white'
                    : day.offset === 0
                      ? 'border-2 border-orange-300'
                      : day.offset > 0
                        ? 'border border-dashed border-gray-300'
                        : 'bg-gray-100'
                "
                :title="day.offset > 0 ? weekday(day.date) : `${weekday(day.date)}: ${day.pages} page${day.pages === 1 ? '' : 's'}`"
              >
                <Icon v-if="day.pages || day.seconds" icon="check" class="h-4 w-4" />
              </div>
              <span class="text-xs" :class="day.offset === 0 ? 'font-semibold text-textPrimary' : 'text-textSecondary'">
                {{ weekday(day.date, 'narrow') }}
              </span>
            </div>
          </div>
          <p class="text-xs text-textSecondary">
            {{ readToday ? 'You read today. Nice.' : streak ? 'Read a page today to keep it going.' : 'Turn a page to start a streak.' }}
          </p>
        </div>
      </div>

      <!-- Pages and time -->
      <div class="rounded-2xl border border-gray-200 bg-white p-6">
        <div class="mb-6 grid grid-cols-2 gap-6 md:grid-cols-4">
          <div>
            <p class="text-sm text-textSecondary">Pages today</p>
            <p class="text-2xl font-semibold tabular-nums text-textPrimary">{{ today.pages }}</p>
          </div>
          <div>
            <p class="text-sm text-textSecondary">Time today</p>
            <p class="text-2xl font-semibold tabular-nums text-textPrimary">{{ duration(today.seconds) }}</p>
          </div>
          <div>
            <p class="text-sm text-textSecondary">Pages this week</p>
            <p class="text-2xl font-semibold tabular-nums text-textPrimary">{{ weekTotals.pages }}</p>
          </div>
          <div>
            <p class="text-sm text-textSecondary">Time this week</p>
            <p class="text-2xl font-semibold tabular-nums text-textPrimary">{{ duration(weekTotals.seconds) }}</p>
          </div>
        </div>

        <p class="mb-3 text-sm font-medium text-textPrimary">Pages read, last 14 days</p>
        <div class="relative">
          <div class="flex h-32 items-end gap-[2px] border-b border-gray-200" role="img" :aria-label="`Pages read per day for the last 14 days`">
            <div
              v-for="(day, i) in chart"
              :key="day.key"
              class="flex h-full flex-1 cursor-default items-end justify-center px-1"
              @mouseenter="hoveredBar = i"
              @mouseleave="hoveredBar = null"
            >
              <div
                class="w-full max-w-8 rounded-t transition-colors"
                :class="hoveredBar === i ? 'bg-primary' : 'bg-primary/70'"
                :style="{ height: day.pages ? `${Math.max((100 * day.pages) / chartMax, 3)}%` : '0' }"
              />
            </div>
          </div>
          <div
            v-if="hoveredBar !== null"
            class="pointer-events-none absolute -top-2 z-10 -translate-x-1/2 -translate-y-full whitespace-nowrap rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-md"
            :style="{ left: `${((hoveredBar + 0.5) / chart.length) * 100}%` }"
          >
            <p class="font-medium text-textPrimary">{{ weekday(chart[hoveredBar].date) }}, {{ shortDate(chart[hoveredBar].date) }}</p>
            <p class="text-textSecondary">
              {{ chart[hoveredBar].pages }} page{{ chart[hoveredBar].pages === 1 ? '' : 's' }} ·
              {{ duration(chart[hoveredBar].seconds) }}
            </p>
          </div>
          <div class="mt-1.5 flex justify-between text-xs text-textSecondary">
            <span>{{ shortDate(chart[0].date) }}</span>
            <span>Today</span>
          </div>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-2">
        <!-- Quote of the day -->
        <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-6">
          <p class="flex items-center gap-2 text-sm font-medium text-textSecondary">
            <Icon icon="quotes" class="h-4 w-4 text-primary" /> Quote of the day
          </p>
          <template v-if="quote">
            <p class="line-clamp-6 font-newsreader text-lg italic leading-relaxed text-textPrimary">“{{ quote.content }}”</p>
            <Link v-if="quote.text" :href="`/texts/${quote.text.id}`" class="text-sm text-primary hover:underline">
              {{ quote.text.title }}
            </Link>
          </template>
          <p v-else class="text-sm text-textSecondary">
            Select a passage while reading and choose <span class="font-medium">Quote</span>. One of them shows up here each day.
          </p>
        </div>

        <!-- Word of the day -->
        <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-6">
          <p class="flex items-center gap-2 text-sm font-medium text-textSecondary">
            <Icon icon="bookA" class="h-4 w-4 text-primary" /> Word of the day
          </p>
          <template v-if="word">
            <p class="font-newsreader text-2xl font-semibold text-textPrimary">{{ word.word }}</p>
            <p v-if="word.definition" class="text-sm text-textPrimary">{{ word.definition }}</p>
            <p v-if="word.context" class="line-clamp-2 border-l-2 border-gray-200 pl-3 text-sm italic text-textSecondary">
              {{ word.context }}
            </p>
          </template>
          <p v-else class="text-sm text-textSecondary">
            Select a word while reading and choose <span class="font-medium">Word</span> to build your vocabulary.
          </p>
        </div>
      </div>

      <!-- Recently read -->
      <div v-if="others.length" class="flex flex-col gap-3">
        <p class="text-sm font-medium text-textPrimary">Recently read</p>
        <div class="grid gap-3 md:grid-cols-3">
          <Link
            v-for="item in others"
            :key="item.id"
            :href="`/texts/${item.id}`"
            class="flex flex-col gap-2 rounded-xl border border-gray-200 bg-white p-4 transition-colors hover:border-primary/40"
          >
            <p class="truncate font-medium text-textPrimary">{{ item.book?.title ?? item.title }}</p>
            <p class="truncate text-sm text-textSecondary">{{ item.book ? item.title : (item.author ?? 'Unknown author') }}</p>
            <div class="mt-auto flex items-center gap-2">
              <div class="h-1 flex-1 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-primary/70" :style="{ width: `${progressPercent(item)}%` }" />
              </div>
              <span class="text-xs tabular-nums text-textSecondary">{{ progressLabel(item) }}</span>
            </div>
          </Link>
        </div>
      </div>

      <!-- Library totals -->
      <div class="grid grid-cols-3 gap-3 md:grid-cols-6">
        <component
          :is="item.href ? Link : 'div'"
          v-for="item in totalsList"
          :key="item.label"
          :href="item.href"
          class="rounded-xl border border-gray-200 bg-white px-4 py-3"
          :class="item.href && 'transition-colors hover:border-primary/40'"
        >
          <p class="text-xl font-semibold tabular-nums text-textPrimary">{{ item.value }}</p>
          <p class="text-xs text-textSecondary">{{ item.label }}</p>
        </component>
      </div>
    </div>
  </div>
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
