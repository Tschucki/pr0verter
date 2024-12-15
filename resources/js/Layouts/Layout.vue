<script setup>
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu'
</script>

<template>
    <header class="max-w-4xl mx-auto">
        <NavigationMenu class="flex items-center justify-between gap-x-4 max-w-4xl py-4">
            <h6>
                pr0konverter
            </h6>
            <NavigationMenuList class="gap-x-4">
                <NavigationMenuItem>
                    <NavigationMenuLink :class="navigationMenuTriggerStyle()">
                        Converter
                    </NavigationMenuLink>
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
    <main class="max-w-4xl mx-auto py-6">
        <slot/>
    </main>
    <footer class="max-w-4xl mx-auto">

    </footer>
</template>

<style scoped>

</style>
