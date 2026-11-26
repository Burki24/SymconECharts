# EChartsGaugeWind

Die Windrose zeigt, **aus welcher Richtung der Wind kommt**. Der Zeiger weist
bei 0° nach Norden, bei 90° nach Osten, bei 180° nach Süden und bei 270° nach
Westen. Mit einer zweiten Variable kannst Du die Geschwindigkeit anzeigen und
ein kleines Blatt rotieren lassen.

## Einrichtung

1. Füge eine Instanz **EChartsGaugeWind** hinzu und wähle Dein vorhandenes
   **EChartsGateway**. [Voraussetzungen und Installation](../README.md#installation-und-erstes-diagramm)
2. Wähle unter **Windrichtung (Grad)** die numerische Richtungsvariable Deiner
   Wetterstation. Bei 360° steht der Zeiger wieder auf Nord.
3. Wähle bei Bedarf unter **Windgeschwindigkeit (optional)** eine zweite
   numerische Variable. Trage deren Einheit ein, beispielsweise `km/h` oder
   `m/s`. Die Zahl wird in der gewählten Genauigkeit angezeigt. Eine Quelle mit
   dem Wert 5 und der Einheit `m/s` erscheint als 5 m/s.
4. Aktiviere **Windrotor anzeigen**, wenn Du die Geschwindigkeit zusätzlich
   als Bewegung sehen möchtest. Bei gleichem Gerät dreht sich das Blatt mit
   steigendem Messwert schneller. Bei Windstille steht es. Die Nadel zeigt
   weiterhin die von der Wetterstation gemeldete Richtung.

## Kachel und IPSView

Für die Kachel kannst Du Gradangabe, Rotor, Animation und Farben im
Kacheldesigner wählen. Für IPSView kannst Du das Kacheldesign übernehmen oder
das Widget separat gestalten. Die Vorschau zeigt ein Standbild der gewählten
Quellen. Das Blatt dreht sich in der laufenden Anzeige bei positiver
Windgeschwindigkeit und eingeschalteter Animation. Die Einstellung für
reduzierte Bewegung auf dem Anzeigegerät wird berücksichtigt.

Bei aktivierter IPSView-Ausgabe bindest Du die WebContent-Stringvariable
`IPSViewWind` als HTML-Widget ein.
[Felder und Standardwerte](../docs/KONFIGURATION.md#echartsgaugewind)

## Skriptzugriff

`ECWI_GetWindData(int $InstanzID): string` liefert die aktuellen Winddaten
als JSON. Verwende die ID der Wind-Gauge-Instanz.

## Lizenz

Es gelten die [Projektlizenz](../LICENSE) und die
[Lizenzhinweise zu Fremdkomponenten](../THIRD_PARTY_NOTICES.md).
