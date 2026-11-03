# ADR 0108: Eindeutige Zustände bei mehreren Wechseln je Sekunde

- Status: Angenommen
- Datum: 2026-10-29
- Ergänzt: ADR 0059 und ADR 0060

## Befund

Die bisherige Gateway-Normalisierung behandelte unterschiedliche Zustände mit
demselben Sekundenzeitstempel immer als ungültig. Die erneute Prüfung des
bekannten Live-Falls zeigt jedoch neun Gruppen in einer Boolean-Quelle:
jeweils genau ein Datensatz mit positiver `Duration` sowie weitere mit Dauer 0.
Das ist mit schnellen Zustandswechseln vereinbar und kein hinreichender
Nachweis korrupter Archivdaten. Die frühere pauschale Einordnung wird korrigiert.

Die offizielle [AC_GetLoggedValues-Dokumentation](https://www.symcon.de/en/service/documentation/module-reference/core-instances/archive-control/ac-getloggedvalues/)
beschreibt `Duration` als die Sekunden, während derer der Datensatz gesetzt war.
Nur die Zeitstempel zu betrachten verwirft diese vorhandene Information.

## Entscheidung

- Die bestehende private Gateway-Normalisierung für `archive.states.read` bleibt
  die gemeinsame Stelle für StateTimeline und CalendarPie-Zustandsdauern.
  Keine parallele familienlokale Interpretation und keine Helper-Kopie.
- Alle Werte und Zeitstempel werden weiterhin vollständig geprüft. Gleiche
  Werte je Zeitstempel dürfen wie bisher zusammenfallen, auch ohne Dauerfeld.
- Bei unterschiedlichen Werten müssen **alle** beteiligten Dauerangaben echte
  nichtnegative Integer sein. Genau **ein** Datensatz muss positive Dauer haben,
  alle anderen müssen Dauer 0 haben. Nur dann bestimmt sein Originalzustand den
  Punkt an diesem unveränderten Zeitstempel. Die Eingangsreihenfolge ist unerheblich.
- Fehlende, negative, als String/Float/Boolean gelieferte Dauer sowie kein oder
  mehr als ein positiver Datensatz bleiben `INVALID_ARCHIVE_DATA`. Es gibt
  keinen allgemeinen First-/Last-wins-Fallback und keine erfundene Untersekunde.
- Dauer-0-Wechsel belegen keinen darstellbaren Zeitabschnitt und keinen
  CalendarPie-Anteil. Die Dauer ersetzt keine Intervallgrenzen; die vorhandene
  halboffene Abschnittsbildung und Tageszerlegung bleiben erhalten.
- Vorgängerabfrage, unbekannte Anfänge, Originalwerte/IDs, einheitliche Grenzen,
  Cache sowie versionierte Anfrage-/Punktformate bleiben kompatibel. Wenn eine
  Reduktion erfolgt, ergänzt das Gateway optional `RawPointCount` mit der
  unveränderten Zahl gelesener Rohzeilen. StateTimeline prüft diese Zahl strikt
  und zählt damit sein gemeinsames Budget; alte Antworten ohne Reduktion/Angabe
  verwenden weiterhin ihre Punktzahl. Die Rohzeilen werden vor jeder Reduktion
  gezählt: 2000 pro Quelle und 16000 insgesamt bei StateTimeline sowie das
  bestehende CalendarPie-Budget bleiben erhalten. Kürzung wird nicht kaschiert.
- Keine Archivbereinigung, keine Neukonfiguration und keine Designänderung.
  Abweichende echte Fehler bleiben sichtbar; es entsteht keine neue Property.

## Nachweise und Grenzen

Die Gateway-Regression begann RED am realen Ablehnungszweig und prüft danach
Boolean-/Integer-Zustände, mehrere Reihenfolgen, unveränderte Rohdaten,
fehlende/ungültige/mehrdeutige Dauern und unveränderte Kürzungskennzeichnung.
Ein weiterer RED-/GREEN-Fall belegt, dass neun Quellen mit jeweils 2000
zusammengefassten Rohzeilen das gemeinsame 16000er-Budget nicht umgehen können.
Modulintegration sichert exakte StateTimeline-Abschnitte einschließlich
Vorgänger/Unbekannt sowie unveränderte CalendarPie-Tagessekunden ab.

Der Live-Fall wird ausschließlich auf der Testinstallation in einer temporären
historischen Ansicht geprüft. Native Renderer und Vorschaugeometrie ändern
sich nicht; daraus wird keine neue Geräte-/Touch-Abnahme abgeleitet.
