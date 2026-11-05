# Öffentliche Beta: Store-Unterlagen und Veröffentlichungsstand

Stand: 05.11.2026. **SymconECharts ist als Beta im Symcon-Modulstore
veröffentlicht.** Der Eigentümer hat die Veröffentlichung bestätigt; die
[öffentliche Ankündigung vom 03.11.2026](https://community.symcon.de/t/beta-modul-symconecharts-nachfolger-von-jslive-charts/144844)
nennt die sofortige Verfügbarkeit. Die lesende Store-Abfrage bestätigt den
Beta-Eintrag unter `de.burki24.symconecharts`. Eine Stable-Veröffentlichung
wird damit nicht behauptet. Die frühere Terminplanung 31.10.2026 und die
noch ausstehende Ersteinreichung sind keine aktuellen offenen Punkte mehr.

Die Dokumentation auf `dev` beschreibt den Entwicklungsstand. Eine neu erzeugte
Library-Version, erfolgreiche CI und die automatisch fortgeschriebene
Changelog-Überschrift beweisen keine Veröffentlichung dieses Stands im Store.
Die Versionsnummer des ersten Store-Stands wird nicht aus der heutigen
Entwicklungsversion abgeleitet; die Ankündigung enthält keine Versionsnummer.
Ein tatsächlich gewählter Store-Commit und dessen `library.json` sind dafür
maßgeblich. Die damalige Kontokategorie und das vollständige Kontoformular
wurden hier nicht unabhängig abgenommen; sie bleiben Grenzen des Nachweises,
keine Behauptung einer noch ausstehenden Erstveröffentlichung.

Die technische Migrationsabnahme gemäß
[Vertrag 1.0](JSLIVE_MIGRATION_CONTRACT.md) einschließlich J01 ist unter 1.386
live bestanden: 53 Ziele aus sechs Familien, je zwei Wiederaufrufe mit aktuell
bestätigten Plänen und unveränderten Ziel-/Journal-IDs sowie geschützten
Beständen. [Migrationsabnahme](JSLIVE_MIGRATION_ABNAHME.md).
Die spätere Tree-JSON-Abnahme unter 1.403/1.404 und die Eigentümerbestätigung
in Kachel/IPSView sind im [Tree-JSON-Vertrag](TREE_JSON_CONTRACT.md#abnahmestand-und-verbleibende-testlücken)
gesondert dokumentiert. Diese späteren Nachweise werden nicht rückwirkend
zum Umfang des ersten Store-Stands erklärt.

## Store-Angaben

| Feld | Inhalt / Nachweis |
| --- | --- |
| Bundle-ID | `de.burki24.symconecharts` |
| Kanal | Beta; im Store bestätigt. |
| Versionsstand | `library.json` des tatsächlich im Store ausgewählten Commits; kann vom aktuellen `dev` abweichen. |
| Erste öffentliche Ankündigung | `03.11.2026`; die frühere Planung `31.10.2026` ist überholt. |
| Git-URL | `https://github.com/Burki24/SymconECharts` — Store-Quelle, keine Installationsanleitung über die Modulverwaltung. |
| Sprache | Deutsch (`de`), vom Eigentümer festgelegt. |
| Name / Installation | Im Symcon-Modulstore nach **SymconECharts** suchen und die Beta installieren. |
| Dokumentation | `https://github.com/Burki24/SymconECharts/blob/dev/README.md` |
| Kategorie | Visualisierung; die konkrete Konto-Kategorien-ID wurde hier nicht unabhängig geprüft. |
| Mindestversion | Symcon 9.0 laut `library.json`; reale Abnahme auf Symcon 9.1/PHP 8.5. |
| Lizenz | PolyForm Noncommercial 1.0.0; Fremdkomponenten gemäß `THIRD_PARTY_NOTICES.md`. |
| Ausnahmen | Keine genehmigte Ausnahme beansprucht. Lesende Ressourcenzugriffe, installierte Symcon-Icons und vorhandene Z2M-Netzwerkkarten sind dokumentiert. |

Die folgenden deutschen Beschreibungstexte bleiben als Release-Unterlagen
verfügbar. Ihre Existenz im Repository ist keine automatische Änderung am
Store-Eintrag; weitere Einreichungen nimmt ausschließlich der Eigentümer vor.

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

| Hinweis | Stand |
| --- | --- |
| Veröffentlichung | Erste öffentliche Beta erfolgt und am 03.11.2026 angekündigt; keine offene Ersteinreichung. |
| JSLive/J01 | Technisch abgeschlossen und unter 1.386 per MCP live bestanden. Aktuelle Plan- und Eigentumsprüfungen bleiben erforderlich. [Nachweis](JSLIVE_MIGRATION_ABNAHME.md). |
| StateTimeline-Archivkonflikt | Eindeutige positive Archiv-Dauern werden seit der Korrektur vom 29.10.2026 ausgewertet; alle neun damaligen Fälle sind per MCP geprüft. Archive unverändert, echte Mehrdeutigkeit bleibt Fehler. [Nachweis](STATE_TIMELINE_ARCHIVPRUEFUNG-2026-10-29.md). |
| Tree-JSON | Datei/Text, Stringquelle und öffentliche Funktionen funktional abgenommen. Weitere konkrete Testlücken bleiben im [Vertrag](TREE_JSON_CONTRACT.md#abnahmestand-und-verbleibende-testlücken). |
| Weitere Testvarianten | Native Dateiauswahl-/Tabellen-/Download-Grenzbedienungen ohne separaten Nachweis, echte Touchgeräte, längere Last-/Speicherläufe, weitere Plattform-/Installationsvarianten und horizontale Mausradfälle ohne Wirkungsnachweis bleiben sichtbar. Die bestätigte WoIst-/Helper-Mausradprüfung wird nicht wieder geöffnet. |
| README-Badges | Fünf Pflicht-Badges: Symcon/PHPModul, Mindestversion 9.0, PolyForm-Lizenz sowie dynamische Style-/Test-Badges für dieses Repository und `dev`. |
| Changelog | Automatisch fortgeschriebene Beta-Übersicht bleibt auf ausdrücklichen Eigentümerwunsch aktiv. Datum = Beginn der öffentlichen Beta, Version = aktueller Entwicklungsstand, kein historischer Erstversions- oder Store-Verfügbarkeitsnachweis. |

Die [gemeinsame Beta-Abnahme](BETA_ABNAHME-2026-10-28.md) und die
[Systemprüfung](SYSTEMPRUEFUNG-2026-10-26.md) bleiben Nachweise ihrer jeweiligen
Testebenen. Die Veröffentlichung schließt diese tatsächlichen Testlücken nicht.
Diese Dokumentationsbereinigung ist kein neuer Geräte-, Last- oder Produktivtest.

## README-Prüfung auf dev am 05.11.2026

Geprüft wurden alle 39 versionierten README-Dateien, darunter die Hauptanleitung
und alle 32 Modul-READMEs. Die regulär gelieferte Helper-Dokumentation blieb
unverändert. Der Abgleich umfasst Modulbestand, Installation, Voraussetzungen,
Quellen-/Archivmodelle, Nutzungsgrenzen, Konfigurationsverweise, Ausgabevariablen,
Skriptzugriff und Lizenzangaben. Die registrierten literalen Standardwerte
stimmen mit den jeweiligen Modultabellen der Konfigurationsreferenz überein;
gemeinsame IPSView-Entsprechungen sind dort zentral erklärt.

156 dokumentierte API-Signaturen wurden gegen die vorhandenen Methoden geprüft.
Alle drei JSON-Beispielblöcke sind syntaktisch gültig; alle 32 PHP-Beispielblöcke
bestehen den Syntaxcheck, ohne ausgeführt zu werden. Relative Dateilinks und
Sprungziele wurden geprüft. Ein alter ADR-Verweis auf den TimeSeries-Vertrag
wurde korrigiert. Strukturprüfung und Metadaten-Regressionen bestehen.

Die Benutzertexte nennen jetzt die veröffentlichte Beta, unterscheiden `dev`
vom Store-Stand und erklären JSLive Beta 0.113 als Ausgangspunkt für neue
Exporte. Die zuvor pauschalen Aussagen zu negativen Werten bei Pie und
PictorialBar gelten nun ausdrücklich nur ohne Normalisierung. Keine Quellen-,
Design-, Runtime- oder Schutzgrenzen wurden geändert. Für diese reine
Dokumentationspflege wurde keine vollständige Qualitätssuite und kein neuer
Live-Test ausgeführt; vorhandene Testlücken bleiben bestehen.

## CI- und Metadatenprüfstand vom 05.11.2026

Die letzte Prüfung vor dieser Dokumentationsbereinigung belegt den `dev`-Commit
`9d371fc2f7a3a4c241cbb00ea658dc175e7920aa` mit Version `1.405`, Build
`250185252` und `date=1793854899`. Build und Datum stammen korrekt aus dem
Quellcommit `ee98624b7c9549c538d9aa57c9e7d6af1fd8ae42` vom 05.11.2026,
06:01:39 Uhr Europe/Berlin. Auch dessen Style-/Testläufe sind erfolgreich.

| Nachweis | Ergebnis |
| --- | --- |
| [Check Style auf 9d371fc](https://github.com/Burki24/SymconECharts/actions/runs/41971699540) | Erfolgreich. |
| [Tests auf 9d371fc](https://github.com/Burki24/SymconECharts/actions/runs/41971699449) | Erfolgreich. |
| [Metadatenlauf des Quellcommits ee98624](https://github.com/Burki24/SymconECharts/actions/runs/41971395217) | Erfolgreich; erzeugte Version 1.405. |
| [Metadatenlauf auf 9d371fc](https://github.com/Burki24/SymconECharts/actions/runs/41971699436) | Absichtlich übersprungen; Bot-Commit erzeugt keine neue Version. |

Dies ist kein Store-Veröffentlichungsnachweis für 1.405. Nach dem nächsten
Eigentümer-Commit/Push sind dessen eigene abschließende CI- und Metadatenläufe
zu prüfen; sie werden hier nicht vorweggenommen.

## Historischer CI- und Metadatenabgleich zu 1.356

Die folgenden Nachweise gelten ausschließlich für den damaligen Commit.
Die technische MCP-Migrationsabnahme unter 1.386 ersetzt keine Prüfung der
GitHub-CI eines später ausgewählten Release-Commits.

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

## Automatische Fortschreibung während der Beta

Die Markierung `<!-- symconecharts:prepared-beta -->` bleibt auf ausdrücklichen
Eigentümerwunsch aktiv. Der vorhandene Metadatenworkflow übergibt
`--prepared-beta-changelog CHANGELOG.md` an den Versionsplaner und schreibt
`library.json` und die markierte Beta-Überschrift gemeinsam im Bot-Commit.
Damit bleibt die Versionsnummer automatisch konsistent. Der folgende
Bot-Commit erhöht die Version nicht erneut.

Die Überschrift ist ausdrücklich eine fortgeschriebene Beta-Übersicht.
Ihr Datum `03.11.2026` bezeichnet den Beginn der öffentlichen Beta, ihre
Version den Entwicklungsstand. Sie darf nicht als historischer Nachweis
verstanden werden, dass jede spätere Version an diesem Tag veröffentlicht
wurde oder bereits im Store verfügbar ist. Andere datierte historische
Überschriften werden nicht verändert. Einträge unter **Unveröffentlicht**
bleiben dort, bis ihr tatsächlicher Veröffentlichungsstand belegt ist.
Der Quellcommit-Zeitpunkt in `library.json` ist ebenfalls kein Store-Datum.

Die bereits vor der öffentlichen Ankündigung vorhandenen Migrationsfunktionen
und Dokumentationseinträge sind in der Beta-Übersicht zusammengeführt. Die
späteren Radar-/Darstellungsmetadatenkorrekturen und Tree-JSON-Änderungen bleiben
unter **Unveröffentlicht**, bis ihre jeweilige Store-Zuordnung feststeht.
Bestehende Änderungseinträge wurden dabei erhalten; aus der Umordnung entsteht
keine neue Versions- oder Veröffentlichungsbehauptung.

## Weitere Beta-Veröffentlichungen

Die erste Einreichung ist erledigt. Für weitere Stände bleiben diese Schritte
beim Eigentümer:

1. Gewünschte Änderungen committen und pushen; regulären Metadatenlauf und
   erfolgreiche Style-/Testläufe des abschließenden Commits prüfen.
2. Tatsächlich ausgewählten Store-Commit, dessen Version, Funktionsumfang und
   Release-Texte abgleichen. Einen gepushten `dev`-Stand nicht allein deshalb
   als veröffentlicht bezeichnen.
3. Store-Angaben kontrollieren und eine weitere Beta nur ausdrücklich einreichen.
   Bekannte Testlücken mitführen; eine Stable-Freigabe ist ein eigener Schritt.

Diese Unterlagen ändern weder Store-Konto noch Version, Build oder Datum.
Commit, Push, Merge und Einreichung führt ausschließlich der Eigentümer aus.

Offizielle Grundlage: [Symcon: Einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/).
