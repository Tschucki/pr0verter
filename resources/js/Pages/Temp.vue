<script setup>
import pimmelmannJones from '../../assets/pimmelmannjones.svg';
import { Button } from '@/components/ui/button/index.js';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head>
    <title>Pr0verter - Converter für das pr0gramm</title>
    <meta
      name="description"
      content="Der pr0verter ist ein Converter für das pr0gramm. Hier kannst Videos konvertieren." />
  </Head>
  <p class="font-medium leading-7 tracking-wide [&:not(:first-child)]:mt-6">
    Der pr0verter hat jetzt einen neuen Besitzer.<br /><br />
    Das alte Projekt wurde leider nicht mehr weiterentwickelt. Auf Nachfrage
    wurde das Projekt nun mir übergeben. Ich werde das Projekt in den nächsten
    Tagen abschließen und auch offiziell veröffentlichen.
    <br /><br />
    Das Projekt wird Open Source sein und in ein paar Tagen auf GitHub zu finden
    sein.
    <br /><br />
    Solltet ihr Fragen und Wünsche haben, dann dürft ihr mir gerne eine
    Nachricht schreiben.
  </p>
  <a href="https://pr0gramm.com/inbox/messages/PimmelmannJones" target="_blank">
    <Button class="mt-6">Nachricht schreiben</Button>
  </a>
  <a
    href="https://pr0gramm.com/user/PimmelmannJones"
    target="_blank"
    class="mt-4 flex flex-col items-end justify-end">
    <img
      :src="pimmelmannJones"
      alt="PimmelmannJones"
      class="w-56 tracking-wide text-foreground sm:w-80" />
    <span class="mt-1">PimmelmannJones</span>
  </a>
</template>

<style scoped></style>
