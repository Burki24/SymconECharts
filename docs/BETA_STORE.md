# Erste Beta: vorbereitete Store-Unterlagen

Stand: 29.10.2026. Die folgenden Inhalte sind zur manuellen Übernahme in den
Symcon-Entwicklerbereich vorbereitet. Es wurde keine Store-Vorlage gespeichert
und keine Version eingereicht. Der Eigentümer bestätigt, dass noch kein Eintrag
existiert, und hat Bundle-ID und Deutsch als erste Store-Sprache festgelegt.

## Einreichungsdaten

| Feld | Vorbereiteter Inhalt |
| --- | --- |
| Bundle-ID | `de.burki24.symconecharts` |
| Kanal | Beta |
| Vorbereiteter Versionsstand | `1.350`, geplantes Veröffentlichungsdatum `29.10.2026`, vom Eigentümer bestätigt. Noch keine Veröffentlichung. |
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

Vorbereiteter Beta-Stand: Version 1.350 vom 29.10.2026.
Mindestversion Symcon 9.0; praktisch geprüft mit Symcon 9.1 und PHP 8.5.
Touchgeräte, weitere Plattformvarianten und längere Lastläufe sind nicht
vollständig abgenommen. Die jeweiligen Modul-READMEs beschreiben Datenmodelle,
Voraussetzungen und Nutzungsgrenzen.

StateTimeline weist widersprüchliche Zustände am selben Sekundenzeitstempel
kontrolliert ab. Betroffene Quelldaten müssen geprüft werden; es findet keine
automatische Archivbereinigung statt.
```

## Einordnung der verbleibenden Hinweise

| Hinweis | Stand für die Beta |
| --- | --- |
| StateTimeline-Archivkonflikt | Bestätigter Konflikt vorhandener Quelldaten, kein allgemeiner Beta-Blocker und kein nachgewiesener Rendererfehler. Eindeutige Zustandsarchive sind geprüft. Reale Archivdaten bleiben unverändert. Das Verhalten ist in der Modul-README und oben in der Versionsinformation beschrieben. |
| Weitere Testvarianten | Echte Touchgeräte, längere Last-/Speicherläufe, weitere Plattform-/Installationsvarianten und horizontale Mausradfälle ohne Wirkungsnachweis bleiben als Testlücken sichtbar. Daraus folgt keine neue Funktionsentwicklung und keine Behauptung, diese Varianten seien geprüft. Kachel, IPSView und die bereits bestätigte WoIst-/Helper-Mausradprüfung bleiben abgenommen. |
| README-Badges | Alle fünf Pflicht-Badges sind bereits vorhanden: Symcon/PHPModul, Mindestversion 9.0, tatsächliche PolyForm-Lizenz sowie dynamische Style-/Test-Badges für dieses Repository und `dev`. |
| Changelog | Sämtliche bisherigen Einträge sind verlustfrei der vorbereiteten Beta `1.350` vom `29.10.2026` zugeordnet und unter `Neu`/`Fixes` zusammengefasst. Der leere Abschnitt `Unveröffentlicht` bleibt für spätere Änderungen bestehen. |

Die [gemeinsame Beta-Abnahme](BETA_ABNAHME-2026-10-28.md) und die
[Systemprüfung](SYSTEMPRUEFUNG-2026-10-26.md) bleiben die Nachweise ihrer
jeweiligen Testebenen. Die redaktionelle Release-Vorbereitung ist kein neuer
Geräte-, Last- oder Produktivtest. Bekannte Grenzen bleiben erhalten.

## Freigabe-Commit und Versionsbindung

Der Stand `fa06401` enthält die Gateway-Hookkorrektur und den Bericht der
gemeinsamen Abnahme. Die jetzige Release-Dokumentation ergänzt diesen Stand.
Der Changelog ist mit ausdrücklicher Eigentümerbestätigung für Version `1.350`
und den `29.10.2026` vorbereitet. Er bezeichnet eine geplante Veröffentlichung,
keine bereits erfolgte Store-Einreichung.

Der endgültige Commit entsteht erst nach dem manuellen Commit/Push durch den
Eigentümer und dem regulären Metadatenworkflow. Deshalb werden hier weder ein
noch nicht existierender SHA noch eine künftige Versionsnummer erfunden.
`library.json` wird in dieser Vorbereitung nicht manuell überschrieben.
Ein normaler Push auf `dev` kann die Version durch den Metadatenworkflow erhöhen.
Dann müssen tatsächliche Metadaten, Changelog-Kopf und Store-Versionsinformation
vor der Einreichung erneut auf denselben bestätigten Release-Stand gebracht
werden. Die vorbereitete `1.350` darf nicht trotz abweichender Metadaten als
tatsächliche Release-Version übernommen werden.

Vor der Einreichung gehört genau dieser abschließende Stand in die Store-
Auswahl: vollständiger SHA, zugehörige Version aus `library.json` und grüne
Style-/Testläufe desselben SHA. Bei einem weiteren Commit gelten dessen alte
Prüfergebnisse nicht automatisch als Freigabe für den neuen Stand.

Die ausstehenden Eigentümerschritte sind:

1. Das gemeinsame Änderungspaket manuell committen und pushen; den vorgesehenen
   Release-Zweig gemäß Projektworkflow verwenden.
2. Den Metadatenlauf abwarten und den tatsächlichen abschließenden SHA, seine
   Version und seine CI-Ergebnisse prüfen.
3. Die vorbereitete Changelog-Überschrift `29.10.2026 - Version 1.350 (Beta)`
   und den Store-Text mit genau diesen Metadaten abgleichen. Bei geändertem
   Veröffentlichungsdatum oder einer erhöhten Version beide Texte vor der
   Einreichung passend berichtigen; ein weiterer Commit/Metadatenlauf muss
   wiederum berücksichtigt werden. Neue fachliche Änderungen gehören unter
   `Unveröffentlicht`, bis ihr Release ausdrücklich festgelegt wird.
4. Im Entwicklerbereich Bundle-ID, Beta-Kanal, Commit, Deutsch, Name,
   Beschreibung, Versionsinformation, Dokumentationslink und die tatsächlich
   angebotene passende Kategorie abgleichen. Keine Stable-Freigabe behaupten.
5. Die vollständige Vorlage kontrollieren. **Einreichen** veröffentlicht Beta
   unmittelbar und bleibt eine ausdrückliche Eigentümeraktion.

H02 ist damit inhaltlich vorbereitet. Die Kontoabnahme, konkrete Kategorien-ID
und Bindung an den erst noch entstehenden Freigabe-Commit sind ausdrücklich
offene formale Schritte und werden nicht als bereits erledigt dargestellt.

Offizielle Grundlage: [Symcon: Einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/).
