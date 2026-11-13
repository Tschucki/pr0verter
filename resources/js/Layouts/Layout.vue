<script setup>
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/solid/index.js';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import pr0verterLogo from '../../assets/pr0verter.png';
import { Button } from '@/components/ui/button/index.js';
import { GithubIcon, Sparkles, X } from 'lucide-vue-next';
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert/index.js';
import 'vue-sonner/style.css';

const page = usePage();
const sessionId = page.props.session.id;
const version = ref(page.props.github_version || '');

// Eine Liste für Desktop und Mobil, damit Reihenfolge und Ziele gleich bleiben.
const navItems = [
  { label: 'Converter', route: 'home' },
  { label: 'Konvertierungen', route: 'conversions.list' },
  { label: 'Statistik', route: 'stats' },
];
const discordPath =
  'M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612';
const externalItems = [
  { label: 'GitHub', href: 'https://github.com/Tschucki/pr0verter' },
  { label: 'Discord', href: 'https://discord.gg/gubnTf4mMK' },
];
// page.url macht die Prüfung reaktiv, der Layout-Rahmen bleibt beim
// Seitenwechsel stehen.
// eslint-disable-next-line no-undef
const isCurrent = (name) => Boolean(page.url) && route().current(name);

const ALERT_DISMISS_KEY = 'pr0verter.dismissedAlerts';
const releaseNotes = [
  {
    id: 'subtitles-thumbnails-2026-04',
    variant: 'default',
    title: 'Neu: Untertitel & Vorschaubilder',
    description:
      'Ihr könnt jetzt Untertitel direkt ins Video rendern. Natürlich nur, wenn das Video auch welche hinterlegt hat. Außerdem ziehe ich jetzt auch das Thumbnail vom Video, um es in der Übersicht darzustellen.',
  },
];
const alerts = ref([]);

const loadDismissedAlerts = () => {
  try {
    const raw = localStorage.getItem(ALERT_DISMISS_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch {
    return [];
  }
};

const dismissAlert = (id) => {
  const dismissed = loadDismissedAlerts();
  if (!dismissed.includes(id)) {
    dismissed.push(id);
    localStorage.setItem(ALERT_DISMISS_KEY, JSON.stringify(dismissed));
  }
  alerts.value = alerts.value.filter((alert) => alert.id !== id);
};

onMounted(() => {
  const dismissed = loadDismissedAlerts();
  alerts.value = releaseNotes.filter((alert) => !dismissed.includes(alert.id));

  // eslint-disable-next-line no-undef
  Echo?.channel(`session.${sessionId}`)
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
      // Ein Toast je Konvertierung, der sich aktualisiert, statt eines neuen
      // Toasts pro Prozentschritt.
      toast.info('Konvertierung Fortschritt: ' + event.percentage + '%', {
        id: `conversion-progress-${event.conversionId}`,
      });
    });
});

const menuVisible = ref(false);

// Nach einem Seitenwechsel ist das mobile Menü wieder zu.
watch(
  () => page.url,
  () => {
    menuVisible.value = false;
  }
);

onBeforeUnmount(() => {
  // eslint-disable-next-line no-undef
  Echo?.leaveChannel(`session.${sessionId}`);
});

const logout = async () => {
  // eslint-disable-next-line no-undef
  await router.post(route('auth.logout'));
};
</script>

<template>
  <header class="mx-auto max-w-4xl px-4">
    <NavigationMenu
      class="flex max-w-4xl gap-4 py-4 md:items-center md:justify-between">
      <Link
        :href="route('home')"
        class="flex w-full items-center gap-x-4 rounded-md pointer-coarse:min-h-11">
        <img :src="pr0verterLogo" alt="" class="size-8 object-contain" />
        <span class="text-xl font-medium tracking-wide">pr0verter</span>
      </Link>
      <Button
        class="shrink-0 md:hidden"
        variant="outline"
        size="icon"
        :aria-expanded="menuVisible"
        aria-controls="mobile-navigation"
        :aria-label="menuVisible ? 'Menü schließen' : 'Menü öffnen'"
        @click.prevent="menuVisible = !menuVisible">
        <XMarkIcon v-if="menuVisible" aria-hidden="true" />
        <Bars3Icon v-else aria-hidden="true" />
      </Button>
      <div class="hidden w-full md:block">
        <NavigationMenuList
          class="w-full flex-row items-center justify-end gap-x-1">
          <NavigationMenuItem v-for="item in navItems" :key="item.route">
            <NavigationMenuLink
              as-child
              :active="isCurrent(item.route)"
              :class="navigationMenuTriggerStyle()">
              <Link :href="route(item.route)">{{ item.label }}</Link>
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem v-for="item in externalItems" :key="item.href">
            <NavigationMenuLink
              target="_blank"
              rel="noopener"
              :href="item.href"
              :aria-label="item.label"
              :class="navigationMenuTriggerStyle()">
              <GithubIcon
                v-if="item.label === 'GitHub'"
                aria-hidden="true"
                class="size-5" />
              <svg
                v-else
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="currentColor"
                class="size-5"
                viewBox="0 0 16 16">
                <path :d="discordPath"></path>
              </svg>
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem v-if="$page.props.user !== null">
            <NavigationMenuLink
              as="button"
              type="button"
              :class="navigationMenuTriggerStyle()"
              @click="logout">
              Logout
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </div>
    </NavigationMenu>
    <NavigationMenu
      v-if="menuVisible"
      id="mobile-navigation"
      class="flex w-full max-w-4xl justify-start pb-2 md:hidden [&>div]:w-full">
      <NavigationMenuList class="w-full flex-col items-stretch gap-y-1">
        <NavigationMenuItem v-for="item in navItems" :key="item.route">
          <NavigationMenuLink
            as-child
            :active="isCurrent(item.route)"
            :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']">
            <Link :href="route(item.route)">{{ item.label }}</Link>
          </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem v-for="item in externalItems" :key="item.href">
          <NavigationMenuLink
            target="_blank"
            rel="noopener"
            :href="item.href"
            :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']">
            <GithubIcon
              v-if="item.label === 'GitHub'"
              aria-hidden="true"
              class="size-5" />
            <svg
              v-else
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              class="size-5"
              viewBox="0 0 16 16">
              <path :d="discordPath"></path>
            </svg>
            {{ item.label }}
          </NavigationMenuLink>
        </NavigationMenuItem>
        <NavigationMenuItem v-if="$page.props.user !== null">
          <NavigationMenuLink
            as="button"
            type="button"
            :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']"
            @click="logout">
            Logout
          </NavigationMenuLink>
        </NavigationMenuItem>
      </NavigationMenuList>
    </NavigationMenu>
  </header>
  <main class="mx-auto max-w-4xl px-4 py-6">
    <Alert
      v-for="alert in alerts.filter((a) => a.title && a.description)"
      :key="alert.id"
      class="mb-8 pr-12 pointer-coarse:pr-14"
      :variant="alert.variant || 'default'">
      <Sparkles class="size-4" aria-hidden="true" />
      <AlertTitle>{{ alert.title }}</AlertTitle>
      <AlertDescription>
        {{ alert.description }}
        <template v-if="alert.button">
          <br />
          <br />
          <Button as-child class="w-full">
            <a :href="alert.button.link" target="_blank" rel="noopener">
              {{ alert.button.text }}
            </a>
          </Button>
        </template>
      </AlertDescription>
      <Button
        :aria-label="`Hinweis „${alert.title}“ ausblenden`"
        class="text-muted-foreground hover:text-foreground absolute top-1 right-1"
        size="icon"
        variant="ghost"
        @click="dismissAlert(alert.id)">
        <X class="size-4" aria-hidden="true" />
      </Button>
    </Alert>
    <slot />
    <!-- Ab 1280 px liegt die Box neben dem Inhalt, darunter im Fluss, damit
         sie keine Bedienelemente überdeckt. -->
    <div
      class="group mt-12 flex flex-col items-center xl:fixed xl:right-5 xl:bottom-5 xl:z-20 xl:mt-0">
      <p
        class="group-hover:text-primary mb-2 cursor-default text-center text-4xl font-extrabold tracking-wide text-gray-200 transition-colors duration-200">
        {{ version }}
      </p>
      <Button as-child>
        <a
          target="_blank"
          rel="noopener"
          href="https://pr0gramm.com/inbox/messages/PimmelmannJones">
          Feedback senden
        </a>
      </Button>
    </div>
  </main>
  <footer>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <div class="py-8 text-center">
        <div class="flex items-center justify-center gap-x-4">
          <img :src="pr0verterLogo" alt="" class="size-8 object-contain" />
          <p class="text-xl font-medium tracking-wide">pr0verter</p>
        </div>
        <nav aria-label="Rechtliches" class="mt-10 text-sm">
          <div class="-my-1 flex flex-wrap justify-center gap-2 lg:gap-6">
            <Link
              class="hover:text-primary inline-flex items-center rounded-lg px-2 py-1 text-sm pointer-coarse:min-h-11"
              :href="route('legal-notice')"
              >Impressum
            </Link>
            <Link
              class="hover:text-primary inline-flex items-center rounded-lg px-2 py-1 text-sm pointer-coarse:min-h-11"
              :href="route('privacy-policy')"
              >Datenschutz
            </Link>
          </div>
        </nav>
      </div>
      <div class="border-muted flex justify-center border-t py-10">
        <p class="text-muted-foreground text-sm">
          {{ new Date().getFullYear() }} – pr0verter
        </p>
      </div>
    </div>
  </footer>
  <Toaster rich-colors theme="dark" />
</template>
