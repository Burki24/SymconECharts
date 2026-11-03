# ADR 0110: Lesende JSLive-Paketprüfung und Importvorschau

- Status: Implementiert; native Formular-Live-Abnahme offen
- Datum: 2026-10-31
- Ergänzt: [ADR 0109](0109-jslive-migration-contract.md),
  [Export-/Importvertrag 1.0](../JSLIVE_MIGRATION_CONTRACT.md)

## Entscheidung

Die bestehende Gateway-Instanz erhält ausschließlich nicht persistierte
Formularaktionen für die gemeinsame Migration. Neue Properties, Attribute,
Modul-GUIDs oder Zielinstanzen werden nicht angelegt. Dies ist eine lesende
Konfigurationsvorschau, noch kein gerenderter Vergleich und keine Übernahme.
Chart-Properties und Renderer bleiben unverändert in ihren Familien.

Die Zuständigkeiten liegen getrennt unter `libs`: `EChartsImportJson` prüft
JSON-Typen, doppelte Schlüssel und kanonisiert Fingerabdrücke;
`EChartsJSLivePackage` validiert den tatsächlichen Producer-Vertrag 1.0,
Identitäten, Originalbezüge, Feldbilanz, Abfragen und Ressourcen;
`EChartsJSLiveImport` berechnet unabhängig Zielkandidaten und Abweichungen;
`EChartsJSLiveRuntime` liest ausschließlich vorhandene Quellen-/Archivmetadaten;
`EChartsJSLiveImportForm` verbindet diese Funktionen mit dem Gateway-Formular.
Der reguläre Normalisierungshelper wird wiederverwendet und nicht verändert.
Der Parser hat derzeit nur diese Library als Nutzer und verbleibt hier.

## Öffentliche und interne Schnittstellen

- `ECGW_PreviewJSLiveImport(int $id, string $Package, string $Options)`:
  vollständiger deterministischer JSON-Bericht, ungültige Pakete als Ausnahme.
  `$Package` enthält unverändertes UTF-8-JSON. `$Options` ist ein Objekt mit
  optionalen `sourceMapping`-/`archiveMapping`-Objekten: `variable:ID` bzw.
  `archive:ID` auf positive vorhandene Ziel-IDs. Keine Namenssuche.
- `ECGW_PreviewJSLiveImportFromForm(int $id, string $FileData,
  string $Mappings, int $Chart, int $Page, bool $Download)`:
  interner Formularadapter mit Base64-Upload und nullbasierten Indizes;
  Erfolgsrückgabe leer, Fehlerrückgabe wird vom Callback mit `echo` angezeigt.
  `$Download=true` liefert nach derselben Ansichtserneuerung eine JSON-Daten-URL
  des gerade berechneten vollständigen Berichts statt einer zweiten Analyse.

Die MCP-Live-Prüfung hat bestätigt, dass Symcon für die exportierten Funktionen
alle drei beziehungsweise sechs Argumente verlangt. Die PHP-Standardwerte
der Modulmethoden bleiben für direkte Methodenaufrufe bestehen, sind aber kein
Vertrag der Kernel-Funktionen. Dateiauswahl übergibt ausdrücklich `'{}', 0, 0,
false`; Diagramm-/Seitenauswahl und erneutes Prüfen erhalten die Entwurfswerte
mit `false`, der Download dieselben Werte mit `true`. Keine Signaturänderung,
Migration oder zusätzliche persistente Konfiguration.

Die gemeinsame vorhandene JSLive-Installationskennung erlaubt ID-Vorschläge,
ersetzt aber keine aktuelle Metadatenprüfung. Fehlende/uneindeutige Herkunft
benötigt explizite Zuordnungen. Variablentyp, native Formatierung, vorhandene
Actions und im historischen Modell Archivierung, Aggregation und Zählerregel
werden geprüft. Archivwerte werden in dieser Phase nicht gelesen oder geändert.

Jeder Bericht enthält Original-Snapshots, Paket-/Revisions-/Planhash,
ID-Zuordnungen, native Quellen-/Bindungsformate, Kandidaten-GUIDs,
Teilkonfigurationen, bekannte Zielgrenzen, Feldbilanz und Probleme. Die drei
Urteile Quellen/Archiv, native Darstellung und Gestaltung bleiben getrennt.
Originale Export-`exact`-Einträge werden niemals als Zielerfolg übernommen.
Unbekannte Felder bleiben `unsupported`. Nicht vorhandene optionale
Abhängigkeiten und ausdrücklich ausgeschlossener Betriebszustand sind als
solche erkennbar. Alle Zielentwürfe sind derzeit unvollständig und gesperrt;
unabhängig geprüfte Metadaten können trotzdem `exact` sein.

Die bekannten Zielgrenzen sperren unpassende Quellenzahlen, Typen, Einheiten
und Mehrfachbindungen. Ein vollständiger Quellen-/Archivbudgetnachweis wird
erst mit dem jeweiligen fertigen Mapper möglich. Der Bericht nennt diesen
Nachweis ausdrücklich als offen statt Standardabfragen zu erfinden.

## Ressourcen und Grenzen

Vorlagen, Skripte, Fonts und SVGs werden ausschließlich als inerte Bytes auf
Länge, Base64 und SHA-256 geprüft. Kein `eval`, Fremdabruf, Entpacken oder
Installation dieser Inhalte. Der vollständige Bericht enthält den Originalstand
als Vergleichsdaten; die native Oberfläche führt ihn nicht aus. Der
native Download verwendet eine JSON-Daten-URL und erzeugt keine Datei im Kernel.
Debugmeldungen enthalten weder Paket noch Bericht.

Zusätzlich zu den unveränderten Transportgrenzen gelten 100000 JSON-Werte je
Dokument, 32 Ebenen für die verschachtelte Inventur und 8 MiB vollständiger
Bericht. Die Grenzen schützen den 128-MiB-Prozess und lehnen sichtbar ab;
niemals stille Kürzung oder Anhebung bestehender Zielbudgets. Tabellen werden
mit 100 Zeilen je Seite durchgeblättert. Der sichtbare Teilentwurf umfasst
höchstens 4000 Zeichen; der Download enthält alle Daten.

## Nachweise und offene Abnahmen

`tests/jslive_import.php` prüft fehlerhafte Versionen/Typen/Referenzen,
doppelte Schlüssel, Zahlen-/Byte-/Strukturgrenzen, Ressourcenintegrität,
Herkunft, aktuelle Quellenänderungen, native Neuableitung, Familienzuordnung,
Feldbilanz, deterministische Wiederholung und Zielgrenzen ohne Schreibzugriff.
`tests/jslive_import_form.php` führt die echten Gateway-Methoden und exakten
Formularcallbacks mit einem lesenden Offline-Kernel aus. Geprüft werden
Download, Seiten, Meldungen, veraltete Pläne nach Fehlern und geschützte Logs.
Die Funktionsadapter benötigen wie Symcon alle sechs beziehungsweise drei
Argumente und lehnen abweichende Anzahlen ab. Der Test führt alle fünf
Callbackskripte aus und prüft Rücksetzungen bei neuer Datei, explizite
ID-Zuordnungen, nullbasierte Indizes und den Download-Schalter. Die alten
verkürzten Aufrufe scheitern mit dieser Fixture reproduzierbar.
Beide laufen im regulären Test-Runner; das 128-MiB-Limit bleibt erhalten.

Die korrigierten fünf Callbackskripte aus dem Repository wurden zusätzlich per
MCP gegen das unveränderte Gateway aus SymconECharts 1.367 ausgeführt
(Symcon 9.1, PHP 8.5.8, 64 MiB). Dateiauswahl, Diagramm-/Seitenauswahl,
erneutes Prüfen und Download bestehen mit sechs Pflichtargumenten; der Download
enthält den vollständigen Bericht und exakte Quellen-/Präsentationsmetadaten.
Ein ungültiger Upload meldet den Paketfehler. Konfigurationen, Quellwerte,
Darstellungen, Archivbestände und Objektmenge blieben im Vergleich unverändert.
Dabei wurden keine Moduldateien bereitgestellt. Die native Editorbedienung des
aktualisierten Formulars nach Modulupdate bleibt als eigene Abnahme offen.
Die vollständige lokale Qualitätssuite `php tests/quality.php --fix` besteht
nach der Korrektur mit Exit 0, einschließlich Style, Syntax, Vertrags-,
Integrations- und Layouttests; das CLI-Speicherlimit bleibt 128 MiB.

Zusätzlich wurden lokal synthetische Pakete aus den fünf echten JSLive-
Export-Einstiegspunkten gegen den Parser geprüft. Dies ersetzt nicht die
native Gateway-Formularabnahme nach Modulupdate. Vollständige Mapper,
neue Fachmodelle, Archiv-/Bildgleichheit, ausdrückliche Anlagebestätigung,
Importjournal und Wiederanlauf bleiben spätere Schritte.
