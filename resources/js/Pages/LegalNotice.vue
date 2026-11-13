<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
  content: String,
});
</script>

<template>
  <Head>
    <title>Impressum - pr0verter</title>
    <meta
      content="Impressum für pr0verter von Viovenia UG (haftungsbeschränkt)"
      name="description" />
  </Head>

  <div
    class="prose prose-invert mx-auto max-w-5xl grow py-6"
    v-html="content"></div>
</template>
