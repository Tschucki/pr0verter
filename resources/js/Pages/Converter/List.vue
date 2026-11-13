<script setup>
import { Button, buttonVariants } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

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
} from '@/components/ui/stepper';
import {
  Captions,
  CaptionsOff,
  Check,
  Dot,
  Download,
  Film,
  Link as LinkIcon,
  Loader,
  Loader2,
  Music,
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
      // Keep the last known speed while yt-dlp briefly reports it as unknown.
      conversion.downloadProgressEvent = {
        ...downloadProgressEvent,
        speedInMegabytes:
          downloadProgressEvent.speedInMegabytes ??
          conversion.downloadProgressEvent?.speedInMegabytes ??
          null,
      };
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

const megabytesFormatter = new Intl.NumberFormat('de-DE', {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const formatSpeed = (speedInMegabytes) =>
  speedInMegabytes === null || speedInMegabytes === undefined
    ? 'wird ermittelt …'
    : `${megabytesFormatter.format(speedInMegabytes)} MB/s`;

const subtitleTooltip = (status) => {
  switch (status) {
    case 'embedded':
      return 'Untertitel zuschaltbar (im Player ein-/ausblendbar)';
    case 'burnt':
      return 'Untertitel immer sichtbar (fest im Bild)';
    case 'unavailable':
      return 'Keine Untertitel gefunden';
    default:
      return '';
  }
};

// Vorlesetext zum Schritt-Symbol: Haken, Kreuz und Spinner nicht nur visuell.
const stepState = (step) => {
  if (step.completed) {
    return 'Erledigt';
  }
  if (step.current_step) {
    return 'Läuft';
  }
  return step.completed === false && step.current_step === false
    ? 'Nicht ausgeführt'
    : 'Ausstehend';
};

// Schritt-Symbol im Look eines runden Buttons, aber ohne Button-Rolle und
// immer 36 px (kein Bedienelement, daher keine 44-px-Regel).
const stepIconClass = (step) =>
  cn(
    buttonVariants({
      variant: step.completed || step.current_step ? 'default' : 'outline',
      size: 'icon',
    }),
    step.completed &&
      step.current_step &&
      'ring-ring ring-offset-background ring-2 ring-offset-2',
    step.current_step === false && 'opacity-50',
    'pointer-coarse:size-9 z-10 shrink-0 rounded-full'
  );

const formatQualityTier = (tier) => {
  const tiers = {
    ultra_hd: '4K Ultra HD (2160p)',
    full_hd: 'Full HD (1080p)',
    hd: 'HD (720p)',
    sd: 'SD (480p)',
    low_res: 'Low Resolution (360p)',
  };
  return tiers[tier] || tier;
};

const getQualityTierVariant = (tier) => {
  const variants = {
    ultra_hd: 'default',
    full_hd: 'default',
    hd: 'secondary',
    sd: 'secondary',
    low_res: 'outline',
  };
  return variants[tier] || 'outline';
};

onMounted(() => {
  // eslint-disable-next-line no-undef
  Echo?.channel(`session.${sessionId}`)
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
        <div class="flex flex-col gap-4 p-6 md:flex-row">
          <div
            class="bg-muted relative aspect-video h-28 shrink-0 overflow-hidden rounded">
            <img
              v-if="conversion.thumbnail_path"
              :src="`${route('conversions.thumbnail', conversion.id)}?v=${conversion.updated_at}`"
              :alt="`Vorschau für ${conversion.file?.filename ?? 'Konvertierung'}`"
              class="absolute inset-0 h-full w-full object-contain"
              loading="lazy" />
            <div v-else class="flex h-full w-full items-center justify-center">
              <Music
                v-if="conversion.audio_only"
                aria-hidden="true"
                class="text-muted-foreground h-6 w-6" />
              <Film
                v-else
                aria-hidden="true"
                class="text-muted-foreground h-6 w-6" />
            </div>
          </div>
          <div class="-m-6 min-w-0 flex-1 md:-my-6 md:-mr-6 md:ml-0">
            <CardHeader>
              <CardTitle class="truncate"
                >{{ conversion.file?.filename ?? 'Noch nicht vorhanden' }}
              </CardTitle>
              <CardDescription>
                <span v-if="conversion.file?.created_at_diff"
                  >Hochgeladen {{ conversion.file.created_at_diff }}</span
                >
              </CardDescription>
              <div
                v-if="
                  conversion.quality_tier ||
                  (conversion.status === 'finished' &&
                    conversion.subtitle_status)
                "
                class="mt-4 flex flex-wrap items-center gap-2">
                <Badge
                  v-if="conversion.quality_tier"
                  :variant="getQualityTierVariant(conversion.quality_tier)">
                  {{ formatQualityTier(conversion.quality_tier) }}
                </Badge>
                <span
                  v-if="
                    conversion.status === 'finished' &&
                    conversion.subtitle_status
                  "
                  class="inline-flex items-center"
                  role="img"
                  :aria-label="subtitleTooltip(conversion.subtitle_status)"
                  :title="subtitleTooltip(conversion.subtitle_status)">
                  <Captions
                    v-if="
                      conversion.subtitle_status === 'embedded' ||
                      conversion.subtitle_status === 'burnt'
                    "
                    :class="[
                      'size-4',
                      conversion.subtitle_status === 'burnt'
                        ? 'text-foreground'
                        : 'text-muted-foreground',
                    ]" />
                  <CaptionsOff
                    v-else-if="conversion.subtitle_status === 'unavailable'"
                    class="text-muted-foreground size-4" />
                </span>
              </div>
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
                          conversion.progress.filter((s) => s.visible).length -
                            1
                        ]
                      "
                      class="bg-muted group-data-[state=completed]:bg-primary absolute top-[38px] left-[18px] block h-[105%] w-0.5 shrink-0 rounded-full" />

                    <div aria-hidden="true" :class="stepIconClass(step)">
                      <Check v-if="step.completed" class="size-5" />
                      <X
                        v-else-if="
                          step.completed === false &&
                          step.current_step === false
                        "
                        class="size-5" />
                      <Loader
                        v-else-if="
                          step.current_step && step.completed === false
                        "
                        class="size-5 animate-spin" />
                      <Dot v-else />
                    </div>

                    <div class="flex flex-col gap-1">
                      <StepperTitle
                        as="h3"
                        :class="[step.current_step && 'text-primary']"
                        class="text-sm font-semibold transition lg:text-base">
                        <span class="sr-only">{{ stepState(step) }}: </span>
                        {{ step.title }}
                      </StepperTitle>
                      <StepperDescription
                        :class="[step.current_step && 'text-primary']"
                        class="text-muted-foreground text-xs transition lg:text-sm">
                        {{ step.description }}
                        <strong
                          v-if="
                            step.status === 'processing' &&
                            conversion.progressEvent
                          ">
                          <br />
                          Fortschritt:
                          {{ conversion.progressEvent.percentage }}%
                        </strong>
                        <strong
                          v-if="
                            step.status === 'downloading' &&
                            conversion.downloadProgressEvent
                          ">
                          <br />
                          Fortschritt:
                          {{ conversion.downloadProgressEvent.percentage
                          }}<br />
                          Geschwindigkeit:
                          <span class="whitespace-nowrap tabular-nums">{{
                            formatSpeed(
                              conversion.downloadProgressEvent.speedInMegabytes
                            )
                          }}</span
                          ><template v-if="conversion.downloadProgressEvent.eta"
                            ><br />
                            Verbleibend:
                            {{ conversion.downloadProgressEvent.eta }}</template
                          >
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
                <Button v-if="conversion.downloadable" as-child class="w-full">
                  <a
                    id="download-button"
                    :href="route('conversions.download', conversion.id)"
                    target="_blank">
                    <Download aria-hidden="true" />
                    Datei herunterladen
                  </a>
                </Button>
                <Button v-else id="download-button" disabled class="w-full">
                  <Loader2 class="animate-spin" aria-hidden="true" />
                  Datei wird vorbereitet …
                </Button>
                <Button
                  :disabled="!conversion.downloadable"
                  class="w-full"
                  variant="outline"
                  @click="togglePublic(conversion)">
                  <LinkIcon aria-hidden="true" />
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
                  <StopCircle aria-hidden="true" />
                  Konvertierung abbrechen
                </Button>
                <p class="text-muted-foreground text-sm">
                  Öffentliche Links können direkt beim Upload auf pr0gramm
                  eingegeben werden.
                  <a
                    target="_blank"
                    rel="noopener"
                    href="https://pr0gramm.com/upload"
                    class="text-primary underline underline-offset-4"
                    >Direkt zum Upload</a
                  >
                </p>
              </div>
            </CardFooter>
          </div>
        </div>
      </Card>
      <Card v-if="allConversions.length === 0" class="text-center">
        <CardHeader>
          <CardTitle class="text-2xl"
            >Keine Konvertierungen vorhanden
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Button as-child class="mt-4">
            <Link :href="route('home')">Neue Konvertierung starten</Link>
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped></style>
