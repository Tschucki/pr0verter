<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <h5 :class="cn('mb-1 leading-none font-medium tracking-tight', props.class)">
    <slot />
  </h5>
</template>
