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
import {
  Check,
  Dot,
  Download,
  Link as LinkIcon,
  Loader,
  Loader2,
  StopCircle,
  X,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

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
    if (c.id === conversion.id) {
      c = { ...c, ...conversion };
    }
    return c;
  });
};

const togglePublic = async (conversion) => {
  let togglePromise = () =>
    new Promise((resolve, reject) => {
      // eslint-disable-next-line no-undef
      axios.patch(route('conversions.toggle-public', conversion.id)).then(
        (response) => {
          conversion.public = response.data.public;
          if (conversion.public) {
            navigator.clipboard.writeText(
              // eslint-disable-next-line no-undef
              route('conversions.download', conversion.id)
            );
            toast.info('Öffentlicher Link in die Zwischenablage kopiert');
          }
          resolve(response.data);
        },
        (error) => {
          reject(error);
        }
      );
    });

  toast.promise(togglePromise, {
    loading: 'Öffentlicher Link wird aktualisiert',
    success: 'Öffentlicher Link erfolgreich aktualisiert',
    error: 'Fehler beim Aktualisieren des öffentlichen Links',
  });
};

const cancelConversion = async (conversion) => {
  let togglePromise = () =>
    new Promise((resolve, reject) => {
      // eslint-disable-next-line no-undef
      axios.patch(route('conversions.cancel', conversion.id)).then(
        (response) => {
          conversion.status = response.data.status;
          resolve(response.data);
        },
        (error) => {
          reject(error);
        }
      );
    });

  toast.promise(togglePromise, {
    loading: 'Konvertierung wird abgebrochen',
    success: 'Konvertierung abgebrochen',
    error: 'Fehler beim Abbrechen des Konvertierung',
  });
};

onMounted(() => {
  // eslint-disable-next-line no-undef
  Echo.channel(`session.${sessionId}`)
    .listen('ConversionProgressEvent', (event) => {
      updateConversionWithProgress(event);
    })
    .listen('DownloadProgress', (event) => {
      updateConversionWithDownloadProgress(event);
    })
    .listen('ConversionUpdated', (event) => {
      updateConversion(event.conversion);
    });
});
</script>

<template>
  <Head>
    <title>Pr0verter - Deine Konvertierungen</title>
    <meta
      name="description"
      content="Der pr0verter ist ein Converter für das pr0gramm. Hier kannst Videos konvertieren." />
  </Head>
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
                  class="bg-muted group-data-[state=completed]:bg-primary absolute top-[38px] left-[18px] block h-[105%] w-0.5 shrink-0 rounded-full" />

                <StepperTrigger as-child>
                  <Button
                    :class="[
                      step.completed &&
                        step.current_step &&
                        'ring-ring ring-offset-background ring-2 ring-offset-2',
                    ]"
                    :disabled="step.current_step === false"
                    :variant="
                      step.completed || step.current_step
                        ? 'default'
                        : 'outline-solid'
                    "
                    class="pointer-events-none z-10 shrink-0 rounded-full"
                    size="icon">
                    <Check v-if="step.completed" class="size-5" />
                    <X
                      v-else-if="
                        step.completed === false && step.current_step === false
                      "
                      class="size-5" />
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
                    class="text-muted-foreground text-xs transition lg:text-sm">
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
                    <strong
                      v-if="
                        step.status === 'failed' && conversion.error_message
                      ">
                      <br />
                      {{ conversion.error_message }}
                    </strong>
                  </StepperDescription>
                </div>
              </StepperItem>
            </Stepper>
          </div>
        </CardContent>
        <CardFooter>
          <div class="grid w-full grid-cols-1 gap-4">
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
              <Button
                id="download-button"
                :disabled="!conversion.downloadable"
                class="w-full">
                <Download v-if="conversion.downloadable" class="mr-2 h-4 w-4" />
                <Loader2 v-else class="mr-2 h-4 w-4 animate-spin" />
                {{ conversion.downloadable ? 'Datei herunterladen' : '' }}
              </Button>
            </a>
            <Button
              :disabled="!conversion.downloadable"
              class="w-full"
              variant="outline"
              @click="togglePublic(conversion)">
              <LinkIcon class="mr-2 size-4"></LinkIcon>
              {{
                conversion.public === false
                  ? 'Link öffentlich machen & kopieren'
                  : 'Öffentlichen Link deaktivieren'
              }}
            </Button>
            <Button
              id="cancel-conversion-button"
              :disabled="
                conversion.downloadable || conversion.status === 'canceled'
              "
              class="w-full"
              variant="outline"
              @click="cancelConversion(conversion)">
              <StopCircle class="mr-2 size-4"></StopCircle>
              Konvertierung abbrechen
            </Button>
            <p class="text-muted-foreground text-sm">
              Öffentliche Links können direkt beim Upload auf pr0gramm
              eingegeben werden.
              <a
                target="_blank"
                href="https://pr0gramm.com/upload"
                class="text-primary"
                >Direkt zum Upload</a
              >
            </p>
          </div>
        </CardFooter>
      </Card>
      <Card v-if="allConversions.length === 0" class="text-center">
        <CardHeader>
          <CardTitle class="text-2xl"
            >Keine Konvertierungen vorhanden
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Link :href="route('home')">
            <Button class="mt-4" type="button">
              Neue Konvertierung starten
            </Button>
          </Link>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped></style>
