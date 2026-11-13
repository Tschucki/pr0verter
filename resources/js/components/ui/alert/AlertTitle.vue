<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <p :class="cn('mb-1 leading-none font-semibold tracking-tight', props.class)">
    <slot />
  </p>
</template>
