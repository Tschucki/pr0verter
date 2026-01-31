<script setup>
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Bars3Icon } from '@heroicons/vue/24/solid/index.js';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'vue-sonner';
import { Link, router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import pr0verterLogo from '../../assets/pr0verter.png';
import { Button } from '@/components/ui/button/index.js';
import { GithubIcon } from 'lucide-vue-next';
import {
  Alert,
  AlertDescription,
  AlertTitle,
} from '@/components/ui/alert/index.js';
import 'vue-sonner/style.css';

const props = usePage().props;
const sessionId = props.session.id;
const version = ref(props.github_version || '');
const alerts = ref([]);

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
      toast.info('Konvertierung Fortschritt: ' + event.percentage + '%');
    });
});

const menuVisible = ref(false);

onBeforeUnmount(() => {
  // eslint-disable-next-line no-undef
  Echo.leaveChannel(`session.${sessionId}`);
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
      <Link :href="route('home')" class="block w-full">
        <NavigationMenuLink class="flex items-center gap-x-4 px-0 py-0">
          <img
            :src="pr0verterLogo"
            alt="pr0verter Logo"
            class="size-8 object-contain" />
          <h1 class="text-xl font-medium tracking-wide">pr0verter</h1>
        </NavigationMenuLink>
      </Link>
      <Button
        class="block md:hidden"
        variant="outline"
        @click.prevent="menuVisible = !menuVisible">
        <Bars3Icon class="block size-6"></Bars3Icon>
      </Button>
      <div class="block hidden w-full md:block">
        <NavigationMenuList
          class="w-full flex-col items-start gap-x-4 md:flex-row md:items-center">
          <NavigationMenuItem class="block w-full">
            <Link :href="route('home')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-center!',
                ]">
                Converter
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <Link :href="route('stats')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-center!',
                ]">
                Statistik
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <Link :href="route('conversions.list')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-center!',
                ]">
                Konvertierungen
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://github.com/Tschucki/pr0verter"
              :class="[
                navigationMenuTriggerStyle(),
                'w-full! justify-center!',
              ]">
              <GithubIcon />
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://discord.gg/gubnTf4mMK"
              :class="[
                navigationMenuTriggerStyle(),
                'w-full! justify-center!',
              ]">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="size-6"
                viewBox="0 0 16 16">
                <path
                  d="M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612"></path>
              </svg>
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem
            v-if="$page.props.user !== null"
            class="block w-full cursor-pointer">
            <NavigationMenuLink
              :class="[navigationMenuTriggerStyle(), 'w-full! justify-center!']"
              @click="logout">
              Logout
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </div>
    </NavigationMenu>
    <NavigationMenu
      v-if="menuVisible"
      class="flex max-w-4xl gap-4 py-1 md:items-center md:justify-between">
      <div class="block w-full md:hidden">
        <NavigationMenuList
          class="w-full flex-col items-start gap-x-4 md:flex-row md:items-center">
          <NavigationMenuItem class="block w-full">
            <Link :href="route('home')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-start!',
                ]">
                Converter
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <Link :href="route('conversions.list')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-start!',
                ]">
                Konvertierungen
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <Link :href="route('stats')" class="block w-full">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'w-full! justify-start!',
                ]">
                Statistik
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://github.com/Tschucki/pr0verter"
              :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']">
              <GithubIcon />
              &nbsp;&nbsp;GitHub
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://discord.gg/gubnTf4mMK"
              :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="size-6"
                viewBox="0 0 16 16">
                <path
                  d="M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612"></path>
              </svg>
              &nbsp;&nbsp;Discord
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem
            v-if="$page.props.user !== null"
            class="block w-full">
            <NavigationMenuLink
              :class="[navigationMenuTriggerStyle(), 'w-full! justify-start!']"
              @click="logout">
              Logout
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </div>
    </NavigationMenu>
  </header>
  <main class="mx-auto max-w-4xl px-4 py-6">
    <Alert
      v-for="alert in alerts.filter((a) => a.title && a.description)"
      class="mb-8"
      :key="index"
      :variant="alert.variant || 'info'">
      <AlertTitle>{{ alert.title }}</AlertTitle>
      <AlertDescription>
        {{ alert.description }}
        <template v-if="alert.button">
          <br />
          <br />
          <a :href="alert.button.link" target="_blank" class="w-full">
            <Button type="button" class="w-full">
              {{ alert.button.text }}
            </Button>
          </a>
        </template>
      </AlertDescription>
    </Alert>
    <slot />
    <div class="group fixed right-5 bottom-5 z-20">
      <h4
        class="group-hover:text-primary mb-2 cursor-default text-center text-4xl font-extrabold tracking-wide text-gray-200 transition-colors duration-200">
        {{ version }}
      </h4>
      <a
        target="_blank"
        href="https://pr0gramm.com/inbox/messages/PimmelmannJones"
        title="Feedback senden">
        <Button icon="heroicon-o-bug-ant">Feedback senden</Button>
      </a>
    </div>
  </main>
  <footer>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <div class="py-8 text-center">
        <div class="flex items-center justify-center gap-x-4">
          <img
            :src="pr0verterLogo"
            alt="pr0verter Logo"
            class="size-8 object-contain" />
          <h1 class="text-xl font-medium tracking-wide">pr0verter</h1>
        </div>
        <nav aria-label="quick links" class="mt-10 text-sm">
          <div class="-my-1 flex flex-wrap justify-center gap-2 lg:gap-6">
            <Link
              class="hover:text-primary inline-block rounded-lg px-2 py-1 text-sm"
              :href="route('legal-notice')"
              >Impressum
            </Link>
            <Link
              class="hover:text-primary inline-block rounded-lg px-2 py-1 text-sm"
              :href="route('privacy-policy')"
              >Datenschutz
            </Link>
          </div>
        </nav>
      </div>
      <div
        class="border-muted flex flex-col items-center border-t py-10 sm:flex-row-reverse sm:justify-between">
        <a :href="route('home')" target="_blank" class="text-sm sm:mt-0"
          >{{ new Date().getFullYear() }} - pr0verter</a
        >
      </div>
    </div>
  </footer>
  <Toaster richColors />
</template>

<style scoped></style>
