# Erste Beta: vorbereitete Store-Unterlagen

Stand: 28.10.2026. Die folgenden Inhalte sind zur manuellen Übernahme in den
Symcon-Entwicklerbereich vorbereitet. Es wurde keine Store-Vorlage gespeichert
und keine Version eingereicht. Der Eigentümer bestätigt, dass noch kein Eintrag
existiert, und hat Bundle-ID und Deutsch als erste Store-Sprache festgelegt.

## Einreichungsdaten

| Feld | Vorbereiteter Inhalt |
| --- | --- |
| Bundle-ID | `de.burki24.symconecharts` |
| Kanal | Beta |
| Git-URL | `https://github.com/Burki24/SymconECharts` |
| Sprache | Deutsch (`de`) |
| Name | SymconECharts |
| Dokumentation | `https://github.com/Burki24/SymconECharts/blob/dev/README.md` |
| Kategorie | Visualisierung; die entsprechende angebotene Kategorie im Store-Dialog auswählen. Die konkrete Kategorien-ID wurde nicht im Konto geprüft. |
| Mindestversion | Symcon 9.0 laut `library.json`; reale Abnahme auf Symcon 9.1/PHP 8.5. |
| Lizenz | PolyForm Noncommercial 1.0.0; Fremdkomponenten gemäß `THIRD_PARTY_NOTICES.md`. |
| Ausnahmen | Keine genehmigte Ausnahme beansprucht. Feste lesende Dateien für mitgelieferte Ressourcen, installierte Symcon-Icons und vorhandene Z2M-Netzwerkkarten sind dokumentiert. |

## Beschreibung

```text
SymconECharts stellt aktuelle Variablenwerte, Archivdaten und ausgewählte
Objektbeziehungen als responsive Apache-ECharts-Diagramme dar. Die Library
bietet 29 Diagrammfamilien, darunter Gauges, Zeitreihen, Balken, Kreisdiagramme,
Heatmaps, Kalender, Graphen und Zustandszeitdiagramme. Native Symcon-Kacheln
und optionale IPSView-HTML-Widgets besitzen anpassbare Designs und Vorschauen.
Historische Zeiträume, Quellenauswahl und Schutzbudgets werden je Diagramm
angeboten. Apache ECharts wird lokal mitgeliefert. Erste öffentliche Beta;
weitere Geräte-, Touch- und Langzeitprüfungen sind noch nicht vollständig.
```

## Versionsinformation für die erste Beta

```text
Erste öffentliche Beta von SymconECharts mit 29 Diagrammfamilien für aktuelle
Werte, Archive und Objektbeziehungen. Die Ausgabe unterstützt native Kacheln
und IPSView, getrennte Designer, Vorschauen und Live-Aktualisierungen.

Die gemeinsame Systemprüfung umfasst Neuinstanzen, vorhandene Konfigurationen,
Archiv- und Transportfälle. Mehrere Gateways verwenden einen koordinierten
HTTP-Zugang; nach dem Entfernen eines Eigentümers wird dieser automatisch
wiederhergestellt. Bestehende URLs, Kanäle, Quellen und Designs bleiben erhalten.

Mindestversion Symcon 9.0; praktisch geprüft mit Symcon 9.1 und PHP 8.5.
Touchgeräte, weitere Plattformvarianten und längere Lastläufe sind nicht
vollständig abgenommen. Die jeweiligen Modul-READMEs beschreiben Datenmodelle,
Voraussetzungen und Nutzungsgrenzen.
```

## Freigabe-Commit und Versionsbindung

Der geprüfte Ausgangscommit ist `1a881d2` mit Version `1.350`. Er enthält die
Hookkorrektur noch nicht und ist **kein freigegebener Beta-Kandidat**.

Der endgültige Commit entsteht erst nach dem manuellen Commit/Push durch den
Eigentümer und dem regulären Metadatenworkflow. Deshalb werden hier weder ein
noch nicht existierender SHA noch eine künftige Versionsnummer erfunden.
`library.json` wird in dieser Vorbereitung nicht manuell überschrieben.

Vor der Einreichung gehört genau dieser abschließende Stand in die Store-
Auswahl: vollständiger SHA, zugehörige Version aus `library.json` und grüne
Style-/Testläufe desselben SHA. Bei einem weiteren Commit gelten dessen alte
Prüfergebnisse nicht automatisch als Freigabe für den neuen Stand.

Die ausstehenden Eigentümerschritte sind:

1. Das gemeinsame Änderungspaket manuell committen und pushen; den vorgesehenen
   Release-Zweig gemäß Projektworkflow verwenden.
2. Den Metadatenlauf abwarten und den tatsächlichen abschließenden SHA, seine
   Version und seine CI-Ergebnisse prüfen.
3. Erst dann den Changelog unter Beibehaltung des tatsächlichen Inhalts mit
   `# <Datum> - Version <Version> (Beta)` diesem Stand zuordnen. Ein dadurch
   entstehender neuer Commit und Metadatenlauf muss erneut abgeglichen werden.
   Alternativ bleibt die noch nicht veröffentlichte Historie bis zum ausdrücklich
   beauftragten letzten Release-Schritt unter `Unveröffentlicht`; der Store erhält
   den vorbereiteten Text oben.
4. Im Entwicklerbereich Bundle-ID, Beta-Kanal, Commit, Deutsch, Name,
   Beschreibung, Versionsinformation, Dokumentationslink und die tatsächlich
   angebotene passende Kategorie abgleichen. Keine Stable-Freigabe behaupten.
5. Die vollständige Vorlage kontrollieren. **Einreichen** veröffentlicht Beta
   unmittelbar und bleibt eine ausdrückliche Eigentümeraktion.

H02 ist damit inhaltlich vorbereitet. Die Kontoabnahme, konkrete Kategorien-ID
und Bindung an den erst noch entstehenden Freigabe-Commit sind ausdrücklich
offene formale Schritte und werden nicht als bereits erledigt dargestellt.

Offizielle Grundlage:
https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/
