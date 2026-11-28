# 0118 – Windrose für aktuelle Windmesswerte

## Entscheidung

Eine eigene Geräteinstanz `EChartsGaugeWind` mit dem Präfix `ECWI` bezieht
die aktuelle Windrichtung und optional die Windgeschwindigkeit über das
vorhandene Gateway. Die Richtung ist ein zyklischer Winkel: 0° liegt im
Norden, 90° im Osten. Der Zeiger weist in die Richtung, aus der der Wind
kommt. Messwerte außerhalb einer Umdrehung werden auf den Kreis abgebildet.
Die Geschwindigkeit ist ein separater nichtnegativer Messwert. Die Einheit
beschriftet ihren Originalwert. Eine fehlende Quelle bleibt von gemessener
Windstille unterscheidbar.

Die Windrose benötigt acht Himmelsrichtungen, einen geschlossenen
360°-Maßstab und eine unabhängige Animation der Windgeschwindigkeit. Sie
erhält deshalb einen eigenen Renderer und nutzt dieselbe lokal ausgelieferte
Gauge-Laufzeit wie die bestehenden Instrumente. Das Rotorblatt verwendet die
ECharts-Grafikkomponente mit einer wiederholten Drehung. Die Drehzahl dient
als qualitative Anzeige. Sie erzeugt keine Messwerte oder Symcon-Timer.

Kachel und IPSView nutzen dasselbe Datenmodell und denselben Renderer.
Beide Designer verwenden die vorhandenen Theme-, Farb- und Vorschaukonventionen.
Die Designoption `RotateCompass` belässt standardmäßig Norden oben. Aktiviert
dreht sie Skala und Beschriftungen entsprechend der gemeldeten Richtung unter
den oben fixierten Zeiger. Die Geschwindigkeit kann dabei null sein oder fehlen.
Für IPSView gilt die übliche Designvererbung mit eigenem `IPSViewRotateCompass`.
Die Formularvorschauen zeigen die Windrose als Standbild. Bei
deaktivierter Animation oder reduzierter Bewegung folgt die Windrose der
gemeldeten Richtung unmittelbar und der Geschwindigkeitsrotor ruht.

Die optionalen String-Properties `CompassSvg` und `IPSViewCompassSvg`
verwenden dieselbe Formularvererbung und Entwurfskopie wie die Farben.
Leere Werte ergeben die native Skala. Das gemeinsame SVG-Bildimportmodul
validiert eigene Windrosen mit einem eigenen statischen Profil für Text,
lokale Ausschnitte und einfache Schlagschatten. Für dieses Profil gelten
256 KiB, maximal 576 Elemente und 32 Verschachtelungsebenen ab der Wurzel.
Die Kupferstichvariante benötigt 556 Elemente. Der Canvas-Renderer und
beide SVG-Vorschauen zentrieren das Bild unter Zeiger und Nabe. Bei festem
Zeiger nutzt das Bild denselben interpolierten Richtungswinkel wie die Skala.

`CompassPreset` und `IPSViewCompassPreset` wählen je Designer zwischen
`auto` (bestehender Dateiupload oder native Skala), `standard` (native Skala)
und einer stabilen Motiv-ID aus dem mitgelieferten Katalog. Nur das gewählte
Motiv durchläuft denselben Import- und Renderpfad wie ein eigener Upload.
Ein Motivwechsel erhält den gespeicherten Upload. Neue Properties verwenden
`auto`, sodass vorhandene Instanzen ihre bisherige Darstellung behalten.
