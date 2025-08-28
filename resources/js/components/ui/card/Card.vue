<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <div
    :class="
      cn(
        'bg-card text-card-foreground rounded-lg border shadow-xs',
        props.class
      )
    ">
    <slot />
  </div>
</template>
