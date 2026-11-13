<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({
  content: String,
});
</script>

<template>
  <Head>
    <title>Datenschutzerklärung - pr0verter</title>
    <meta content="Datenschutzerklärung für pr0verter" name="description" />
  </Head>

  <div
    class="prose prose-invert mx-auto max-w-5xl grow py-6"
    v-html="content"></div>
</template>
