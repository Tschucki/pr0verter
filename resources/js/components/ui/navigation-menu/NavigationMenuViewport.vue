<script setup>
import { cn } from '@/lib/utils';
import { NavigationMenuViewport, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({
  forceMount: { type: Boolean, required: false },
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
  <div class="absolute top-full left-0 flex justify-center">
    <NavigationMenuViewport
      :class="
        cn(
          'origin-top-center bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-90 relative mt-1.5 h-(--radix-navigation-menu-viewport-height) w-full overflow-hidden rounded-md border shadow-lg md:w-(--radix-navigation-menu-viewport-width)',
          props.class
        )
      "
      v-bind="forwardedProps" />
  </div>
</template>
