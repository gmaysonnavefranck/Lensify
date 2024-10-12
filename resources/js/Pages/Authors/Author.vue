<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import { Author } from '@/types/index';
  import { onMounted, ref } from 'vue';

  import axios from 'axios';

  interface authorProps {
    author: Author;
  }

  defineProps<authorProps>();


</script>

<template>
  <AuthLayout>
    <div class="flex h-full flex-col gap-3">
      <p>
        Name: {{ author.name }}
      </p>
      <p>
        Genres: {{ author.genres }}
      </p>
      <p>
        Themes: {{ author.themes }}
      </p>
      <p>
        Top works: {{ author.famous_works }}
      </p>
      <p>
        Facts: {{ author.key_facts }}
      </p>
      <p>
        Period: {{ author.period }}
      </p>
      <p>
        Born: {{ author.born_year }}
      </p>
      <p>
        Death: {{ author.death_year }}
      </p>
      <p>
        Nationality: {{ author.nationality }}
      </p>
    </div>
  </AuthLayout>
</template>
