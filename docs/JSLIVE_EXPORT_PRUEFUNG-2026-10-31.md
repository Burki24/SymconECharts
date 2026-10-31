# JSLive-Producer und gemeinsame Normalisierung: lokale Prüfung

Stand: 31.10.2026, PHP 8.5.10 auf Windows. Drei getrennte Repositories:
Symcon_ModuleHelper, JSLive und SymconECharts. Keine Bereitstellung, keine
Installation geändert, keine Archivwerte oder Quellkonfigurationen geschrieben.

## Implementiert und geprüft

- Zentrale lesende `VariablePresentationNormalizationHelper`-Klasse mit
  normalisierten nativen Parametern, vollständigen Originaldefinitionen,
  Herkunftspfaden und blockierenden Meldungen für nicht belegte Regeln.
- Native Wertanzeige, Slider und Enumeration; gewöhnliche Legacy-Zahlenprofile,
  Boolean-/String-Optionen und konstante numerische Schwellenassoziationen.
  Native Prozentformatierung und JSLives bloßer Prozenttext sind getrennt.
- Vollständiger JSLive-Einzelchart-Producer für Chart, DoughnutPie, Gauge,
  RadarChart und Progressbar. Bestehender Export bleibt unverändert verfügbar.
- Originale Steuerwerte, Zeitplanung, wiederholte Bindungen und gleichnamige
  unterschiedliche Quellen, Achsen-/Ringprofile, gewählte Ausgaben, wirksame
  Parentfelder ohne Adressen/Zugangsdaten, Vorlagen, lokale Schriften und Formen.
- Eigentümerkennung über mehrere Splitter mit Wiederholung und Konfliktsperre.
  Fehlende neue Exportvoraussetzungen unterbrechen alte Ausgaben nicht.
- ECharts verwendet denselben Konverter über den neuen lesenden
  `EChartsVariablePresentation::Snapshot()`-Adapter. Die alte `Resolve()`-
  Auflösung und bestehende Chart-Designs sind unverändert.

## Nachweise vor der regulären Verteilung

| Befehl | Ergebnis |
| --- | --- |
| `php tests/quality.php --fix` in SymconECharts | Erfolgreich mit temporärem vollständigem Helper-Testbundle; Format, Syntax, Verträge, Integration, Layout und Integrität |
| `php tests/presentation_snapshot.php` in SymconECharts | Erfolgreich am abschließenden Helper-/Adapterstand |
| `php tests/run.php` in JSLive | Erfolgreich mit temporärem regulär erzeugtem Bundle, einschließlich der neuen Export-/Herkunftstests und bisherigen Exportfälle |
| `php -d extension=gd tests/run.php` in Symcon_ModuleHelper | Erfolgreich; GD nur für den bereits vorhandenen Rasterimage-Test im gesamten Runner aktiviert |
| `python .github/scripts/verify_manifest.py` in Symcon_ModuleHelper | Erfolgreich, 32 Helper |
| `python tests/helper_sync_bundle.py` / `python tests/helper_sync_distribution.py` | Erfolgreich, ohne GitHub- oder Consumer-Schreibzugriffe |
| Gezielter php-cs-fixer-Prüflauf des neuen Helpers und seiner Tests | Erfolgreich |
| `git diff --check`, UTF-8/LF und lokale Dokumentverweise | Geprüft |

Die neue Exportprüfung verwendet die echten öffentlichen Kindmodul-Methoden
und deren originale Modell-/Zeitraumfunktionen mit synthetischen Symcon-APIs.
Sie prüft die Trennung eigener Skripte, Ressourcenbytes und Hashes, fehlende
Quellen, unsichere Schriftpfade, Konfigurationsbudgets nach Ausschluss alter
Importdaten und die unveränderte Konfiguration. Sie ist kein realer HTTP-Test.
Die ursprünglichen 20 Chart-Exportfälle und 108 ReceiveData-Fälle laufen weiter.

## Nachprüfung mit regulären Helpern

Am 31.10.2026 wurden die gepullten Consumer-Stände erneut geprüft:
SymconECharts `bc5786ad7f8fe094440394ff54706c70a512af93` zuzüglich der
rein mechanischen Array-Ausrichtung in `tests/validate_structure.php` sowie
JSLive `1b91ee9aa78c56086de8ec5bd5992f5a324048fe` ohne Änderungen.
Beide enthalten `VariablePresentationNormalizationHelper` 1.0.0 aus dem
regulären Helper-Bundle 3.30.0, Quelle
`c2690313db70d2c2c9fd7d2549461f43a50384e9`, SHA-256
`1385757d6c2b2190c6c11c01a0d8c1a44af789ca4132fbd3ac9d3ec39c052db9`.
Es wurden keine Helper-Testkopien oder veränderten Vendor-Manifeste verwendet.

| Prüfung | Ergebnis |
| --- | --- |
| php-cs-fixer für `tests/validate_structure.php` | Style-Fehler reproduziert; der vorgeschriebene Qualitätslauf korrigiert ausschließlich die Ausrichtung von neun Array-Zeilen. Der Diff ohne Whitespace ist leer. |
| `php tests/presentation_snapshot.php` | Erfolgreich mit dem regulär verteilten Helper; Originalformatierung und lesender Zugriff erhalten. |
| `python tests/helper_integrity.py` in SymconECharts | Erfolgreich, zehn Abonnements; Quellcommit und SHA-256 des Normalisierungshelpers zusätzlich geprüft. |
| `php tests/run.php` in JSLive | Vollständig erfolgreich, einschließlich fünf neuer Export-Einstiege, Herkunftskennung, bisheriger 20 Export- und 108 Empfängerfälle sowie zwölf Helper-Abonnements mit 17 Dateien/Ressourcen. |
| PHP-Syntax der JSLive-Migrationsänderungen | Erfolgreich, 19 PHP-Dateien. |
| `php tests/quality.php --fix` in SymconECharts | Formatierung und Syntax aller 227 PHP-Dateien erfolgreich; Gesamtlauf mit Exit 255 im monolithischen Gateway-Test an dessen unverändertem 128-MiB-Limit abgebrochen. |
| `php tests/gateway_gauges.php --current-normalization` | Erfolgreich im eigenen Prozess bei unverändertem 128-MiB-Limit, einschließlich der im Gesamtlauf betroffenen PictorialBar-Normalisierung. |
| Sämtliche 74 nachgelagerten Befehle aus `tests/run.php` | Separat unverändert ausgeführt und vollständig erfolgreich: zusätzliche Integrationsfälle, Layouts, Frontend-Verträge, Helper-Integrität und Metadatenprüfung. Der Abbruch der vorangehenden monolithischen Fixture wird dadurch nicht aufgehoben. |

Der Gesamtabbruch trat unter Windows/PHP 8.5.10 ZTS in
`IPSViewHTMLPageHelper::RenderVisualizationHTMLPage()` beim Rendern der
PictorialBar-Testausgabe auf (`tests/pictorial_bar_normalization.php:36`).
Die isolierte Prüfung besteht; der kumulierte Speicherbedarf der großen
Gateway-Fixture bleibt als separates offenes Testergebnis bestehen. Weder
Speicherlimit noch Quellen-, Modell- oder Ausgabebudgets wurden angehoben.
Damit sind Export und Snapshot lokal mit der regulären Abhängigkeit geprüft;
eine vollständig grüne ECharts-Gesamtsuite wird nicht behauptet.

## Noch nicht nachgewiesen oder vorhanden

- Der reale Download aus den fünf Symcon-Formularen nach regulärer
  Helper-Verteilung und Modulupdate; noch keine neue MCP-/UI-Abnahme.
- Weitere spezielle native/Legacy-Darstellungsarten, dynamische printf-Texte,
  problematische Bereiche und kombinierte Prozent-/Assoziationssemantik sind
  kontrolliert blockiert, keine behauptete verlustfreie Konvertierung.
- Ein nativer SDK-Farb-/Darstellungsrundlauf an der Zielinstallation und die
  eigentliche ECharts-Importvorschau, Zielmodelle, Bestätigung und Journal.
- Erneute CI nach dem Eigentümer-Commit/Push der Style-Korrektur und der
  erfolgreiche monolithische ECharts-Gesamtlauf unter Windows bei 128 MiB.
  Bestandsabnahmen bleiben gültig.

## Lieferzustand

Die regulären Helper-Bundle-PRs sind in beiden Consumer-Repositories auf `dev`
gemergt und vom Eigentümer gepullt. Die Export-/Snapshot-Änderungen sind in
den oben genannten Consumer-Ständen enthalten. `libs/helper` bleibt bei der
Nachprüfung unverändert; die bisher offene reguläre Abhängigkeit ist erfüllt.
Der ursprüngliche Lieferablauf samt vorbereitenden Abo-Commit-Texten steht unter
[Verteilung und Abnahme](../../JSLive/docs/ECHARTS_EXPORT.md#verteilung-und-abnahme).
