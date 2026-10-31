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

## Nachweise

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

## Noch nicht nachgewiesen oder vorhanden

- Der reale Download aus den fünf Symcon-Formularen nach regulärer
  Helper-Verteilung und Modulupdate; noch keine neue MCP-/UI-Abnahme.
- Weitere spezielle native/Legacy-Darstellungsarten, dynamische printf-Texte,
  problematische Bereiche und kombinierte Prozent-/Assoziationssemantik sind
  kontrolliert blockiert, keine behauptete verlustfreie Konvertierung.
- Ein nativer SDK-Farb-/Darstellungsrundlauf an der Zielinstallation und die
  eigentliche ECharts-Importvorschau, Zielmodelle, Bestätigung und Journal.
- CI der noch nicht gepushten Änderungen sowie die endgültige Integration
  nach dem tatsächlichen Helper-Pull. Bestandsabnahmen bleiben gültig.

## Lieferzustand

Die Consumer-Testdatei, Testmanifeste und generierten Test-READMEs werden vor
Übergabe wieder entfernt beziehungsweise auf den gesicherten Ausgangsstand
zurückgestellt. Änderungen an Subscriptions, Sourcecode und Tests bleiben
erhalten. Der Consumer-Arbeitsbaum benötigt damit zuerst den regulären neuen
Helper und ist noch nicht als vollständiger Consumer-Commit freigegeben.
Die genaue Reihenfolge samt vorbereitenden Abo-Commit-Texten steht unter
[Verteilung und Abnahme](../../JSLive/docs/ECHARTS_EXPORT.md#verteilung-und-abnahme).
Helper-Code, Registry und Manifest sind das zuerst freizugebende zentrale Paket.
