# ADR 0094: Auswahl gespeicherter Zigbee-Verbindungen

- Status: Angenommen
- Datum: 2026-10-23
- Ergänzt: ADR 0047 und 0066

## Vertrag und Kompatibilität

GraphZ2M erhält `LinkSelection` mit `strongest` (Default), `weakest` und
`range`. `MaximumLQI` ist additiv mit Default 255 und wird nur bei `range`
ausgewertet. Der vorhandene `MinimumLQI` gilt in allen Modi. Aktive Grenzen
liegen einschließlich in 0–255; gleiche Grenzen sind zulässig. Unbekannte
Modi und umgekehrte aktive Grenzen werden abgewiesen, nicht still korrigiert.
Keine Migration, keine Property-/Ident-/GUID-Änderung und keine Designänderung.

Die bestehende Auflösung auf den höchsten LQI je gerichteter Verbindung erfolgt
**vor** der Bereichsfilterung. Eine außerhalb liegende starke Dublette wird
nicht durch eine schwächere Meldung ersetzt. Gegenrichtungen bleiben eigenständig.
Danach wird einschließlich gefiltert und auf-/absteigend sortiert; `range`
verwendet stärkste passende Verbindungen zuerst. Gleiche LQI behalten die
bisherige deterministische technische Reihenfolge. Erst danach greift
`MaximumLinks`. Ein endlicher exportierter LQI 0 ist gültig, keine Offline-Regel.

Alle Geräte bleiben auch bei null Treffern vorhanden. `totalLinks` bleibt die
rohe Meldungszahl, `filteredLinks` zählt passende eindeutige Verbindungen vor
dem Anzeigebudget. Additive Metadaten beschreiben Auswahl und effektive Grenzen.
Snapshot, Geräteidentitäten, Richtungen und Farbskala 0–255 bleiben unverändert.
Maximal 500 Rohknoten, 10000 Rohkanten, 1–2000 angezeigte Verbindungen
(Default 300), 16 MiB je Export und 1 MiB je HTML-Ausgabe bleiben verbindlich.
Nur der letzte eindeutige Export wird gelesen; kein Scan und keine Live-LQI-Messung.

## Zentralisierung

`ZigbeeGraphModel::Build` ergänzt optionale Parameter, sodass bisherige
Drei-Parameter-Aufrufer unverändert funktionieren. `IsValidFilter` wird für
gespeicherte Konfiguration und Modell verwendet; Vorschauen rufen denselben
Modellpfad mit vier synthetischen Knoten auf. Sie bleiben ausdrücklich
Beispielnetzwerke und lesen weder Export noch Symcon-Daten. Deren vier Kanten
haben LQI 0, 128, 255 und 128; ein anderer gültiger Bereich kann dort leer sein.
Ungültige Entwürfe zeigen einen Hinweis statt plausibel wirkender Verbindungen.

Der geprüfte Symcon_ModuleHelper enthält allgemeine Formularsichtbarkeit und
SVG-Kodierung, aber keine Zigbee-Auswahl. Diese bleibt projektspezifisch im
vorhandenen `libs/ZigbeeGraphModel.php`. Allgemeiner Graph und Z2M verwenden
weiterhin dieselbe `echarts-graph.js`-Ansichtserhaltung; Filter ändern keine
Geräteidentitäten. Exportleser, Gateway und gepinnte Runtime bleiben unverändert.

## Nachweise und Lücken

Der fokussierte Modelltest begann RED bei der Auswahl der schwächsten Kante.
Modell-/Integrationstests prüfen Standard, Reihenfolge, Dubletten, Gegenrichtung,
inklusive/exakte/leere Bereiche, stabile Gleichstände, ungültige Einstellungen,
nicht endliche LQI, reine Entwürfe sowie beide unabhängigen HTML-Ausgaben.
Maximalmodelle prüfen 500/10000 Rohdatensätze und 2000 angezeigte Verbindungen
in allen drei Modi unter dem 1-MiB-Ausgabebudget. Bestehende N+1- und Export-
Leseschutztests bleiben aktiv. Rendererprüfungen sichern beide Adapter/Layouts,
unveränderte Farben und gültige leere Verbindungsmengen.

`node tests/graph_browser.mjs` bietet unter `/?family=z2m` eine lokale Testseite.
Sie nutzt synthetische Exporte und das echte PHP-Modell. Die Prüfung beider
Layouts und Adapter bestätigt Auswahlwechsel, leere Trefferlisten sowie
Zoomerhalt nach Wechsel und Refresh; die Browserkonsole blieb ohne Warnungen
oder Fehler. Diese Canvas-Frames sind keine tatsächlichen Symcon-/IPSView-Hosts.
Keine laufende Symcon-Instanz,
kein Export und kein Design wird geändert. Funktionale MCP-Abnahme und tatsächliche
Kachel-/IPSView-Prüfung bleiben nach Modulupdate offen; kein Live-Lastnachweis.
