# EChartsGaugeWind

Die Windrose zeigt, **aus welcher Richtung der Wind kommt**. Standardmäßig liegt
Norden oben und der Zeiger bewegt sich. Du kannst auch den Zeiger oben fixieren
und die Windrose darunter drehen. Eine zweite Variable ergänzt die Windgeschwindigkeit.

## Einrichtung

1. Füge eine Instanz **EChartsGaugeWind** hinzu und wähle Dein vorhandenes
   **EChartsGateway**. [Voraussetzungen und Installation](../README.md#installation-und-erstes-diagramm)
2. Wähle unter **Windrichtung (Grad)** die numerische Richtungsvariable Deiner
   Wetterstation. Bei 360° steht der Zeiger wieder auf Nord.
3. Wähle bei Bedarf unter **Windgeschwindigkeit (optional)** eine zweite
   numerische Variable. Trage deren Einheit ein, beispielsweise `km/h` oder
   `m/s`. Die Zahl wird in der gewählten Genauigkeit angezeigt. Eine Quelle mit
   dem Wert 5 und der Einheit `m/s` erscheint als 5 m/s.
4. Öffne **Kacheldesigner → Kompass** und aktiviere **Windrose drehen (Zeiger oben fixieren)**.
   Bei Wind aus Südost (135°) steht jetzt **SO** oben unter dem Zeiger. Die
   Ausrichtung folgt allein der Richtungsvariable, auch bei 0 km/h.
5. Mit **Windrotor anzeigen** kannst Du zusätzlich die Geschwindigkeit als
   Bewegung darstellen. Dieser kleine Rotor dreht sich bei stärkerem Wind
   schneller und steht bei Windstille.

## Windrose auswählen

Unter **Kacheldesigner → Kompass → Windrose** kannst Du eines von 37 mitgelieferten
Motiven auswählen. Die Liste enthält 12 Farbvarianten, 8 historische Windrosen
und 12 klassische Motive, darunter Kupferstich, Admiralskompass und geschnitzte
Eiche. Hinzu kommen 5 einfarbige Windrosen mit transparentem Grund. Die Vorschau
zeigt Deine Auswahl sofort. **Übernehmen** speichert sie.
Bei festem Zeiger dreht sich auch das gewählte Motiv mit der Windrichtung.
Bei den fünf transparenten Motiven steht die Himmelsrichtung unter dem Zeiger
jeweils aufrecht.

Im **IPSView-Designer** findest Du dieselbe Auswahl, sobald Du
**Kacheldesign verwenden** ausschaltest.

Die Einträge unter **Transparent** lassen die Farbe durch die freien Flächen
der Windrose scheinen. Stelle **Diagrammhintergrund** im Abschnitt **Kompass**
neben dem **ECharts-Theme** ein.
Die dunkle Lilienrose, der Nautische Ring und die Graduierte Rose passen zu
hellen Hintergründen. Der helle Windstern und das helle Kompasssiegel sind für
dunkle Hintergründe gedacht.

## Eigene SVG-Windrose

Wähle unter **Windrose** den Eintrag **Automatisch (eigene SVG oder Standard)**
und lade im Feld **Eigene Windrose (SVG)** Deine Datei. Bei leerem Dateifeld
erscheint die eingebaute Windrose. Mit **Standardwindrose** kannst Du jederzeit
zum eingebauten Zifferblatt wechseln und die hochgeladene Datei für später behalten.

Lege Norden in der Datei oben und den Drehpunkt in die Mitte der `viewBox`.
Ein quadratisches Motiv nutzt den Platz am besten. Rechteckige Motive werden
zentriert und proportional eingepasst. Zeichne die gewünschten Himmelsrichtungen
direkt ins SVG. Farben, Beschriftung und Teilstriche kommen dann aus der Datei.
Zeiger, Nabe und optionaler Windrotor liegen darüber.

Mit **Windrose drehen (Zeiger oben fixieren)** dreht sich das gesamte Motiv
einschließlich seiner Beschriftung. Bei 90° wandert das **O** unter den oben
stehenden Zeiger. Schaltest Du die Option aus, bleibt das Bild mit Norden oben.
Für eine andere Datei in IPSView deaktiviere dort **Kacheldesign verwenden**
und wähle im Abschnitt **Kompass** die zweite Datei.

Verwende ein eigenständiges SVG mit `viewBox` und höchstens 256 KiB.
Unterstützt werden unter anderem Pfade, einfache Formen, Text,
Farbverläufe, lokale Ausschnittmasken (`clipPath`) und einfache Schlagschatten.
Schriften werden auf dem Anzeigegerät aufgelöst. Für ein festes Schriftbild
kannst Du die Beschriftung im Zeichenprogramm in Pfade umwandeln.
Externe Ressourcen, Skripte und eingebettete Bilder werden beim Import abgewiesen.

## Kachel und IPSView

IPSView übernimmt standardmäßig die Ausrichtung der Kachel. Für eine eigene
Ausrichtung schalte im IPSView-Designer **Kacheldesign verwenden** aus und
wähle dort unter **Kompass** die gewünschte Einstellung. Mit dem Kopierknopf
kannst Du die aktuellen Kachelfelder als Ausgangspunkt verwenden. Die
IPSView-Vorschau reagiert sofort. **Übernehmen** speichert Deine Auswahl. Beide Vorschauen
zeigen ein Standbild der gewählten Quellen. Bei reduzierter Bewegung zeigt
die Windrose jede neue Richtung sofort an und der zusätzliche Rotor ruht.

Bei aktivierter IPSView-Ausgabe bindest Du die WebContent-Stringvariable
`IPSViewWind` als HTML-Widget ein.
[Felder und Standardwerte](../docs/KONFIGURATION.md#echartsgaugewind)

## Skriptzugriff

`ECWI_GetWindData(int $InstanzID): string` liefert die aktuellen Winddaten
als JSON. Verwende die ID der Wind-Gauge-Instanz.

## Lizenz

Es gelten die [Projektlizenz](../LICENSE) und die
[Lizenzhinweise zu Fremdkomponenten](../THIRD_PARTY_NOTICES.md).
