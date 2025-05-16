<script setup>
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Switch } from '@/components/ui/switch';
import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/components/ui/number-field';
import { CloudUpload, RotateCcw, Trash2 } from 'lucide-vue-next';
import { useForm } from 'vee-validate';
import { Head, useForm as useInertiaForm } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

import {
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Label } from '@/components/ui/label/index.js';
import { Button } from '@/components/ui/button/index.js';
import { useDropZone } from '@vueuse/core';
import { computed, ref } from 'vue';
import { Input } from '@/components/ui/input/index.js';
import { toast } from 'vue-sonner';

const dropZoneRef = ref();

const onDrop = (inputFile) => {
  if (inputFile.length === 0) {
    return;
  }

  form.setValues({
    file: inputFile[0],
  });
};

const sizeOfFile = (file) => {
  return file.size / 1024 / 1024;
};

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop,
  multiple: false,
  preventDefaultForUnhandled: false,
});

const formSchema = toTypedSchema(
  z.object({
    file: z.any().optional(),
    url: z.string().url('Keine valide URL').nullish().optional().default(null),
    // keepResolution: z.boolean().default(false),
    audio: z.boolean().default(true),
    audioQuality: z
      .number()
      .min(0.01)
      .max(1.0, 'Die Zahl muss zwischen 1 und 100 liegen.')
      .nonnegative()
      .default(1.0),
    trimStart: z.string().nullish(),
    trimEnd: z.string().nullish(),
    segments: z.array().optional(), // Zod Form sucks
    maxSize: z.number().min(1).max(500).default(200),
    autoCrop: z.boolean().default(false),
    watermark: z.boolean().default(false),
    audio_only: z.boolean().default(false),
  })
);

const form = useForm({
  validationSchema: formSchema,
  name: 'converterSettings',
  validateOnMount: true,
});

const inertiaForm = useInertiaForm({
  file: null,
  url: null,
  audio: null,
  audioQuality: null,
  trimStart: null,
  trimEnd: null,
  segments: [],
  maxSize: null,
  autoCrop: null,
  watermark: null,
  audio_only: false,
});

const onSubmit = form.handleSubmit(async (values) => {
  if (inertiaForm.processing) {
    return;
  }

  inertiaForm.file = values.file;
  inertiaForm.url = values.url;
  //inertiaForm.segments
  inertiaForm.audio = values.audio;
  inertiaForm.audioQuality = values.audioQuality;
  inertiaForm.trimStart = values.trimStart;
  inertiaForm.trimEnd = values.trimEnd;
  inertiaForm.maxSize = values.maxSize;
  inertiaForm.autoCrop = values.autoCrop;
  inertiaForm.watermark = values.watermark;
  inertiaForm.audio_only = values.audio_only;

  if (!values.file && !values.url) {
    form.setErrors({
      file: 'Bitte wähle eine Datei aus oder gib eine URL ein',
      url: 'Bitte wähle eine Datei aus oder gib eine URL ein',
    });
    toast.error('Fehlende Datei', {
      description: 'Lade eine Datei hoch oder gib eine URL ein',
    });

    return;
  }

  let submitPromise = () =>
    new Promise((resolve, reject) => {
      // eslint-disable-next-line no-undef
      inertiaForm.post(route('converter.start'), {
        preserverState: true,
        preserveScroll: true,
        onSuccess: (data) => {
          resolve(data);
        },
        onError: (error) => {
          reject(error);
        },
      });
    });

  toast.promise(submitPromise, {
    loading: computed(() => {
      if (inertiaForm.file && inertiaForm.processing) {
        return (
          'Datei wird hochgeladen (' +
          inertiaForm.progress?.percentage +
          '%) und Konvertierung gestartet'
        );
      }
      return 'Konvertierung wird gestartet';
    }),
    success: 'Konvertierung erfolgreich gestartet',
    error: 'Fehler beim Starten der Konvertierung',
  });
});

const resetAudioQuality = () => {
  form.setValues({
    audioQuality: 1.0,
  });
};

const removeFile = () => {
  form.resetField('file');
};
</script>

<template>
  <Head>
    <title>Pr0verter - Converter für das pr0gramm</title>
    <meta
      name="description"
      content="Der pr0verter ist ein Converter für das pr0gramm. Hier kannst Videos konvertieren." />
  </Head>
  <form class="grid w-full items-start gap-6" @submit.prevent="onSubmit">
    <h1 class="scroll-m-20 text-4xl font-extrabold tracking-tight lg:text-5xl">
      Konvertierung
    </h1>
    <fieldset class="grid gap-6 rounded-lg border p-4">
      <legend class="-ml-1 px-1 text-sm font-medium">Datei auswählen</legend>
      <Tabs class="w-full" default-value="upload">
        <TabsList class="w-full">
          <TabsTrigger class="w-full" value="upload">
            Datei hochladen
          </TabsTrigger>
          <TabsTrigger class="download-tab-trigger w-full" value="download">
            Download</TabsTrigger
          >
        </TabsList>
        <TabsContent value="upload">
          <FormField v-slot="{ handleChange, handleBlur }" name="file">
            <FormItem
              ref="dropZoneRef"
              :class="{
                'border-dashed': !isOverDropZone,
                'border-primary': isOverDropZone,
                'justify-center': !form.values.file,
              }"
              class="relative flex h-60 shrink-0 items-center rounded-md border">
              <label
                :class="{
                  'absolute inset-0': !form.values.file,
                  hidden: form.values.file,
                }"
                class="h-full w-full cursor-pointer rounded-md"
                for="file-input"></label>
              <div
                v-show="!form.values.file"
                class="mx-auto flex max-w-[420px] flex-col items-center justify-center text-center">
                <CloudUpload class="size-8" />
                <h3 class="mt-4 text-lg font-semibold">
                  {{
                    isOverDropZone
                      ? 'Lass die Maus los'
                      : 'Datei hochladen oder ablegen'
                  }}
                </h3>
                <p class="mb-4 mt-2 text-sm text-muted-foreground">
                  Datei hier hineinziehen oder anklicken um Datei
                  hochzuladen.<br />
                  Upload beginnt erst beim Starten der Konvertierung!
                </p>
              </div>
              <div
                v-if="form.values.file"
                class="m-4 flex w-full flex-col justify-between gap-4 self-start rounded-lg border p-4 lg:flex-row lg:items-center">
                <div class="space-y-0.5">
                  <FormLabel class="text-base">
                    Datei für den Upload
                  </FormLabel>
                  <FormDescription>
                    Die Datei wird beim Starten der Konvertierung hochgeladen.
                  </FormDescription>
                  <FormMessage />
                </div>
                <div>
                  <div
                    class="flex w-full flex-row items-start gap-x-4 lg:items-center">
                    {{ form.values.file.name }}
                  </div>
                  <div
                    class="flex w-full flex-row items-start gap-x-4 lg:items-center">
                    <span class="text-muted-foreground"
                      >{{ sizeOfFile(form.values.file).toFixed(2) }} MB</span
                    >
                  </div>
                </div>
                <div>
                  <Trash2 class="size-6 cursor-pointer" @click="removeFile" />
                </div>
              </div>
              <FormControl>
                <Input
                  id="file-input"
                  accept="video/*"
                  class="sr-only"
                  type="file"
                  @blur="handleBlur"
                  @change="handleChange" />
              </FormControl>
            </FormItem>
            <FormMessage class="mt-2" />
          </FormField>
        </TabsContent>
        <TabsContent value="download">
          <FormField v-slot="{ value, handleChange }" name="url">
            <div class="flex h-60 shrink-0 rounded-md border p-4">
              <div class="flex w-full flex-col">
                <div class="space-y-1">
                  <Label for="url">URL eingeben</Label>
                  <Input
                    id="url"
                    name="url"
                    :model-value="value"
                    class="w-full"
                    @update:model-value="handleChange" />
                  <p class="text-sm text-muted-foreground">
                    Kann alles sein. YouTube, einfache Datei oder sonst was
                  </p>
                </div>
              </div>
            </div>
            <FormMessage class="mt-2" />
          </FormField>
        </TabsContent>
      </Tabs>
    </fieldset>
    <fieldset class="grid gap-6 rounded-lg border p-4">
      <legend class="-ml-1 px-1 text-sm font-medium">Einstellungen</legend>
      <FormField v-slot="{ value, handleChange }" name="audio_only">
        <label class="cursor-pointer" for="audio_only">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base">Nur Audio</FormLabel>
              <FormDescription>
                Lädt nur die Audiospur des Videos als MP3-Datei herunter.<br />
                Ideal für Musik oder Podcasts ohne unnötige Videodaten.
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <Switch
                id="audio_only"
                :checked="value"
                @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="audio">
        <label class="cursor-pointer" for="audio">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Audio</FormLabel>
              <FormDescription> Audiospur beibehalten</FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <Switch
                id="audio"
                :checked="value"
                @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </label>
      </FormField>
      <FormField
        v-if="form.values.audio === true && form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="audioQuality">
        <Label for="audioQuality">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Audio Qualität</FormLabel>
              <FormDescription> Qualität der Audiospur</FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex w-full flex-row items-center gap-x-4 lg:w-auto">
                <RotateCcw
                  class="size-4 cursor-pointer text-muted-foreground"
                  @click="resetAudioQuality" />
                <NumberField
                  id="audioQuality"
                  class="w-full lg:w-auto"
                  :default-value="1.0"
                  :format-options="{
                    style: 'percent',
                  }"
                  :max="1.0"
                  :min="0.01"
                  :model-value="value"
                  :step="0.01"
                  @update:model-value="handleChange">
                  <NumberFieldContent>
                    <NumberFieldDecrement />
                    <NumberFieldInput />
                    <NumberFieldIncrement />
                  </NumberFieldContent>
                </NumberField>
              </div>
            </FormControl>
          </FormItem>
        </Label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="maxSize">
        <Label for="maxSize">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Gewünschte Maximalgröße</FormLabel>
              <FormDescription>
                Wie viel MB soll die Datei maximal haben?
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex w-full flex-row items-center gap-x-4 lg:w-auto">
                <NumberField
                  id="maxSize"
                  class="w-full"
                  :default-value="200"
                  :max="500"
                  :model-value="value"
                  :step="1"
                  @update:model-value="handleChange">
                  <NumberFieldContent>
                    <NumberFieldInput />
                  </NumberFieldContent>
                </NumberField>
              </div>
            </FormControl>
          </FormItem>
        </Label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="autoCrop">
        <label class="cursor-pointer" for="autoCrop">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base">
                Automatisches Zuschneiden
              </FormLabel>
              <FormDescription>
                Cropt deinen Unrat automatisch. Nutzt Bewegungsvektoren des
                Decoders um den Videobereich zu erkennen.<br />
                Dauert einen Moment länger und ist nicht immer perfekt.
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <Switch
                id="autoCrop"
                :checked="value"
                @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="watermark">
        <label class="cursor-pointer" for="watermark">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Wasserzeichen</FormLabel>
              <FormDescription>
                Fügt dem Video ein pr0gramm Wasserzeichen hinzu.<br />
                50 Pixel in der unteren rechten Ecke.
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <Switch
                id="watermark"
                :checked="value"
                @update:checked="handleChange" />
            </FormControl>
          </FormItem>
        </label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="trimStart">
        <Label for="trimStart">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Startzeitpunkt</FormLabel>
              <FormDescription>
                Leer lassen, um von Anfang an zu konvertieren.<br />
                Angabe in Sekunden oder Doppelpunktschreibweise (HH:MM:SS).<br />
                Bsp.: 111 ≙ 1:51 ≙ 0:01:51
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex w-full flex-row items-center gap-x-4 lg:w-auto">
                <Input
                  id="trimStart"
                  class="w-full"
                  :disabled="inertiaForm.segments.length > 0"
                  :model-value="value"
                  @update:model-value="handleChange" />
              </div>
            </FormControl>
          </FormItem>
        </Label>
      </FormField>
      <FormField
        v-if="form.values.audio_only === false"
        v-slot="{ value, handleChange }"
        name="trimEnd">
        <Label for="trimEnd">
          <FormItem
            class="flex w-full flex-col items-start justify-between gap-4 rounded-lg border p-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Endzeitpunkt</FormLabel>
              <FormDescription>
                Leer lassen, um bis zum Ende zu konvertieren.<br />
                Angabe in Sekunden oder Doppelpunktschreibweise (HH:MM:SS).<br />
                Bsp.: 111 ≙ 1:51 ≙ 0:01:51
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex w-full flex-row items-center gap-x-4 lg:w-auto">
                <Input
                  id="trimEnd"
                  class="w-full"
                  :disabled="inertiaForm.segments.length > 0"
                  :model-value="value"
                  @update:model-value="handleChange" />
              </div>
            </FormControl>
          </FormItem>
        </Label>
      </FormField>
      <FormField v-if="form.values.audio_only === false" name="segments">
        <FormItem
          class="flex flex-col items-start justify-between space-y-4 rounded-lg border p-4">
          <div
            class="flex w-full flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
            <div class="space-y-0.5">
              <FormLabel class="text-base">Video-Segmente</FormLabel>
              <FormDescription>
                Definiere Abschnitte des Videos, die du behalten möchtest.
                Aktuell noch in Entwicklung und noch nicht wirklich stabil.
              </FormDescription>
            </div>
            <Button
              class="w-full lg:w-auto"
              type="button"
              variant="outline"
              @click="inertiaForm.segments.push({ start: 0, duration: 0 })">
              Segment hinzufügen
            </Button>
          </div>

          <div
            v-for="(segment, index) in inertiaForm.segments"
            :key="index"
            class="mb-4 w-full rounded-lg border p-4">
            <div
              class="flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
              <div class="text-base font-medium">Segment {{ index + 1 }}</div>
              <Button
                class="w-full lg:w-auto"
                type="button"
                variant="destructive"
                size="sm"
                @click="inertiaForm.segments.splice(index, 1)">
                Entfernen
              </Button>
            </div>

            <div class="mt-4 grid w-full grid-cols-1 gap-4 md:grid-cols-2">
              <div class="flex flex-col gap-2">
                <Label :for="`segment-${index}-start`">Start (Sekunden)</Label>
                <NumberField
                  :id="`segment-${index}-start`"
                  :model-value="segment.start"
                  :min="0"
                  :step="1"
                  @update:model-value="
                    (val) => (inertiaForm.segments[index].start = val)
                  ">
                  <NumberFieldContent>
                    <NumberFieldInput />
                  </NumberFieldContent>
                </NumberField>
              </div>

              <div class="flex flex-col gap-2">
                <Label :for="`segment-${index}-duration`"
                  >Dauer (Sekunden)</Label
                >
                <NumberField
                  :id="`segment-${index}-duration`"
                  :model-value="segment.duration"
                  :min="0.1"
                  :step="0.1"
                  @update:model-value="
                    (val) => (inertiaForm.segments[index].duration = val)
                  ">
                  <NumberFieldContent>
                    <NumberFieldInput />
                  </NumberFieldContent>
                </NumberField>
              </div>
            </div>
          </div>
        </FormItem>
      </FormField>
      <p class="text-sm text-muted-foreground">
        <a
          target="_blank"
          href="https://pr0gramm.com/inbox/messages/PimmelmannJones"
          >Alle Daten werden zum Ende der Session nach 2 Stunden vom Server
          gelöscht. Metadaten werden anonymisiert für die Statistik gespeichert.
          Bei Problemen oder Fragen schreib mir (PimmelmannJones) auf pr0gramm.
        </a>
      </p>
    </fieldset>
    <Button id="startConversionButton" type="submit">Konvertieren</Button>
  </form>
</template>

<style scoped></style>
