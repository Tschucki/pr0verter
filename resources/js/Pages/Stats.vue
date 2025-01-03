<script setup>
import { Head } from '@inertiajs/vue3';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card/index.js';

defineProps({
  stats: {
    type: Array,
    required: true,
  },
});
</script>

<template>
  <Head>
    <title>Pr0verter - Statistik</title>
    <meta
      name="description"
      content="Der pr0verter ist ein Converter für das pr0gramm. Hier kannst Videos konvertieren." />
  </Head>
  <div class="grid w-full items-start gap-6">
    <h1 class="scroll-m-20 text-4xl font-extrabold tracking-tight lg:text-5xl">
      Statistik
    </h1>

    <div v-if="stats" class="my-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <Card v-for="(stat, idx) in stats" :key="idx">
        <CardHeader
          class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">
            {{ stat.title }}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ stat.value }}
          </div>
        </CardContent>
      </Card>
    </div>
    <div class="text-muted-foreground">
      <p>Folgende Domains fungieren als Synonyme für youtube.com:</p>
      <ul>
        <li>www.youtube.com</li>
        <li>youtu.be</li>
        <li>m.youtube.com</li>
        <li>music.youtube.com</li>
        <li>youtube-nocookie.com</li>
      </ul>
    </div>
  </div>
</template>

<style scoped></style>
