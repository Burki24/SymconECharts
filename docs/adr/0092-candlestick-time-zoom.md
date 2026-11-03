# ADR 0092: Optionaler Candlestick-Zeitzoom

- Status: Angenommen
- Datum: 2026-10-22
- Ergänzt: ADR 0056 und 0079

## Vertrag

`EnableZoom` und `IPSViewEnableZoom` sind additive Boolean-Designproperties
mit Default false. Übernahme des Kacheldesigns, unabhängige Designer,
Designkopie und reine Entwurfsvorschauen folgen den vorhandenen Verträgen.
Keine Migration, Instanzänderung oder Archiv-Neukonfiguration ist erforderlich.
Das additive Modellfeld `candlestick.variableID` erhält die technische
Quellenidentität auch bei gleichen oder geänderten Anzeigenamen.

Native Slider- und Inside-DataZoom-Komponenten zeigen ausschließlich einen
Ausschnitt der vollständig geladenen Kategorien. `filterMode=none` erhält
alle Rohwertkerzen, Lücken und die vollständige Werteachse. Der Regler steht
bei vertikalen Kerzen unten, bei horizontalen rechts. Umgekehrte Zeitachsen
gelten auch für den Mausanker; die Werteachse wird nicht gezoomt.
Rohwert-Endpunkte, 720 Intervalle, 63 Abfragen und 64000 Rohpunkte bleiben
unverändert. Keine zusätzliche Archivabfrage durch Interaktion.

## Gemeinsame Logik und flüchtiger Zustand

`libs/echarts-zoom.js` ergänzt `categoryTimeOptions` ohne Änderungen an den
bisherigen Filter-/Optionsverträgen. `capture`/`apply` und `zoomWindow` werden
wiederverwendet. `attachIPSViewWheel` erhält optional eine Achsenrichtung;
vorhandene Aufrufer behalten ihren bisherigen Vertrag. Nur beim neuen
richtungsbezogenen Aufruf wird die tatsächliche Gridfläche als Hit-Test und
Mausanker verwendet. Native Mausradverarbeitung ist hier ausgeschaltet, um
doppelte Zoomaktionen zu vermeiden; Inside behält Drag- und Pinchbedienung.
Generische Event-/Frame-Normalisierung bleibt beim unveränderten zentralen
Helper. Keine Event-Sperre über fremden Host-Dokumenten.

Der relative Prozentbereich bleibt bei kompatiblen Aktualisierungen,
Resize, vorübergehenden Ladefehlern und Themewechsel erhalten. Quellen-ID,
Intervall, Zeitraumvorgabe, Custom-Dauer und feste angefragte Grenzen bilden
den Kompatibilitätsschlüssel; nicht überlappende, leere, fehlerhafte oder
deaktivierte Modelle verwerfen den Ausschnitt. Kein persistierter Zoom und
keine veränderte Konfiguration. Nach Seitenneuladen ist alles sichtbar.

Nur Kerzenbreiten werden nach einer Zoomaktion an die sichtbare Slotzahl
angepasst. ECharts 6 kann bei automatischer Außenrandkorrektur während eines
ungefilterten Kategoriezooms das Grid verschieben. Ausschließlich bei aktivem
Zoom ist deshalb `outerBoundsMode=none` gesetzt; die bereits berechneten
Titel-/Achsen-/Reglerabstände bleiben maßgeblich. Legacy-Layout bleibt erhalten.

## Fremdkomponenten und Prüfung

ECharts bleibt gepinnt bei 6.1.0 / Apache-2.0. Ausschließlich der Candlestick-
Build ergänzt offizielle DataZoom-Komponenten; Herkunft, Integrität und
Lieferartefakt stehen in Runtime-README und THIRD_PARTY_NOTICES.

PHP-Integration prüft Defaults, getrennte Designer, reine SVG-Entwürfe,
Quellenidentität, gemeinsame Daten/Caches und Ausgabelimits. JavaScript prüft
den ausgelieferten Build mit nativem SVG-Renderer, beide Ausrichtungen und
inverse Achsen, Wheel-Zoom, Lücken, stabile Skalen/Grids, Kerzenbreiten,
Update/Resize, Fehler und Abschalten. Der native SVG-Renderer wird nur im
Test zusätzlich aus derselben gepinnten Runtime registriert.

Lokale verschachtelte Browser-Fixtures prüfen echte Mausradaktionen in beide
Richtungen und Ansichtserhalt. Die anschließende Live-Abnahme im echten Symcon-Host und tatsächlichen
IPSView-Client ist durch den Eigentümer einschließlich Mausrad bestätigt.
Echte Touchgeräte wurden nicht geprüft. Bestehende Instanzen, Designs,
Variablenwerte und Archive wurden nicht geändert.

Im automatisierten Frame-Browserprotokoll trat außerdem ein unspezifischer
`MutationObserver.observe`-Hinweis ohne Quellort auf; die direkte Ausgabe
blieb ohne Warnungen/Fehler. In Candlestick, Zoom-/Designbasis, Interaktionshelper
und den eingebundenen ECharts-/ZRender-Quellen existiert kein solcher Aufruf.
Die Quelle ist nicht abschließend zugeordnet; die funktionalen Frame-Prüfungen
bestanden trotzdem. Kein ungeprüfter Produktfix oder pauschaler Event-Workaround.
