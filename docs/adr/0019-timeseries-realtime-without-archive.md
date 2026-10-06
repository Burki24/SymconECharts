# ADR 0019: Echtzeit-Zeitreihen ohne Archiv ermöglichen

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ergänzt: ADR 0018
- Ersetzt durch: –

## Kontext

ADR 0018 trennt Rohwerte und aggregierte Archivwerte eindeutig. Anwender
benötigen zusätzlich eine Zeitreihendarstellung für numerische Variablen, deren
Archivierung nicht aktiviert ist. Ein stiller Rückfall des Modus `raw` auf
Momentanwerte würde dessen zugesicherte Archivsemantik verletzen und könnte
eine vermeintlich vollständige Historie vortäuschen.

## Entscheidung

`EChartsTimeSeries` erhält den zusätzlichen Datenmodus `realtime`. Er ist von
`raw` und allen Aggregationsmodi getrennt:

- Beim Öffnen beziehungsweise Neuaufbau liest das Modul genau den aktuellen
  Wert über die vorhandene Gateway-Operation `current.read` und setzt ihn zum
  Beginn der lokalen Beobachtung als ersten Punkt.
- Anschließende `VM_UPDATE`-Ereignisse werden als neuer Punkt an die geöffnete
  native Kachel übertragen. Bei gleichem Zeitstempel wird der letzte Punkt
  ersetzt.
- Der Browser entfernt Punkte außerhalb des gewählten rollenden Zeitraums und
  begrenzt jede Reihe zusätzlich auf ihr wirksames Punktbudget.
- `archive.read`, Archive Control und Archivlogging werden im Echtzeitmodus
  nicht verwendet. Der Modus funktioniert deshalb für archivierte und nicht
  archivierte numerische Variablen gleichermaßen.
- Die gesammelten Punkte werden nicht im Modul persistiert. Nach dem Neuladen
  beginnt die Darstellung wieder mit dem dann aktuellen Wert. Die Oberfläche
  bezeichnet den Modus ausdrücklich als Echtzeitdarstellung ohne benötigtes
  Archiv und verspricht keine rückwirkende Historie.

Der Modus `raw` bleibt unverändert eine Abfrage von `AC_GetLoggedValues()` und
liefert bei einer nicht archivierten Quelle weiterhin den strukturierten
Gateway-Fehler. Automatische und feste Verdichtungsmodi bleiben ebenfalls
reine Archivpfade.

## Folgen

Nicht archivierte Sensoren können ohne Seiteneffekt sofort als laufende Kurve
dargestellt werden. Eine belastbare Historie über Seitenneuladungen oder
Symcon-Neustarts erfordert weiterhin eine aktivierte Archivierung. Das Modul
legt keinen eigenen Ersatzspeicher an und aktiviert das Symcon-Archiv niemals
selbstständig.
