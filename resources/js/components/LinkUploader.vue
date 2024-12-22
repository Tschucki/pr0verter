<script setup>
import {ref} from "vue";
import {Label} from "@/components/ui/label/index.js";
import {Input} from "@/components/ui/input/index.js";

const url = ref()
</script>

<template>
    <div class="flex h-60 shrink-0 p-4 rounded-md border">
        <div class="flex flex-col w-full">
            <div class="space-y-1">
                <Label for="url">URL eingeben</Label>
                <Input id="url" v-model="url" class="w-full"/>
                <p class="text-sm text-muted-foreground">Kann alles sein. YouTube, einfache Datei oder sonst was</p>
            </div>
        </div>
    </div>
</template>
