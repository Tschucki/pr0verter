<script setup>
import { cn } from '@/lib/utils';
import { useFormField } from './useFormField';

const props = defineProps({
  class: { type: null, required: false },
});

const { formDescriptionId } = useFormField();
</script>

<template>
  <p
    :id="formDescriptionId"
    :class="cn('text-muted-foreground text-sm', props.class)">
    <slot />
  </p>
</template>
