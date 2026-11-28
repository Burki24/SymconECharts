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
