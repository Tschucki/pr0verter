<script setup>
import { CloudUpload } from 'lucide-vue-next';
import { useDropZone } from '@vueuse/core';
import { ref } from 'vue';

const dropZoneRef = ref();
const file = ref();

function onDrop(inputFile) {
  if (inputFile.length === 0) {
    return;
  }
  file.value = inputFile[0];
}

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop,
  multiple: false,
  preventDefaultForUnhandled: false,
});
</script>

<template>
  <div
    ref="dropZoneRef"
    :class="{
      'border-dashed': !isOverDropZone,
      'border-primary': isOverDropZone,
    }"
    class="relative flex h-60 shrink-0 items-center justify-center rounded-md border">
    <label
      class="absolute inset-0 h-full w-full cursor-pointer rounded-md"
      for="file-input"></label>
    <div
      class="mx-auto flex max-w-[420px] flex-col items-center justify-center text-center">
      <CloudUpload class="size-8" />
      <h3 class="mt-4 text-lg font-semibold">
        {{
          isOverDropZone ? 'Lass die Maus los' : 'Datei hochladen oder ablegen'
        }}
      </h3>
      <p class="mb-4 mt-2 text-sm text-muted-foreground">
        Datei hier hineinziehen oder anklicken um Datei hochzuladen.<br />
        Upload beginnt erst beim Starten der Konvertierung!
      </p>
    </div>
    <input id="file-input" :value="file" class="sr-only" type="file" />
  </div>
</template>
