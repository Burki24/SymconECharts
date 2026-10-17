# EChartsFunnel

Zeigt aktuelle, nichtnegative Symcon-Variablenwerte als Funnel-Stufen.
Stufen werden manuell benannt und angeordnet. Die Breite richtet sich nach
dem größten aktuellen Wert, nicht nach einer Gesamtsumme. Das Diagramm setzt
weder einen Prozess noch fallende Werte voraus.

## Einrichtung

1. Instanz **ECharts Funnel** anlegen und das vorhandene EChartsGateway wählen.
2. Unter **Funnel-Stufen** 1–16 unterschiedliche Integer-/Float-Variablen derselben
   Einheit auswählen. Optional je Stufe Beschriftung und Farbe festlegen.
3. Reihenfolge über die Quellenliste bestimmen und optional einen Titel setzen.
4. Design einstellen und Änderungen übernehmen.

Die Einheit und Nachkommastellen werden aus der Variablendarstellung
beziehungsweise dem Variablenprofil übernommen. Ohne eigene Beschriftung wird
der Variablenname verwendet. Gleiche sichtbare Namen sind zulässig; die
Quellen bleiben technisch getrennt.

Nullwerte sind erlaubt. Sind alle Werte null, erscheint ein Hinweis statt
einer erfundenen Flächenverteilung. Negative oder ungültige Werte werden
abgewiesen. Eine Archivierung ist nicht erforderlich. Die Instanz liest
Quellen nur und aktualisiert sich bei deren Wertmeldungen.

## Design und Bedienung

Kachel und IPSView bieten Ausrichtung (vertikal/horizontal), Reihenfolge
(Liste/aufsteigend/absteigend), Querausrichtung, Stufenabstand, Beschriftungen
innen/außen, Werteanzeige, Schriftgrößen, Textfarben, Fettschrift, Rahmen,
Deckkraft, Themes, Tooltip und Animation. Standard ist die Listenreihenfolge.
Die Quellfarbe bleibt beim Sortieren erhalten. Der größte Wert erhält standardmäßig die
volle Breite beziehungsweise bei horizontaler Ausrichtung die volle Höhe.
Trapeze verbinden benachbarte Stufen; ihre Flächen sind keine Prozentanteile.

Zusätzlich stehen folgende Einstellungen in beiden Designern zur Verfügung:

- **Minimale/maximale optische Größe:** 0–100 % beziehungsweise 1–100 %;
  die minimale Größe darf die maximale nicht überschreiten. Standard ist
  0/100. Eine Mindestgröße über null verbreitert auch Nullwerte sichtbar.
  Dies ist eine optische Untergrenze, kein zusätzlicher Messwert. Tooltip
  und Datenmodell behalten die Originalwerte; ausschließlich Nullwerte
  zeigen weiterhin den Leerhinweis.
- **Rahmen und Schatten:** durchgezogene, gestrichelte oder gepunktete
  Rahmen; Schattenunschärfe, Farbe und Versatz.
- **Beschriftungen:** lokale Schriftart Arial, Verdana, Georgia oder
  nichtproportional; maximale Textbreite (0 = automatisch) und abschaltbarer
  Überlappungsschutz. Außenbeschriftungen können am Anfangsrand
  (links/oben) oder Endrand (rechts/unten) stehen.
- **Führungslinien:** einzeln schaltbar, Länge, Breite, Linienart, Farbe
  und Deckkraft. Sie gelten nur für Außenbeschriftungen.
- **Hervorhebung:** berührte Stufe hervorheben oder Hover-Effekt abschalten;
  Deckkraft der übrigen Stufen, mit -1 für die automatische ECharts-Abdunklung.

Enge Ausgaben begrenzen Abstände und Beschriftungen; standardmäßig werden
überlappende Texte ausgeblendet. Ohne Überlappungsschutz können sich Texte
absichtlich überdecken. Der Tooltip zeigt weiterhin den vollständigen Namen und Wert.
Die statische Vorschau verwendet bei gültigen Quellen deren aktuelle Werte,
ohne Quellen gekennzeichnete Beispieldaten. Hover und Animation sind dort nicht
nachgebildet. Die endgültige Anordnung passt sich an die Ausgabegröße an.

Unter **IPSView-Design** kann die optionale HTML-Ausgabe aktiviert werden.
Standardmäßig folgt sie dem Kacheldesign. **Kacheldesign übernehmen** erlaubt
anschließend eine unabhängige Gestaltung. Titel und Quellen bleiben gemeinsam.
Die String-Variable mit Ident `IPSViewFunnel` enthält schreibgeschützten
WebContent für das HTML-Widget. Beim Deaktivieren bleibt die Variable erhalten,
wird aber nicht mehr aktualisiert.

## Öffentliche Funktionen

- `ECFU_GetFunnelData(InstanzID)`: aktuelles Diagrammmodell als JSON.
- `ECFU_GetFunnelDiagnostic(InstanzID)`: Konfigurationsgültigkeit und letzter Fehler als JSON.
- `ECFU_GetIPSViewHTML(InstanzID)`: eigenständiges HTML-Dokument.
- `ECFU_CopyTileDesignToIPSView(InstanzID)`: kopiert das Kacheldesign und aktiviert unabhängige Bearbeitung.

Ungültige Quellen, gemischte Einheiten oder ungültige Einstellungen werden
als Instanzstatus angezeigt. Vorübergehende Ladefehler behalten zuletzt
verfügbare Werte mit Hinweis; fachlich ungültige Werte zeigen einen Fehler.
