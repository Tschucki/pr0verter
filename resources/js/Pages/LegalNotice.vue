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
    class="markdown prose mx-auto max-w-5xl flex-grow py-6"
    v-html="content"></div>
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
