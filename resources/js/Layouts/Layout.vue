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
                  '!w-full !justify-center',
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
                  '!w-full !justify-center',
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
                  '!w-full !justify-center',
                ]">
                Konvertierungen
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://pr0gramm.com/inbox/messages/PimmelmannJones"
              :class="[
                navigationMenuTriggerStyle(),
                '!w-full !justify-center',
              ]">
              Kontakt
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://github.com/Tschucki/pr0verter"
              :class="[
                navigationMenuTriggerStyle(),
                '!w-full !justify-center',
              ]">
              <GithubIcon />
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem
            v-if="$page.props.user !== null"
            class="block w-full cursor-pointer">
            <NavigationMenuLink
              :class="[navigationMenuTriggerStyle(), '!w-full !justify-center']"
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
                  '!w-full !justify-start',
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
                  '!w-full !justify-start',
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
                  '!w-full !justify-start',
                ]">
                Statistik
              </NavigationMenuLink>
            </Link>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://pr0gramm.com/inbox/messages/PimmelmannJones"
              :class="[navigationMenuTriggerStyle(), '!w-full !justify-start']">
              Kontakt
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem class="block w-full">
            <NavigationMenuLink
              target="_blank"
              href="https://github.com/Tschucki/pr0verter"
              :class="[navigationMenuTriggerStyle(), '!w-full !justify-start']">
              <GithubIcon />
              - GitHub
            </NavigationMenuLink>
          </NavigationMenuItem>
          <NavigationMenuItem
            v-if="$page.props.user !== null"
            class="block w-full">
            <NavigationMenuLink
              :class="[navigationMenuTriggerStyle(), '!w-full !justify-start']"
              @click="logout">
              Logout
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </div>
    </NavigationMenu>
  </header>
  <main class="mx-auto max-w-4xl px-4 py-6">
    <slot />
    <div class="group fixed bottom-5 right-5 z-20">
      <h4
        class="mb-2 cursor-default text-center text-4xl font-extrabold tracking-wide text-gray-200 transition-colors duration-200 group-hover:text-primary">
        BETA
      </h4>
      <a
        target="_blank"
        href="https://pr0gramm.com/inbox/messages/PimmelmannJones?beta=1"
        title="Feedback zum Beta-Test senden">
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
              class="inline-block rounded-lg px-2 py-1 text-sm hover:text-primary"
              :href="route('legal-notice')"
              >Impressum
            </Link>
            <Link
              class="inline-block rounded-lg px-2 py-1 text-sm hover:text-primary"
              :href="route('privacy-policy')"
              >Datenschutz
            </Link>
          </div>
        </nav>
      </div>
      <div
        class="flex flex-col items-center border-t border-muted py-10 sm:flex-row-reverse sm:justify-between">
        <a :href="route('home')" target="_blank" class="text-sm sm:mt-0"
          >{{ new Date().getFullYear() }} - pr0verter</a
        >
      </div>
    </div>
  </footer>
  <Toaster richColors />
</template>

<style scoped></style>
