<script setup>
import { cn } from '@/lib/utils';
import { StepperTitle, useForwardProps } from 'radix-vue';

import { computed } from 'vue';

const props = defineProps({
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
  <StepperTitle
    :class="cn('text-md whitespace-nowrap font-semibold', props.class)"
    v-bind="forwarded">
    <slot />
  </StepperTitle>
</template>
