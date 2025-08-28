<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <p :class="cn('text-muted-foreground text-sm', props.class)">
    <slot />
  </p>
</template>
