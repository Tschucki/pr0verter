<script setup>
import { Button } from '@/components/ui/button';

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils.js';
import {
  Stepper,
  StepperDescription,
  StepperItem,
  StepperSeparator,
  StepperTitle,
  StepperTrigger,
} from '@/components/ui/stepper';
import { Check, Dot, Download, Loader, Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  conversions: {
    type: Array,
    required: true,
  },
});

const pageProps = usePage().props;
const sessionId = pageProps.session.id;
const allConversions = ref(props.conversions);

const updateConversionWithProgress = (progressEvent) => {
  allConversions.value = allConversions.value.map((conversion) => {
    if (conversion.id === progressEvent.conversionId) {
      conversion.progressEvent = progressEvent;
    }
    return conversion;
  });
};

const updateConversionWithDownloadProgress = (downloadProgressEvent) => {
  allConversions.value = allConversions.value.map((conversion) => {
    if (conversion.id === downloadProgressEvent.conversionId) {
      if (
        downloadProgressEvent.speed !== null &&
        downloadProgressEvent.speed !== ''
      ) {
        conversion.downloadProgressEvent = downloadProgressEvent;
      }
    }
    return conversion;
  });
};

const updateConversion = (conversion) => {
  allConversions.value = allConversions.value.map((c) => {
    console.log(c);
    if (c.id === conversion.id) {
      c = { ...c, ...conversion };
    }
    return c;
  });
};

onMounted(() => {
  // eslint-disable-next-line no-undef
  Echo.channel(`session.${sessionId}`)
    .listen('ConversionProgressEvent', (event) => {
      updateConversionWithProgress(event);
    })
    .listen('DownloadProgress', (event) => {
      console.log(event);
      updateConversionWithDownloadProgress(event);
    })
    .listen('ConversionUpdated', (event) => {
      console.log(event);
      updateConversion(event.conversion);
    });
});
</script>

<template>
  <div class="grid w-full items-start gap-6">
    <h1 class="scroll-m-20 text-4xl font-extrabold tracking-tight lg:text-5xl">
      Meine Konvertierungen
    </h1>

    <div class="grid grid-cols-1">
      <Card
        v-for="(conversion, idx) in allConversions"
        :key="idx"
        :class="cn($attrs.class ?? '')">
        <CardHeader>
          <CardTitle class="truncate"
            >{{ conversion.file?.filename ?? 'Noch nicht vorhanden' }}
          </CardTitle>
          <CardDescription>
            <span v-if="conversion.file?.created_at_diff"
              >Hochgeladen {{ conversion.file.created_at_diff }}</span
            >
          </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4">
          <div>
            <Stepper
              class="mx-auto flex w-full flex-col justify-start gap-10"
              orientation="vertical">
              <StepperItem
                v-for="step in conversion.progress.filter(
                  (step) => step.visible
                )"
                :key="step.order"
                :step="step.order"
                class="relative flex w-full items-start gap-6">
                <StepperSeparator
                  v-if="
                    step !==
                    conversion.progress.filter((s) => s.visible)[
                      conversion.progress.filter((s) => s.visible).length - 1
                    ]
                  "
                  class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary" />

                <StepperTrigger as-child>
                  <Button
                    :class="[
                      step.completed &&
                        step.current_step &&
                        'ring-2 ring-ring ring-offset-2 ring-offset-background',
                    ]"
                    :disabled="step.current_step === false"
                    :variant="
                      step.completed || step.current_step
                        ? 'default'
                        : 'outline'
                    "
                    class="pointer-events-none z-10 shrink-0 rounded-full"
                    size="icon">
                    <Check v-if="step.completed" class="size-5" />
                    <Loader
                      v-else-if="step.current_step && step.completed === false"
                      class="size-5 animate-spin" />
                    <Dot v-else />
                  </Button>
                </StepperTrigger>

                <div class="flex flex-col gap-1">
                  <StepperTitle
                    :class="[step.current_step && 'text-primary']"
                    class="text-sm font-semibold transition lg:text-base">
                    {{ step.title }}
                  </StepperTitle>
                  <StepperDescription
                    :class="[step.current_step && 'text-primary']"
                    class="sr-only text-xs text-muted-foreground transition md:not-sr-only lg:text-sm">
                    {{ step.description }}
                    <strong
                      v-if="
                        step.status === 'processing' && conversion.progressEvent
                      ">
                      <br />
                      Fortschritt: {{ conversion.progressEvent.percentage }}%
                    </strong>
                    <strong
                      v-if="
                        step.status === 'downloading' &&
                        conversion.downloadProgressEvent
                      ">
                      <br />
                      Fortschritt:
                      {{ conversion.downloadProgressEvent.percentage }}<br />
                      Geschwindigkeit:
                      {{ conversion.downloadProgressEvent.speed }}<br />
                      Verbleibend: {{ conversion.downloadProgressEvent.eta }}
                    </strong>
                  </StepperDescription>
                </div>
              </StepperItem>
            </Stepper>
          </div>
        </CardContent>
        <CardFooter>
          <a
            :class="{
              'cursor-not-allowed': !conversion.downloadable,
              'w-full': true,
            }"
            :href="
              conversion.downloadable
                ? route('conversions.download', conversion.id)
                : null
            "
            target="_blank">
            <Button :disabled="!conversion.downloadable" class="w-full">
              <Download v-if="conversion.downloadable" class="mr-2 h-4 w-4" />
              <Loader2 v-else class="mr-2 h-4 w-4 animate-spin" />
              {{ conversion.downloadable ? 'Datei herunterladen' : '' }}
            </Button>
          </a>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>

<style scoped></style>
