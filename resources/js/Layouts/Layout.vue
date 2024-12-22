<script setup>
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { Link, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

const props = usePage().props;
const sessionId = props.session.id;

onMounted(() => {
  // eslint-disable-next-line no-undef
  Echo.channel(`session.${sessionId}`)
    .listen('FileUploadFailed', () => {
      toast.error('Datei konnte nicht hochgeladen werden');
    })
    .listen('FileUploadSuccessful', () => {
      toast.success('Datei erfolgreich hochgeladen');
    })
    .listen('PreviousFilesDeleted', () => {
      toast.info('Zuvor bestehende Dateien wurden gelöscht');
    })
    .listen('ConversionFinished', () => {
      toast.success('Konvertierung erfolgreich abgeschlossen');
    })
    .listen('ConversionFailed', () => {
      toast.error('Konvertierung fehlgeschlagen');
    })
    .listen('ConversionProgressEvent', (event) => {
      toast.loading('Konvertierung Fortschritt: ' + event.percentage + '%');
    });
});

onBeforeUnmount(() => {
  // eslint-disable-next-line no-undef
  Echo.leaveChannel(`session.${sessionId}`);
});
</script>

<template>
  <header class="mx-auto max-w-4xl px-4">
    <NavigationMenu
      class="flex max-w-4xl items-center justify-between gap-x-4 py-4">
      <Link :href="route('home')">
        <NavigationMenuLink :class="navigationMenuTriggerStyle()">
          pr0konverter
        </NavigationMenuLink>
      </Link>
      <NavigationMenuList class="gap-x-4">
        <NavigationMenuItem>
          <Link :href="route('home')">
            <NavigationMenuLink :class="navigationMenuTriggerStyle()">
              Converter
            </NavigationMenuLink>
          </Link>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <Link :href="route('conversions.list')">
            <NavigationMenuLink :class="navigationMenuTriggerStyle()">
              Konvertierungen
            </NavigationMenuLink>
          </Link>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuLink :class="navigationMenuTriggerStyle()">
            FAQ
          </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem>
          <NavigationMenuLink :class="navigationMenuTriggerStyle()">
            Kontakt
          </NavigationMenuLink>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  </header>
  <main class="mx-auto max-w-4xl px-4 py-6">
    <slot />
  </main>
  <footer class="mx-auto max-w-4xl px-4"></footer>
  <Toaster richColors />
</template>

<style scoped></style>
