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
| Versionsstand | Aus `library.json` genau des im Store ausgewählten Commits übernehmen. CI-Prüfstand dieses Berichts: `1.352`; Nachweis unten. |
| Geplantes Veröffentlichungsdatum | `29.10.2026`, vom Eigentümer bestätigt. Noch keine Veröffentlichung. |
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

StateTimeline und CalendarPie erkennen schnelle Wechsel derselben Sekunde,
wenn genau ein Eintrag durch seine positive Archiv-Dauer eindeutig den
folgenden Zustand bestimmt. Wirklich mehrdeutige Fälle bleiben Fehler;
es findet keine automatische Archivbereinigung statt.
```

## Einordnung der verbleibenden Hinweise

| Hinweis | Stand für die Beta |
| --- | --- |
| StateTimeline-Archivkonflikt | Am 29.10.2026 genauer untersucht und korrigiert: neun Gruppen schneller Wechsel besitzen jeweils genau einen positiven Dauereintrag. Die frühere pauschale Einordnung als Datenfehler war zu weitgehend. Die gemeinsame Gateway-Auswertung berücksichtigt diese eindeutige Information; alle neun Fälle sind auf der Testinstallation per MCP geprüft. Archive bleiben unverändert. Echte Mehrdeutigkeit bleibt ein Fehler. [Nachweis](STATE_TIMELINE_ARCHIVPRUEFUNG-2026-10-29.md). |
| Weitere Testvarianten | Echte Touchgeräte, längere Last-/Speicherläufe, weitere Plattform-/Installationsvarianten und horizontale Mausradfälle ohne Wirkungsnachweis bleiben als Testlücken sichtbar. Daraus folgt keine neue Funktionsentwicklung und keine Behauptung, diese Varianten seien geprüft. Kachel, IPSView und die bereits bestätigte WoIst-/Helper-Mausradprüfung bleiben abgenommen. |
| README-Badges | Alle fünf Pflicht-Badges sind bereits vorhanden: Symcon/PHPModul, Mindestversion 9.0, tatsächliche PolyForm-Lizenz sowie dynamische Style-/Test-Badges für dieses Repository und `dev`. |
| Changelog | Die bisherigen Beta-Einträge sind erhalten; die neue Dauerkorrektur ist nach erfolgreicher lokaler und MCP-Prüfung in den bereits beauftragten vorbereiteten Beta-Abschnitt übernommen. Die markierte Versionsüberschrift folgt gemeinsam mit `library.json` dem Metadatenworkflow. Historische Releases bleiben unverändert. |

Die [gemeinsame Beta-Abnahme](BETA_ABNAHME-2026-10-28.md) und die
[Systemprüfung](SYSTEMPRUEFUNG-2026-10-26.md) bleiben die Nachweise ihrer
jeweiligen Testebenen. Die redaktionelle Release-Vorbereitung ist kein neuer
Geräte-, Last- oder Produktivtest. Bekannte Grenzen bleiben erhalten.

## Geprüfter Push und Versionsmetadaten

Beim damaligen CI-Abgleich am 29.10.2026 lieferten GitHub und der lokale Branch `dev` denselben HEAD:
`4b36c637f23a2f1aa89d33db4f43f4450d6069ee`. Der Metadatencommit enthält
Version `1.352`, Build `226068802` und `date=1793255814`.
Build und Datum stammen korrekt aus dem Quellcommit
`d798942879b35f1949304f6a133b5055bb1927f5`: dessen sieben führende Hexzeichen
ergeben den Build; dessen Commit-Zeit ist 29.10.2026, 07:36:54 Uhr Europe/Berlin.
Das Metadatendatum ist der Quellcommit-Zeitpunkt, kein Veröffentlichungsnachweis.

| Nachweis | Ergebnis |
| --- | --- |
| [Check Style auf 4b36c63](https://github.com/Burki24/SymconECharts/actions/runs/40830651356) | Erfolgreich, exakt auf dem Metadatencommit. |
| [Tests auf 4b36c63](https://github.com/Burki24/SymconECharts/actions/runs/40830651428) | Erfolgreich, exakt auf dem Metadatencommit. |
| [Metadatenlauf des Quellcommits d798942](https://github.com/Burki24/SymconECharts/actions/runs/40830284824) | Erfolgreich; erzeugte 4b36c63 und Version 1.352. Auch Style und Tests des Quellcommits sind erfolgreich. |
| [Metadatenlauf auf 4b36c63](https://github.com/Burki24/SymconECharts/actions/runs/40830651441) | Absichtlich übersprungen: Bot-Commit löst keine weitere Erhöhung aus. Kein fehlgeschlagener Check. |

Dieser Nachweis gilt für den bereits gepushten Commit. Er enthält noch den
alten Changelog-Kopf `1.350` und ist deshalb **kein fertig abgeglichener
Einreichungsstand**. Die jetzige Korrektur setzt ihn lokal auf `1.352` und
ergänzt den ausdrücklich beauftragten automatischen Abgleich. Die CI des erst
noch zu pushenden Änderungspakets wird nicht als bereits bestanden ausgegeben.

## Automatischer Abgleich der vorbereiteten Beta

`<!-- symconecharts:prepared-beta -->` unmittelbar vor der datierten
Beta-Überschrift aktiviert den Abgleich für genau diese Überschrift.
Der vorhandene Metadatenworkflow übergibt `--prepared-beta-changelog CHANGELOG.md`
an seinen Versionsplaner und nimmt `library.json` sowie `CHANGELOG.md` in
denselben Bot-Commit auf. So erzeugt der Changelog-Abgleich keinen zusätzlichen
Versionslauf. Das bestätigte Datum und alle Änderungseinträge bleiben erhalten.
Fehlt die Markierung, wird keine Versionsüberschrift verändert. Mehrdeutige
oder ungültige Markierungen stoppen den Lauf vor dem Schreiben beider Dateien.

Die freie Store-Versionsinformation oben wiederholt keine manuell gepflegte
Library-Version. Maßgeblich sind die Metadaten des ausgewählten unveränderlichen
Commits. Nach tatsächlich erfolgter Veröffentlichung die Vorbereitungsmarkierung
und den Hinweis auf die noch ausstehende Veröffentlichung im folgenden
Entwicklungsstand entfernen; die veröffentlichte Versionsüberschrift bleibt
dadurch bei späteren Metadatenläufen unverändert. Ändert sich das geplante
Veröffentlichungsdatum, muss der Eigentümer es vor der Einreichung berichtigen.

`library.json` wurde für diesen Abgleich nicht manuell verändert. Es wird keine
zukünftige Version oder noch nicht existierende Commit-ID vorweggenommen.
Die neue Automatik ist lokal durch den erweiterten CLI-Regressionstest und
`php tests/quality.php --fix` mit Exit 0 geprüft. Diese lokalen Nachweise ersetzen
nicht die noch ausstehende GitHub-CI des nächsten Eigentümer-Pushs.

Die ausstehenden Eigentümerschritte sind:

1. Das jetzige Abgleichpaket manuell committen und pushen; den vorgesehenen
   Release-Zweig gemäß Projektworkflow verwenden.
2. Den Metadatenlauf abwarten und den tatsächlichen abschließenden SHA wählen.
   Für genau diesen SHA erfolgreiche Style-/Testläufe prüfen. Der übersprungene
   Metadatenlauf auf dessen Bot-Commit ist weiterhin beabsichtigt.
3. Im selben Commit `library.json` und die markierte Beta-Überschrift auf gleiche
   Version prüfen. Alle Änderungseinträge sowie das bestätigte Datum bleiben
   erhalten. Neue fachliche Änderungen gehören unter `Unveröffentlicht`, bis
   ihr Release ausdrücklich festgelegt wird. Nicht allein den früher geprüften
   SHA 4b36c63 aus der Tabelle übernehmen.
4. Im Entwicklerbereich Bundle-ID, Beta-Kanal, Commit, Deutsch, Name,
   Beschreibung, Versionsinformation, Dokumentationslink und die tatsächlich
   angebotene passende Kategorie abgleichen. Keine Stable-Freigabe behaupten.
5. Die vollständige Vorlage kontrollieren. **Einreichen** veröffentlicht Beta
   unmittelbar und bleibt eine ausdrückliche Eigentümeraktion.

H02 ist inhaltlich vorbereitet; die CI und Metadaten des erfolgten Pushs sind
belegt. Kontoabnahme, konkrete Kategorien-ID und CI des neu angepassten
Einreichungsstands bleiben ausdrücklich offen.

Offizielle Grundlage: [Symcon: Einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/).
