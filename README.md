# Pr0konverter

Pr0konverter soll eine einfache Webseite werden, auf der pr0gramm Nutzer Videos für das pr0gramm konvertieren können.

Ich werde YouTube DL verwenden, um Videos von X beliebigen Plattformen zu downloaden und ffmpeg, um diese in das
gewünschte Format zu konvertieren.

Damit das ganze etwas geiler aussieht und man den Fortschritt ordentlich verfolgen kann, werde ich Reverb mit Shadcn
verwenden.

## Features die ich mindestens implementieren möchte.

- [ ] Einfacher Download von Videos von X beliebigen Plattformen und diese dann komplett downloadbar machen. (Private
      Zwecke natürlich)
- [ ] Nur Sound von Videos downloaden. (Weil Schwester das "braucht")
- [ ] Download von Ziel-URLs (.mp4 URL, .webm URL, .mp3 URL, .jpg URL, .png URL, .gif URL)
- [ ] Bulk Download von YouTube Playlists (Sound & Video - Auswählbar)
- [ ] Konvertieren von Videos in das gewünschte Format.
  - [ ] MP4 (H264/AAC)
  - [ ] WebM (VP8/Vorbis/Opus)
  - [ ] GIF aus Video (Von-bis Zeit)
  - [ ] MP3
  - [ ] JPEG Frame aus Video
  - [ ] PNG Frame aus Video
- [ ] Video schneiden (Von-bis Zeit)
- [ ] Untertitel von YouTube übernehmen
- [ ] Ratio von Bildern und Videos definieren
- [ ] Megapixel von den Bildern definieren (min. 0,09 Megapixel (z.B. 300×300), max. 20,25 Megapixel (z.B. 4608×4608))
- [ ] Dateigröße von Bildern und Videos evtl. komprimierbar machen (20MB für Bilder und 200MB für Videos)
- [ ] FPS von Videos definieren (min. 1, max. 60)
- [ ] Statistik über die konvertierten Videos und Bilder
- [ ] Voreinstellungen definierbar machen (Damit ein normaler User einfach nur auf "Konvertieren" klicken muss und
      fertig)

## Features die ich gerne implementieren möchte aber nicht muss.

- [ ] System für pr0gramm Nutzer begrenzen (0Auth Login, wenn Gamb das gut findet) um Missbrauch zu verhindern.
- [ ] FFMPEG Parameter zusammenklicken (Art Pipeline an Dateien die man haben möchte)
  - Bsp.: Man möchte aus einer Rohdatei eine MP4, AVI und ein GIF von 0:12 bis 0:15 erstellen. (Das ganze dann in
    einer Queue)

## Use Case

1. User kommt auf die Seite und sieht direkt den Konverter.
2. Frage, was er machen möchte (Video oder Bild konvertieren)
3. Zieldatei beziehen (URL oder Datei)
4. Einstellungen definieren (FPS, Megapixel, Dateigröße, Konvertierung)
5. Konvertieren
6. Downloaden
7. Statistik einsehen
8. Fertig

## Umsetzung

Alles in einzelne Schritte / Jobs aufteilen, damit man dem User auch den wirklichen Fortschritt mitteilen kann.
Reverb Channel → Status Mitteilungen

Liste an erlaubten Mimes definieren

Datei Upload muss Multi-Part sein, sonst Problem mit kack header

Ich werde Pipeline Pattern verwenden, um den Zyklus abzubilden. So kann man auch einfach weitere Schritte dazu klicken.
Bspw. Ich möchte ein Video zu 60 FPS konvertieren und zuschneiden.
Also muss ich den Job zum Zuschneiden zuerst machen und dann das Video auf 60 FPS konvertieren.
Wie definiere ich, dass der Job X for Y laufen muss?
Ich bau mir eine Config an verfügbaren Operationen und gebe so die sinnvolle Reihenfolge vor.
Die verfügbaren Operationen wären aber als einzelne Models viel geiler. So kann man die Commands auch einfach erweitern.
Jedes Model muss dann dem MediaOperation Interface entsprechen, was die Pipeline dann auch erwartet.

Um einen Überblick über die Dateien am Server zu haben, werde ich jede zu verarbeitende Datei in der DB speichern.
Die Datei ist dann immer die Zieldatei, an der die Operationen nach und nach durchgeführt werden.
Der Datei speichere ich noch Daten wie Ursprung (URL oder Upload) und Anonyme IP des Users oder wirklich den Nutzer vom
pr0gramm zu.

Die Dateien müssen nach der Verarbeitung sofort gelöscht werden, damit der Server nicht voll läuft.
Über Listener oder automatische Löschung nach spätestens 24 Stunden, wenn fehlgeschlagen.
Jeder Upload erstellt aber eine neue File. Also → Löschen wir besser sofort.

Begrenzung pro IP oder User auf eine Datei gleichzeitig.

Models:

- User
- File
- MediaOperation
- ConversionPreset - Voreinstellung für die Konvertierung (Ein Preset besteht aus mehreren MediaOperations)
- Conversion - aktueller Status der Konvertierung und das definierte Preset - Jedes Zielformat ist eine eigene
  Conversion
- ConversionLog = Log der Konvertierung (Nachvollziehbarkeit für Nutzer)
