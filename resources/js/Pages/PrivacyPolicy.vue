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
    class="markdown prose mx-auto max-w-5xl grow py-6"
    v-html="content"></div>
  <div class="markdown prose mx-auto max-w-5xl grow py-6"></div>
</template>

<style scoped>
.markdown.prose * {
  color: white !important;
}

.markdown.prose
  :where(ul > li):not(
    :where([class~='not-prose'], [class~='not-prose'] *)
  )::marker {
  color: white !important;
}
</style>
