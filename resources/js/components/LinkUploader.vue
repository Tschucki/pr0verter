<script setup>
import { ref } from 'vue';
import { Label } from '@/components/ui/label/index.js';
import { Input } from '@/components/ui/input/index.js';

const url = ref();
</script>

<template>
  <div class="flex h-60 shrink-0 rounded-md border p-4">
    <div class="flex w-full flex-col">
      <div class="space-y-1">
        <Label for="url">URL eingeben</Label>
        <Input id="url" v-model="url" class="w-full" />
        <p class="text-muted-foreground text-sm">
          Kann alles sein. YouTube, einfache Datei oder sonst was
        </p>
      </div>
    </div>
  </div>
</template>
