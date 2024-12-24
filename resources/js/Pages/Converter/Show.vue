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
    trimStart: z.any().nullish(),
    trimEnd: z.any().nullish(),
    maxSize: z.number().min(1).default(200),
    autoCrop: z.boolean().default(false),
    watermark: z.boolean().default(false),
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
  // keepResolution: null,
  audio: null,
  audioQuality: null,
  trimStart: null,
  trimEnd: null,
  maxSize: null,
  autoCrop: null,
  watermark: null,
});

const onSubmit = form.handleSubmit(async (values) => {
  console.log(values);
  if (inertiaForm.processing) {
    return;
  }

  inertiaForm.file = values.file;
  inertiaForm.url = values.url;
  // inertiaForm.keepResolution = values.keepResolution;
  inertiaForm.audio = values.audio;
  inertiaForm.audioQuality = values.audioQuality;
  inertiaForm.trimStart = values.trimStart;
  inertiaForm.trimEnd = values.trimEnd;
  inertiaForm.maxSize = values.maxSize;
  inertiaForm.autoCrop = values.autoCrop;
  inertiaForm.watermark = values.watermark;

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
      console.log(inertiaForm);
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
          <TabsTrigger class="w-full" value="download"> Download</TabsTrigger>
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
                  <div class="flex flex-row items-center gap-x-4">
                    {{ form.values.file.name }}
                  </div>
                  <div class="flex flex-row items-center gap-x-4">
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
      <FormField v-slot="{ value, handleChange }" name="audio">
        <label class="cursor-pointer" for="audio">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
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
        v-if="form.values.audio === true"
        v-slot="{ value, handleChange }"
        name="audioQuality">
        <Label for="audioQuality">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Audio Qualität</FormLabel>
              <FormDescription> Qualität der Audiospur</FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex flex-row items-center gap-x-4">
                <RotateCcw
                  class="size-4 cursor-pointer text-muted-foreground"
                  @click="resetAudioQuality" />
                <NumberField
                  id="audioQuality"
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
      <FormField v-slot="{ value, handleChange }" name="maxSize">
        <Label for="maxSize">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Gewünschte Maximalgröße</FormLabel>
              <FormDescription>
                Wie viel MB soll die Datei maximal haben?
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex flex-row items-center gap-x-4">
                <NumberField
                  id="maxSize"
                  :default-value="200"
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
      <FormField v-slot="{ value, handleChange }" name="autoCrop">
        <label class="cursor-pointer" for="autoCrop">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
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
      <FormField v-slot="{ value, handleChange }" name="watermark">
        <label class="cursor-pointer" for="watermark">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
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
      <FormField v-slot="{ value, handleChange }" name="trimStart">
        <Label for="trimStart">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Startzeitpunkt</FormLabel>
              <FormDescription>
                Leer lassen, um von Anfang an zu konvertieren
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex flex-row items-center gap-x-4">
                <NumberField
                  id="trimStart"
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
      <FormField v-slot="{ value, handleChange }" name="trimEnd">
        <Label for="trimEnd">
          <FormItem
            class="flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
              <FormLabel class="text-base"> Endzeitpunkt</FormLabel>
              <FormDescription>
                Leer lassen, um bis zum Ende zu konvertieren
              </FormDescription>
              <FormMessage />
            </div>
            <FormControl>
              <div class="flex flex-row items-center gap-x-4">
                <NumberField
                  id="trimEnd"
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
    </fieldset>
    <Button type="submit">Konvertieren</Button>
  </form>
</template>

<style scoped></style>
