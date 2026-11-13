<script setup>
import { cn } from '@/lib/utils';
import { NumberFieldInput } from 'radix-vue';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <NumberFieldInput
    :class="
      cn(
        'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border py-2 text-center text-base focus:ring-0 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:cursor-not-allowed disabled:opacity-50 md:text-sm pointer-coarse:h-11',
        props.class
      )
    "
    data-slot="input" />
</template>
