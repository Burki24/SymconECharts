# Erste Beta: vorbereitete Store-Unterlagen

**Technische Migrationsabnahme abgeschlossen:** Die zugesagte JSLive-Übernahme
gemäß [Vertrag 1.0](JSLIVE_MIGRATION_CONTRACT.md) einschließlich J01 ist unter
1.386 live bestanden. Alle 53 vorhandenen Testziele aus sechs Familien wurden
mit aktuell bestätigten Plänen je zweimal erfolgreich wiederaufgerufen;
Ziel-/Journal-IDs und geschützte Bestände blieben unverändert. Die frühere
migrationsbedingte Freigabesperre ist damit technisch erledigt. Einzelheiten
stehen in der [Migrationsabnahme](JSLIVE_MIGRATION_ABNAHME.md).

Stand: 03.11.2026. Die folgenden Inhalte sind zur manuellen Übernahme in den
Symcon-Entwicklerbereich vorbereitet. Durch diese Vorbereitung wurde keine
Store-Vorlage gespeichert und keine Version eingereicht. Bundle-ID und Deutsch
als erste Store-Sprache sind vom Eigentümer festgelegt; die Kontovorlage bleibt
ungeprüft. Release-Commit samt CI/Metadaten, Store-Angaben und Termin sind vor
Einreichung abschließend abzugleichen. Die bisherige Diagrammabnahme bleibt gültig.
Die frühere Terminplanung 31.10.2026 ist überholt; ein neuer Veröffentlichungstermin
wird hier nicht festgelegt. Die vorbereitete Changelog-Überschrift trägt noch
das frühere Datum und ist nach der neuen Eigentümerfestlegung abzugleichen.

## Einreichungsdaten

| Feld | Vorbereiteter Inhalt |
| --- | --- |
| Bundle-ID | `de.burki24.symconecharts` |
| Kanal | Beta |
| Versionsstand | Aus `library.json` genau des im Store ausgewählten Commits übernehmen. Technische Migrationsabnahme unter `1.386`; die unten belegte GitHub-CI zu `1.356` ist ein historischer Nachweis. |
| Geplantes Veröffentlichungsdatum | Neu vom Eigentümer festzulegen. Frühere Planung: `31.10.2026`; keine Veröffentlichung erfolgt. |
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
bietet 31 Diagrammfamilien, darunter Gauges, Zeitreihen, Balken, Kreisdiagramme,
Heatmaps, Kalender, Graphen und Zustandszeitdiagramme. Native Symcon-Kacheln
und optionale IPSView-HTML-Widgets besitzen anpassbare Designs und Vorschauen.
Historische Zeiträume, Quellenauswahl und Schutzbudgets werden je Diagramm
angeboten. Geprüfte JSLive-Exporte 1.0 lassen sich nach Vorschau und Bestätigung
als neue ECharts-Instanzen übernehmen. Originale bleiben erhalten; konkrete
Abweichungen werden ausgewiesen. Apache ECharts wird lokal mitgeliefert.
Erste öffentliche Beta; weitere Geräte-, Touch- und Langzeitprüfungen sind
noch nicht vollständig.
```

## Versionsinformation für die erste Beta

```text
Erste öffentliche Beta von SymconECharts mit 31 Diagrammfamilien für aktuelle
Werte, Archive und Objektbeziehungen. Die Ausgabe unterstützt native Kacheln
und IPSView, getrennte Designer, Vorschauen und Live-Aktualisierungen.

Die geprüfte JSLive-Übernahme umfasst vollständige Paket-/Quellenprüfung,
Vorschau, einzeln bestätigte Abweichungen und ein wiederaufnehmbares Journal.
Auch nach Modulupdates können geprüfte Übernahmen mit aktueller Bestätigung
sicher wiederaufgenommen werden. JSLive-Originale, Quellen, Archive und Designs
bleiben erhalten; eine beliebige oder pixelgleiche Chart.js-Migration wird
nicht zugesagt.

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
| JSLive/J01 | Technisch abgeschlossen und unter 1.386 per MCP live bestanden: 53 vorhandene Ziele aus sechs Familien, je zwei Wiederaufrufe mit aktuell bestätigtem Plan. Aktuelle Plan- und Eigentumsprüfungen bleiben erhalten; alte Bestätigungen und ungültige Vorgänger werden abgewiesen. [Nachweis](JSLIVE_MIGRATION_ABNAHME.md). |
| StateTimeline-Archivkonflikt | Am 29.10.2026 genauer untersucht und korrigiert: neun Gruppen schneller Wechsel besitzen jeweils genau einen positiven Dauereintrag. Die frühere pauschale Einordnung als Datenfehler war zu weitgehend. Die gemeinsame Gateway-Auswertung berücksichtigt diese eindeutige Information; alle neun Fälle sind auf der Testinstallation per MCP geprüft. Archive bleiben unverändert. Echte Mehrdeutigkeit bleibt ein Fehler. [Nachweis](STATE_TIMELINE_ARCHIVPRUEFUNG-2026-10-29.md). |
| Weitere Testvarianten | Native Dateiauswahl-/Tabellen-/Download-Grenzbedienungen ohne separaten Nachweis, echte Touchgeräte, längere Last-/Speicherläufe, weitere Plattform-/Installationsvarianten und horizontale Mausradfälle ohne Wirkungsnachweis bleiben als Testlücken sichtbar. Daraus folgt keine neue Funktionsentwicklung und keine Behauptung, diese Varianten seien geprüft. Kachel, IPSView und die bereits bestätigte WoIst-/Helper-Mausradprüfung bleiben abgenommen. |
| README-Badges | Alle fünf Pflicht-Badges sind bereits vorhanden: Symcon/PHPModul, Mindestversion 9.0, tatsächliche PolyForm-Lizenz sowie dynamische Style-/Test-Badges für dieses Repository und `dev`. |
| Changelog | Die vorhandenen Beta-Einträge bleiben erhalten. Die markierte Versionsüberschrift folgt gemeinsam mit `library.json` dem Metadatenworkflow; das bisherige Datum ist vor Einreichung mit dem neu festgelegten Termin abzugleichen. Historische Releases bleiben unverändert. |

Die [gemeinsame Beta-Abnahme](BETA_ABNAHME-2026-10-28.md) und die
[Systemprüfung](SYSTEMPRUEFUNG-2026-10-26.md) bleiben die Nachweise ihrer
jeweiligen Testebenen. Die redaktionelle Release-Vorbereitung ist kein neuer
Geräte-, Last- oder Produktivtest. Bekannte Grenzen bleiben erhalten.

## Historischer CI- und Metadatenabgleich zu 1.356

Die folgenden Nachweise gelten ausschließlich für den damaligen Commit.
Die technische MCP-Migrationsabnahme unter 1.386 ersetzt keine Prüfung der
GitHub-CI des künftig zur Einreichung ausgewählten Release-Commits.

Beim dokumentierten Abgleich am 31.10.2026 standen GitHub und der lokale Branch
`dev` auf demselben HEAD: `8daade37edb98439b4c236ce8d72179285e851c3`. Der Metadatencommit enthält
Version `1.356`, Build `205210267` und `date=1793290222`.
Build und Datum stammen korrekt aus dem Quellcommit
`c3b429b0b7af7bba3c35a8a46c87ef42124040bb`: dessen sieben führende Hexzeichen
ergeben den Build; dessen Commit-Zeit ist 29.10.2026, 17:10:22 Uhr Europe/Berlin.
Das Metadatendatum ist der Quellcommit-Zeitpunkt, kein Veröffentlichungsnachweis.
Es wird nicht auf das geplante Veröffentlichungsdatum umgeschrieben.

| Nachweis | Ergebnis |
| --- | --- |
| [Check Style auf 8daade3](https://github.com/Burki24/SymconECharts/actions/runs/40909319039) | Erfolgreich, exakt auf dem Metadatencommit. |
| [Tests auf 8daade3](https://github.com/Burki24/SymconECharts/actions/runs/40909319104) | Erfolgreich, exakt auf dem Metadatencommit. |
| [Metadatenlauf des Quellcommits c3b429b](https://github.com/Burki24/SymconECharts/actions/runs/40908926284) | Erfolgreich; erzeugte 8daade3 und Version 1.356. Auch Style und Tests des Quellcommits sind erfolgreich. |
| [Metadatenlauf auf 8daade3](https://github.com/Burki24/SymconECharts/actions/runs/40909319107) | Absichtlich übersprungen: Bot-Commit löst keine weitere Erhöhung aus. Kein fehlgeschlagener Check. |

Der gepushte Stand enthält die StateTimeline-Dauerkorrektur und HelperBundle
3.29.3. Sein Changelog ist automatisch auf dieselbe Version `1.356` fortgeführt.
Die damalige Dokumentationsänderung bereitete den 31.10.2026 als Termin vor.
Diese Planung ist inzwischen historisch. Die Versionsmetadaten wurden dabei
nicht manuell verändert. Die obigen
CI-Nachweise gelten ausschließlich für den genannten Commit. Spätere
Dokumentations-/Metadatencommits benötigen eigene erfolgreiche CI-Läufe;
ihre Commit-IDs und Ergebnisse werden hier nicht vorweggenommen.

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
nicht die GitHub-CI des jeweils zur Einreichung gewählten Commits.

Die ausstehenden Eigentümerschritte sind:

1. Diese Release-Dokumentation selbst committen und auf `dev` pushen.
   Die nächste Version wird regulär vom Metadatenworkflow bestimmt; sie wird
   hier nicht vorweggenommen. Die Beta-Überschrift folgt automatisch derselben
   Version. Vor der Einreichung den neuen Veröffentlichungstermin festlegen
   und die vorbereitete Changelog-Überschrift sowie diese Unterlagen damit abgleichen.
2. Den Metadatenlauf abwarten und für dessen abschließenden Commit erfolgreiche
   Style-/Testläufe prüfen. Der übersprungene Metadatenlauf auf dem Bot-Commit
   ist weiterhin beabsichtigt.
3. Den freigegebenen Stand gemäß Projektworkflow per Pull Request nach `main`
   übernehmen. Für genau den zur Einreichung gewählten Release-Commit ebenfalls
   erfolgreiche Style-/Testläufe sowie gleiche Version in `library.json` und
   Beta-Überschrift prüfen. Das Datum muss dem neu festgelegten Termin entsprechen.
   Nicht einfach den früher geprüften SHA 8daade3 aus der Nachweistabelle übernehmen.
4. Im Entwicklerbereich Bundle-ID, Beta-Kanal, diesen Commit, Deutsch, Name,
   Beschreibung, Versionsinformation, Dokumentationslink und die tatsächlich
   angebotene passende Kategorie abgleichen. Keine Stable-Freigabe behaupten.
5. Die vollständige Vorlage kontrollieren. **Einreichen** veröffentlicht Beta
   unmittelbar und bleibt eine ausdrückliche Eigentümeraktion.

H02 ist inhaltlich vorbereitet; die historische CI/Metadatenprüfung zu `1.356`
und die technische Migrationsabnahme unter `1.386` sind belegt. Kontoabnahme,
konkrete Kategorien-ID, neuer Veröffentlichungstermin sowie die abschließende CI
nach Eigentümer-Commit/Push und Übernahme des Einreichungsstands bleiben offen.
Commit, Push, Merge und Einreichung führt ausschließlich der Eigentümer aus.

Offizielle Grundlage: [Symcon: Einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/).
