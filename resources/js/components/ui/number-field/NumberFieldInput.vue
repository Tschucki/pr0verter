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
        'flex h-10 w-full rounded-md border border-input bg-background py-2 text-center text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        props.class
      )
    "
    data-slot="input" />
</template>
