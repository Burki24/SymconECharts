# Gemeinsame Beta-Abnahme am 28.10.2026

Nachtrag 29.10.2026: Der unten historisch beschriebene StateTimeline-Archivfall
ist inzwischen durch Auswertung eindeutiger nativer Archiv-Dauern korrigiert
und per MCP nachgeprüft. Es wurden keine Archivdaten bereinigt. Die damalige
Einordnung als bloßer Datenkonflikt war zu pauschal; siehe
[neuer Nachweis](STATE_TIMELINE_ARCHIVPRUEFUNG-2026-10-29.md).

## Umfang und Entscheidung

Geprüft ist das gemeinsame Arbeitspaket auf `dev`, ausgehend von
`1a881d247446d0e3a2d8dd5a99ffa0466293fe5b` und Library `1.350`.
Der Arbeitsbaum enthält die anschließende Hookkorrektur und ist noch kein
unveränderlicher Release-Commit. Commit, Push und Store-Einreichung bleiben
beim Eigentümer. Versionsmetadaten wurden nicht manuell verändert.

| Befund | Ergebnis |
| --- | --- |
| B01 – gemeinsamer Gateway-Hook | Korrigiert und durch Regression sowie echte Mehr-Gateway-HTTP-Aufrufe abgesichert. Automatische Übernahme nach Löschung funktioniert. |
| H01 – gemeinsame Laufzeitabnahme | Neuinstanzen, Bibliotheksreload und alle 29 Familien auf der Testinstallation bestanden. Bestehende Kachel-/IPSView-Abnahme vom Eigentümer ausdrücklich bestätigt. |
| H02 – Beta-Unterlagen | Bundle-ID und Deutsch bestätigt; vollständige Texte und Übergabecheckliste in [BETA_STORE.md](BETA_STORE.md). Kontoformular, tatsächliche Kategorie und endgültiger Commit bleiben formale Eigentümerschritte. |

Damit ist der bestätigte technische Beta-Blocker beseitigt. Eine bereits
veröffentlichte oder vollständig im Store eingereichte Beta wird nicht behauptet.

## Lokale Prüfung

- Der neue Gateway-Regressionstest begann RED: Ein weiteres Gateway übernahm
  den exklusiven Hook. GREEN prüft deterministische Eigentümerschaft auch bei
  abweichender ID-Reihenfolge, beide Datenflussparent-Gruppen, geschützte
  Kanäle/Offsets, native Navigation, mehrfache Eigentümerlöschung und Kernelstart.
- Ein zusätzlicher RED-/GREEN-Fall verhindert Peer-Aufrufe, solange deren
  PHP-Schnittstelle während eines Reloads noch nicht verfügbar ist. Der erste
  reale Mehr-Gateway-Reload deckte diesen Fehler auf; der korrigierte Reload
  protokollierte anschließend keine Warnungen oder Fehler.
- Die gemeinsame Integration enthält den neuen Test. Die Kernel-Doppel für
  GraphZ2M verwenden dieselbe zentrale Instanzauflösung, ohne Funktionskollision.
- Einzelprüfungen für Modulverträge, striktes SDK, Archivzustände und
  GraphZ2M-Verbindungsauswahl bestanden. Die abschließende Qualitätssuite wird
  unten mit ihrem tatsächlichen Ergebnis festgehalten.

## Reale Testinstallation

Die folgenden MCP-Prüfungen liefen auf der freigegebenen Testinstallation mit
Symcon 9.1/PHP 8.5 unter Windows. Keine produktiven Symcon-API-Schreibzugriffe.
Private IDs, Kanäle, Zugriffspfade und Quellnamen sind nicht Teil dieses Berichts.

| Prüfschritt | Nachweis |
| --- | --- |
| Neuanlage ohne Konfiguration | Alle 30 Module erzeugt, alle Formulare gültiges JSON. Gateway Status 102; 29 unkonfigurierte Diagramme kontrolliert Status 201, keine Create-Fatals. |
| Gültige Modelle | 29 temporäre Diagramme mit übernommenen Konfigurationen, ausschließlich die temporären Instanzen verändert. Alle 29 Status 102 und gültige Modelle. |
| StateTimeline | Vier eigene Boolean-Testarchive mit definierten Zustandswechseln. Der bereits bekannte widersprüchliche reale Archiveintrag wurde weder geändert noch verborgen. |
| Zwei Datenflussgruppen | Alle 29 Familien abwechselnd an zwei temporäre Gateways angeschlossen. Jeder erzeugte HTTP-Zustandspfad antwortete mit 200 und gültigem JSON über den tatsächlichen Parent. |
| Eigentümerlöschung | Temporäre Kinder vorab auf das andere temporäre Gateway umgehängt. Unveränderter Bestandszustandspfad vor Löschung 200, unmittelbar im Löschaufruf einmal 404, im nächsten unabhängigen Abruf automatisch wieder 200. Kein manueller Refresh oder ApplyChanges nötig. |
| Bibliotheksreload | Abschließender Mehr-Gateway-Reload verbindet 62 Instanzen aus Bestand und Test neu; keine Warnungen oder Fehler. Danach alle 29 Testfamilien Status 102, HTTP 200 und gültiges JSON. |
| Ausgabebudgets | Alle erzeugten IPSView-Dokumente unter 1 MiB; größtes Dokument im abschließenden Rundlauf 811881 Bytes. Ausgabeerzeugung ist kein zusätzlicher nativer Darstellungstest. |
| Negative HTTP-Fälle | Falscher Kanal 404, Offset 121 und unbekannte Ausgabe 400, POST 404. IPSView-Kanal für Kachelausgabe 404: getrennte Kanäle bleiben getrennt. Deaktivierter temporärer IPSView-Transport 404, nach Wiederherstellung 200. |
| Bestandserhalt | Nach Abschluss SHA-256 aller 32 ursprünglichen Konfigurationen und deren Datenflussparent mit dem Ausgangsstand verglichen: keine Änderungen. |
| Bereinigung | Insgesamt 31 temporäre Instanzen entfernt, davon der Hook-Eigentümer schon im Übergabetest. 29 Ausgabevariablen, vier eigene Variablen samt Testarchivdaten, Manifest und Kategorie entfernt. Nur das ursprüngliche Gateway bleibt. |
| Abschließender Zustand | Ursprüngliches Gateway Status 102; unveränderter Bestandszustandspfad HTTP 200 und gültiges JSON. Beide temporären Gateways und die Testkategorie existieren nicht mehr. |

## Vorliegende Darstellungsabnahme und Systemzugriff

Der Eigentümer hat Kachel und IPSView bereits funktional geprüft und diese
IPSView-Abnahme am 28.10.2026 nochmals ausdrücklich bestätigt. Sie wird als
vorliegende Eigentümerabnahme übernommen; ein neuer vollständiger nativer
App-Durchlauf durch den Agenten wird nicht behauptet.

Bei der zunächst begonnenen nativen App-Prüfung wurde ein bereits produktiv
verbundenes Clientprofil ohne vorherige Ankündigung eingesehen. Das entsprach
nicht der vom Eigentümer gewünschten Zugriffspraxis. Es gab keine produktiven
MCP-Schreibzugriffe. Nach der Rückmeldung wurde die Computerbedienung beendet;
der zuletzt angestoßene Rückwechsel des Clientprofils wurde wegen des
Benutzerabbruchs nicht weiter kontrolliert. Weitere Produktivzugriffe sind vorab
mit Zweck und Umfang anzukündigen; diese Regel ist in `AGENTS.md` festgehalten.

## Verbleibende Grenzen

- Der native Kernelstart wurde durch den Regressionstest simuliert; ein echter
  Dienstneustart war nicht Teil dieser Abnahme.
- IM_DELETE wird asynchron verarbeitet. Die kurze beobachtete HTTP-Unterbrechung
  beim Eigentümerwechsel ist kein Nachweis unterbrechungsfreier Übergabe.
- Echte Touchgeräte, weitere Plattform-/Installationsvarianten, horizontale
  Mausradfälle ohne bisherigen Wirkungsnachweis und mehrstündige Last-/
  Speichertests bleiben offen. Die bereits bestätigte WoIst-/Helper-Mausrad-
  Prüfung wird nicht erneut als Lücke geführt.
- Der StateTimeline-Archivdatenkonflikt bleibt eine Datenfrage. Der Bericht
  vom [26.10.2026](SYSTEMPRUEFUNG-2026-10-26.md) enthält die übrigen vorhandenen
  Last-, Fehler-, Wiederanlauf- und Debugnachweise samt deren Grenzen.
- Die Store-Unterlagen sind inhaltlich vorbereitet. Einreichungs-Commit,
  Kontokategorie und tatsächliche Vorlage müssen vor der Eigentümereinreichung
  feststehen. Symcon-Store-Freigabe wird daraus nicht abgeleitet.

## Abschließende Qualitätssuite

`php tests/quality.php --fix` endet mit Exit 0: PHP-/JSON-Formatierung,
Whitespace, PHP-Syntax, gemeinsame Repositorytests und sämtliche vorgesehenen
JavaScript-Layout-/Runtime-Prüfungen bestanden. Der abschließende Log enthält
keine PHP-Warnungen oder Fatalfehler. Die zuvor entdeckten Testdoppel-Kollisionen
und der Reload-Sonderfall sind in diesem gemeinsamen grünen Lauf enthalten.

`git diff --check` sowie LF-/UTF-8-ohne-BOM-Prüfung aller geänderten und neuen
Textdateien bestanden. Der auf der Testinstallation zuletzt geladene Gateway-
Quelltext entspricht bytegleich der lokalen Datei (SHA-256-Präfix
`04f289efaa91e169`); der abschließende Bestandsreload meldete keine Warnungen
oder Fehler. Ein weiterer Vergleich der 32 ursprünglichen Konfigurationen und
Parents nach diesem Reload blieb unverändert.

Der statische Store-Scanner meldet 30 Module und keine strukturellen Blocker.
Seine 182 Review-Signale sind heuristische Prüforte, keine 182 bestätigten
Defekte und kein eigenständiger Store-Freigabenachweis. Die bestehende Einordnung
von Dateizugriffen, Benutzerkonfiguration und Transport wird nicht durch eine
bloße Scannerzählung ersetzt.
