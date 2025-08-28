<script setup>
import { cn } from '@/lib/utils';
import { TabsList } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps({
  loop: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});
</script>

<template>
  <TabsList
    :class="
      cn(
        'bg-muted text-muted-foreground inline-flex items-center justify-center rounded-md p-1',
        props.class
      )
    "
    v-bind="delegatedProps">
    <slot />
  </TabsList>
</template>
