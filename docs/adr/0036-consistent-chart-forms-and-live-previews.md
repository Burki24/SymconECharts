# ADR 0036: Einheitliche Diagrammformulare mit Live-Vorschau

- Status: Angenommen
- Datum: 2026-10-11
- Entscheider: Burki24
- Ergänzt: ADR 0006, ADR 0017, ADR 0020 und ADR 0025
- Ersetzt durch: –

## Kontext

Die neun Diagrammgeräte besitzen unterschiedliche Quellen und Designer, aber
denselben grundsätzlichen Bedienablauf. Die Formulare wurden bereits bei
Titel, Quellen, Animation und IPSView-Designvererbung angeglichen. Ohne einen
festen Formularvertrag könnten neue Chartfamilien diese Reihenfolge und
Beschriftung erneut unterschiedlich umsetzen.

Gauge Single, Gauge Multi, Gauge Tacho, Gauge Chronograph und TimeSeries
besitzen bereits eine SVG-Vorschau im Kachel- und IPSView-Designer. Category
Bar, BarHistory, Waterfall Bar und Polar Bar besitzen noch keine
Formularvorschau. Die vorhandenen Vorschauen bilden Diagramme gezielt nach;
sie sind kein pixelidentischer Ersatz für die ECharts-Laufzeitausgabe.

Das allgemeine Konfigurationsformular folgt weiterhin dem zentralen Standard
`CONFIGURATION_FORM.md` in `SymconDevelopment`. Der hier beschlossene Vertrag
betrifft ausschließlich die Diagrammgeräte dieser Library, nicht das
infrastrukturelle `EChartsGateway` ohne Diagramm-Designer.

## Entscheidung

Jedes bestehende und künftige Diagrammgerät erhält ein weitgehend einheitlich
aufgebautes Instanzformular:

1. Gateway-Hinweis, Titel und fachliche Quellen stehen am Anfang. Ein
   ausdrücklich konfigurierter Anzeigename hat Vorrang; Variablen-IDs bleiben
   interne Identitäten und erscheinen nicht automatisch in der Vorschau.
2. Daten-, Bereichs- und Zeiteinstellungen folgen vor den Designern.
   Familienspezifische Felder bleiben dort, wo ihr Zusammenhang verständlich
   ist; identische Feldsätze oder eine universelle Chartinstanz sind nicht
   Ziel dieser Entscheidung.
3. Der Kacheldesigner enthält eine unmittelbar erreichbare Vorschau der
   Kacheldarstellung. Der Abschnitt `IPSView-Design` enthält Ausgabe,
   Hintergrund, Designvererbung und eine eigene Vorschau der effektiven
   IPSView-Darstellung. Bei aktivierter Vererbung zeigt diese das aktuelle
   Kacheldesign, bei unabhängiger Gestaltung dessen eigene Einstellungen.
   Unabhängige Eingaben bleiben im vererbten Modus deaktiviert.
4. Diagnose- und Bedienaktionen stehen getrennt von den persistierenden
   Eingaben am Ende des Formulars.

Eine **Live-Vorschau** reagiert auf noch nicht übernommene Änderungen an
relevanten Titeln, Quellenlisten, Themes und Designfeldern, auch nach dem
Bestätigen einer Listenzeile. Sie benötigt weder `ApplyChanges()` noch eine
Änderung gespeicherter Properties. „Live“ bezeichnet hier die unmittelbare
Reaktion auf Formularbearbeitung, nicht ein dauerhaftes Polling von
Variablen- oder Archivwerten. Aktuelle Werte dürfen begrenzt und lesend
verwendet werden; bei fehlenden oder ungültigen Quellen zeigt das Formular
einen verständlichen Platzhalter statt eines Ladefehlers. Repräsentative
Beispielwerte oder eine Auswahl aus vielen Quellen werden als solche
kenntlich gemacht. Historische Vorschauen führen beim Bearbeiten keine
unbegrenzten Archivabfragen aus.

Die Vorschau macht die wesentlichen sichtbaren Wirkungen der jeweiligen
Einstellungen nachvollziehbar. SVG bleibt der bevorzugte leichte Weg über
den vorhandenen `SVGPreviewHelper`; eine andere Technik braucht einen
belegten Nutzen sowie dieselben Sicherheits- und Laufzeitgrenzen. Dynamische
Texte und Attribute werden escaped, importierte SVGs nur über die bereits
validierten Verträge verwendet. Eine Vorschau muss nicht pixelidentisch mit
dem responsiven ECharts-Renderer sein; Kachel und IPSView werden zusätzlich
real im Browser geprüft.

## Wiederverwendung und Ablage

| Aufgabe | Zuständigkeit |
| --- | --- |
| Laden und Serialisieren von `form.json`, Sichtbarkeit sowie SVG-Escaping, Data-URI und `Image`-Feld | Bereits in den synchronisierten `libs/helper/ConfigurationFormHelper.php` und `libs/helper/SVGPreviewHelper.php`; keine lokale Änderung dieser Kopien. |
| Animationsfelder, IPSView-Vererbung und Hintergrund einschließlich Vorschauparameter | Bereits in `libs/EChartsAnimationDesign.php`, `libs/EChartsIPSViewDesignForm.php` und `libs/EChartsIPSViewBackground.php`; vorhandene Verträge wiederverwenden. |
| Theme-Palette und ID-basierte Auflösung sichtbarer Quellnamen | Bereits in `libs/EChartsAsset.php` und `libs/EChartsSourceIdentity.php`; keine zweite Logik in Vorschauklassen. |
| Lesen des ungespeicherten Formularzustands, Verknüpfen relevanter Änderungsereignisse und Aktualisieren beider Vorschaufelder | Kandidat für einen kleinen projektspezifischen Baustein direkt unter `libs`, sobald Category Bar neben Gauge und TimeSeries denselben stabilen Ein-/Ausgabevertrag belegt. Bestehende `onChange`-Aktionen dürfen nicht überschrieben werden. |
| Gemeinsame SVG-Teile wie Rahmen, Titel oder Legende | Nur nach Vergleich mindestens zweier Bar-Vorschauen und bei tatsächlich gleicher Semantik direkt unter `libs` extrahieren; keine vorsorgliche universelle Zeichenbibliothek. |
| Quellenvalidierung, Wertebildung, Skalen und Diagrammgeometrie | Im zuständigen Gerätemodul und dessen Vorschauklasse belassen. Category, History, Waterfall und Polar besitzen verschiedene fachliche Verträge. |
| Prüfung der Reihenfolge, Vorschaufelder und Änderungsreaktionen aller Diagrammformulare | Gemeinsame Vertrags- und Integrationstests unter `tests`, nicht als Laufzeit-Helper. |

Eine ECharts-spezifische Vorschauverdrahtung gehört nicht in
`Symcon_ModuleHelper`. Dessen bereits abonnierte allgemeinen Formular- und
SVG-Helfer werden unverändert genutzt. Eine neue lokale Abstraktion folgt
erst nach dem Vergleich von Eingaben, Rückfallzuständen und Seiteneffekten
gemäß [ADR 0017](0017-reuse-before-new-chart-development.md).

## Einführung und Prüfung

Der Vertrag ist ein Ziel für alle Diagrammgeräte, keine Behauptung, dass die
vier Bar-Vorschauen bereits vorhanden sind. Zuerst wird Category Bar als
erste Bar-Vertikale mit Kachel- und IPSView-Vorschau ergänzt und die
gemeinsame Vorschauverdrahtung gegen Gauge und TimeSeries geprüft. Danach
folgen BarHistory, Waterfall Bar und Polar Bar mit ihren jeweils eigenen
Geometrien. Bestehende Vorschauen werden auf Reaktion auf ungespeicherte
Listenänderungen, Vererbungswechsel und die Kennzeichnung von Beispieldaten
überprüft. Das infrastrukturelle Gateway benötigt keine Diagrammvorschau.

Für jede Vertikale werden mindestens gültige und leere Quellen, ungespeicherte
Designänderungen, Tile- und IPSView-Vererbung, fehlerhafte Entwürfe,
mehrfache Formularöffnung ohne Seiteneffekt sowie Kachel und IPSView im
Browser geprüft. Ist eine Ausgabeumgebung nicht erreichbar, wird diese
Laufzeitprüfung als Lücke benannt. Struktur- und PHP-Tests allein belegen
keine korrekte visuelle Laufzeitdarstellung. Veröffentlichte Properties,
Idents, GUIDs und gespeicherte Formate bleiben durch diese
Formularentscheidung unverändert.

## Folgen

Anwender finden die grundlegenden Einstellungen und die Vorschau in jeder
Diagrammfamilie an vergleichbarer Stelle. Neue Module übernehmen denselben
Bedienvertrag, ohne fachlich verschiedene Diagramme in ein generisches
Formularschema oder einen gemeinsamen Renderer zu zwingen. Die vier fehlenden
Bar-Vorschauen und gegebenenfalls Lücken bestehender Vorschauen bleiben bis
zur jeweiligen Implementierung ausdrücklich offen.
