<script setup>
import FileUploader from "@/components/FileUploader.vue";
import {Tabs, TabsContent, TabsList, TabsTrigger} from '@/components/ui/tabs'
import LinkUploader from "@/components/LinkUploader.vue";
</script>

<template>
    <form class="grid w-full items-start gap-6">
        <fieldset class="grid gap-6 rounded-lg border p-4">
            <legend class="-ml-1 px-1 text-sm font-medium">
                Datei auswählen
            </legend>
            <Tabs default-value="upload" class="w-full">
                <TabsList class="w-full">
                    <TabsTrigger value="upload" class="w-full">
                        Datei hochladen
                    </TabsTrigger>
                    <TabsTrigger value="download" class="w-full">
                        Download
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="upload">
                    <FileUploader/>
                </TabsContent>
                <TabsContent value="download">
                    <LinkUploader/>
                </TabsContent>
            </Tabs>

        </fieldset>
    </form>
</template>

<style scoped>

</style>
