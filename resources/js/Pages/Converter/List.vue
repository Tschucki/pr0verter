<script setup>
import {Button} from '@/components/ui/button'

import {Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,} from '@/components/ui/card'
import {cn} from '@/lib/utils.js'
import {
    Stepper,
    StepperDescription,
    StepperItem,
    StepperSeparator,
    StepperTitle,
    StepperTrigger,
} from '@/components/ui/stepper'
import {Check, Dot, Download, Loader, Loader2} from 'lucide-vue-next'
import {onBeforeUnmount, onMounted, ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    conversions: {
        type: Array,
        required: true,
    },
})

const pageProps = usePage().props
const sessionId = pageProps.session.id
const allConversions = ref(props.conversions)

const updateConversionWithProgress = (progressEvent) => {
    allConversions.value = allConversions.value.map(conversion => {
        if (conversion.id === progressEvent.conversionId) {
            conversion.progressEvent = progressEvent
        }
        return conversion
    })
}

const updateConversion = (conversion) => {
    allConversions.value = allConversions.value.map(c => {
        if (c.id === conversion.id) {
            c = {...c, ...conversion}
        }
        return c
    })
}

onMounted(() => {
    Echo.channel(`session.${sessionId}`)
        .listen('ConversionProgressEvent', (event) => {
            updateConversionWithProgress(event)
        })
        .listen('ConversionUpdated', (event) => {
            console.log(event)
            updateConversion(event.conversion)
        })
})

// add an interval that fetches the conversions every 10 seconds
/*const interval = setInterval(() => {
    axios.post(route('conversions.my'))
        .then(response => {
            console.log(response)
            response.data.forEach(conversion => {
                console.log(conversion)
                updateConversion(conversion)
            })
        })
}, 10000)*/

const downloadButtonText = (conversion) => {
    if (conversion.downloadable) {
        return 'Datei herunterladen'
    } else {
        return 'Konvertierung läuft'
    }
}

</script>

<template>
    <div class="grid w-full items-start gap-6">
        <h1 class="scroll-m-20 text-4xl font-extrabold tracking-tight lg:text-5xl">
            Meine Konvertierungen
        </h1>

        <div class="grid grid-cols-1 ">
            <Card :class="cn($attrs.class ?? '')" v-for="(conversion, idx) in allConversions" :key="idx">
                <CardHeader>
                    <CardTitle class="truncate">{{ conversion.file.filename }}</CardTitle>
                    <CardDescription>Hochgeladen {{ conversion.file.created_at_diff }}</CardDescription>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div>
                        <Stepper orientation="vertical"
                                 class="mx-auto flex w-full flex-col justify-start gap-10">
                            <StepperItem
                                v-for="step in conversion.progress.filter(step => step.visible)"
                                :key="step.order"
                                class="relative flex w-full items-start gap-6"
                                :step="step.order"
                            >
                                <StepperSeparator
                                    v-if="step !== conversion.progress.filter(s => s.visible)[conversion.progress.filter(s => s.visible).length - 1]"
                                    class="absolute left-[18px] top-[38px] block h-[105%] w-0.5 shrink-0 rounded-full bg-muted group-data-[state=completed]:bg-primary"
                                />

                                <StepperTrigger as-child>
                                    <Button
                                        :variant="step.completed || step.current_step ? 'default' : 'outline'"
                                        size="icon"
                                        :disabled="step.current_step === false"
                                        class="z-10 rounded-full shrink-0 pointer-events-none"
                                        :class="[step.completed && step.current_step && 'ring-2 ring-ring ring-offset-2 ring-offset-background']"
                                    >
                                        <Check v-if="step.completed" class="size-5"/>
                                        <Loader v-else-if="step.current_step && step.completed === false"
                                                class="size-5 animate-spin"/>
                                        <Dot v-else/>
                                    </Button>
                                </StepperTrigger>

                                <div class="flex flex-col gap-1">
                                    <StepperTitle
                                        :class="[step.current_step && 'text-primary']"
                                        class="text-sm font-semibold transition lg:text-base"
                                    >
                                        {{ step.title }}
                                    </StepperTitle>
                                    <StepperDescription
                                        :class="[step.current_step && 'text-primary']"
                                        class="sr-only text-xs text-muted-foreground transition md:not-sr-only lg:text-sm"
                                    >
                                        {{ step.description }}
                                        <strong
                                            v-if="step.status === 'processing' && conversion.progressEvent"
                                        >
                                            <br/>
                                            Fortschritt: {{ conversion.progressEvent.percentage }}%
                                        </strong>

                                    </StepperDescription>
                                </div>
                            </StepperItem>
                        </Stepper>
                    </div>
                </CardContent>
                <CardFooter>
                    <a target="_blank"
                       :href="conversion.downloadable ? route('conversions.download', conversion.id) : null"
                       :class="{
                            'cursor-not-allowed': !conversion.downloadable,
                            'w-full': true
                       }"
                    >
                        <Button class="w-full"
                                :disabled="!conversion.downloadable"
                        >
                            <Download v-if="conversion.downloadable" class="mr-2 h-4 w-4"/>
                            <Loader2 v-else class="w-4 h-4 mr-2 animate-spin" />
                            {{ conversion.downloadable ? 'Datei herunterladen' : '' }}
                        </Button>
                    </a>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>

<style scoped>

</style>
