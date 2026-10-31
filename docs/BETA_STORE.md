# Erste Beta: vorbereitete Store-Unterlagen

**Aktuelle Freigabesperre vom 31.10.2026:** Der Eigentümer hat die erste Beta
bis zur zugesagten JSLive-Übernahme zurückgestellt. Der gemeinsame
[Export-/Importvertrag](JSLIVE_MIGRATION_CONTRACT.md) ist spezifiziert;
Implementierung und gemeinsame Abnahme stehen aus. Die folgenden Release-Texte
und das bisher geplante Datum sind Vorbereitung, keine Einreichungsfreigabe.
Nach Abschluss der Migration sind Umfang, Versionsmetadaten und Termin erneut
mit dem Eigentümer abzugleichen. Die bisherige Diagrammabnahme bleibt gültig.

Stand: 31.10.2026. Die folgenden Inhalte sind zur manuellen Übernahme in den
Symcon-Entwicklerbereich vorbereitet. Durch diese Vorbereitung wurde keine
Store-Vorlage gespeichert und keine Version eingereicht. Bundle-ID und Deutsch
als erste Store-Sprache sind vom Eigentümer festgelegt; die Kontovorlage bleibt
ungeprüft. Geplantes Veröffentlichungsdatum ist ausdrücklich der 31.10.2026.

## Einreichungsdaten

| Feld | Vorbereiteter Inhalt |
| --- | --- |
| Bundle-ID | `de.burki24.symconecharts` |
| Kanal | Beta |
| Versionsstand | Aus `library.json` genau des im Store ausgewählten Commits übernehmen. CI-Prüfstand dieses Berichts: `1.356`; Nachweis unten. |
| Geplantes Veröffentlichungsdatum | `31.10.2026`, vom Eigentümer bestätigt. Noch keine Veröffentlichung. |
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
Diese Dokumentationsänderung verschiebt ausschließlich das geplante
Veröffentlichungsdatum auf den 31.10.2026 und aktualisiert die Release-Angaben.
Die Versionsmetadaten wurden dabei nicht manuell verändert. Die obigen
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
nicht die noch ausstehende GitHub-CI des nächsten Eigentümer-Pushs.

Die ausstehenden Eigentümerschritte sind:

1. Diese Release-Dokumentation selbst committen und auf `dev` pushen.
   Die nächste Version wird regulär vom Metadatenworkflow bestimmt; sie wird
   hier nicht vorweggenommen. Die Beta-Überschrift folgt automatisch derselben
   Version und behält das bestätigte Datum 31.10.2026.
2. Den Metadatenlauf abwarten und für dessen abschließenden Commit erfolgreiche
   Style-/Testläufe prüfen. Der übersprungene Metadatenlauf auf dem Bot-Commit
   ist weiterhin beabsichtigt.
3. Den freigegebenen Stand gemäß Projektworkflow per Pull Request nach `main`
   übernehmen. Für genau den zur Einreichung gewählten Release-Commit ebenfalls
   erfolgreiche Style-/Testläufe sowie gleiche Version in `library.json` und
   Beta-Überschrift prüfen. Der Kopf muss den 31.10.2026 tragen. Nicht einfach
   den früher geprüften SHA 8daade3 aus der Nachweistabelle übernehmen.
4. Im Entwicklerbereich Bundle-ID, Beta-Kanal, diesen Commit, Deutsch, Name,
   Beschreibung, Versionsinformation, Dokumentationslink und die tatsächlich
   angebotene passende Kategorie abgleichen. Keine Stable-Freigabe behaupten.
5. Die vollständige Vorlage kontrollieren. **Einreichen** veröffentlicht Beta
   unmittelbar und bleibt eine ausdrückliche Eigentümeraktion.

H02 ist inhaltlich vorbereitet; CI und Metadaten des geprüften Stands `1.356`
sind belegt. Kontoabnahme, konkrete Kategorien-ID sowie die abschließende CI
nach Eigentümer-Commit/Push und Übernahme des Einreichungsstands bleiben offen.
Commit, Push, Merge und Einreichung führt ausschließlich der Eigentümer aus.

Offizielle Grundlage: [Symcon: Einreichen](https://www.symcon.de/de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/store/einreichen/).
