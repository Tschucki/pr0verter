<script setup>
import { cn } from '@/lib/utils';
import { TabsTrigger, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({
  value: { type: [String, Number], required: true },
  disabled: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
  <TabsTrigger
    :class="
      cn(
        'ring-offset-background focus-visible:ring-ring data-[state=active]:bg-background data-[state=active]:text-foreground inline-flex items-center justify-center rounded-sm px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-all focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:pointer-events-none disabled:opacity-50 data-[state=active]:shadow-xs',
        props.class
      )
    "
    v-bind="forwardedProps">
    <span class="truncate">
      <slot />
    </span>
  </TabsTrigger>
</template>
