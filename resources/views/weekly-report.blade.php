<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wöchentlicher Statistikbericht</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-background text-foreground">
<div class="max-w-[1052px] mx-auto">
    <div class="py-8">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:max-w-none">
                <div class="text-center">
                    <div class="flex items-center gap-4 justify-center">
                        <img src="{{asset('images/pr0verter.png')}}" alt="pr0verter Logo" class="size-20 aspect-square">
                        <h2 class="text-4xl font-semibold tracking-tight text-balance text-white sm:text-5xl">
                            Wochenstatistik vom pr0verter</h2>
                    </div>
                    <p class="mt-6 text-sm/8 text-white/70 text-center">
                        Erstelldatum: {{$generatedAt->format('d.m.Y H:i')}} Uhr
                    </p>
                </div>
                <dl class="mt-12 grid grid-cols-1 gap-0.5 overflow-hidden rounded-2xl text-center sm:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Konvertierungen</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['total_conversions']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Beliebteste Download-URL</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['favorite_url']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Traffic</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['traffic']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Häufigstes Eingabeformat</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['most_used_input_extension']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Durchschnittliche Konvertierungszeit</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['average_conversion_time']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Beliebteste Konvertierungszeit</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['favorite_time_to_convert']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Wasserzeichen hinzugefügt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['added_watermarks']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Unrat gecroppt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['auto_copped']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Ausschnitte erstellt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['trimmed']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Audio entfernt</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['removed_audio']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Nur Audio heruntergeladen</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['audio_only']}}</dd>
                    </div>
                    <div class="flex flex-col bg-white/5 p-8">
                        <dt class="text-sm/6 font-semibold">Videos segmentiert</dt>
                        <dd class="order-first text-3xl font-semibold tracking-tight text-white">{{$statistics['segmented']}}</dd>
                    </div>
                </dl>
                <p class="mt-12 text-sm/8 text-white/70 text-center">
                    Dieser Post wurde automatisch generiert und gepostet.
                </p>
                <p class="mt-1 text-sm/8 font-semibold tracking-wide text-center text-primary">
                    pr0verter.de
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
