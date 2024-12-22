<script setup>
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu'
import {Toaster} from '@/components/ui/sonner'
import {toast} from "vue-sonner";
import {Link, usePage} from "@inertiajs/vue3";
import {onBeforeUnmount, onMounted} from "vue";

const props = usePage().props
const sessionId = props.session.id

onMounted(() => {
    Echo.channel(`session.${sessionId}`)
        .listen('FileUploadFailed', (event) => {
            toast.error('Datei konnte nicht hochgeladen werden')
        })
        .listen('FileUploadSuccessful', (event) => {
            toast.success('Datei erfolgreich hochgeladen')
        })
        .listen('PreviousFilesDeleted', (event) => {
            toast.info('Zuvor bestehende Dateien wurden gelöscht')
        })
        .listen('ConversionFinished', (event) => {
            toast.success('Konvertierung erfolgreich abgeschlossen')
        })
        .listen('ConversionFailed', (event) => {
            toast.error('Konvertierung fehlgeschlagen')
        })
        .listen('ConversionProgressEvent', (event) => {
            toast.loading('Konvertierung Fortschritt: ' + event.percentage + '%')
        })
})

onBeforeUnmount(() => {
    Echo.leaveChannel(`session.${sessionId}`)
})

</script>

<template>
    <header class="max-w-4xl mx-auto px-4">
        <NavigationMenu class="flex items-center justify-between gap-x-4 max-w-4xl py-4">
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
    <main class="max-w-4xl mx-auto py-6 px-4">
        <slot/>
    </main>
    <footer class="max-w-4xl mx-auto px-4">

    </footer>
    <Toaster richColors/>
</template>

<style scoped>

</style>
