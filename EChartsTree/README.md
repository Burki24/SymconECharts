# EChartsTree

`EChartsTree` zeigt einen ausgewählten Teil des IP-Symcon-Objektbaums oder
eine eigene JSON-Hierarchie als interaktiven Apache-ECharts-Baum in einer nativen Kachel oder einem optionalen
IPSView-HTML-Widget. Benötigt werden IP-Symcon 9.0/9.1 und ein verbundenes,
aktives `EChartsGateway`. Ein Archiv ist nicht erforderlich.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartstree).

## Einrichtung und Darstellung

Die **Datenquelle des Baums** ist standardmäßig **Symcon-Objektbaum**.
Bestehende Instanzen behalten damit ihre bisherige Konfiguration. Für eine
eigene Netzwerk- oder Gerätehierarchie wählen Sie **Gespeichertes JSON** oder
**JSON-Stringvariable**; die vorhandenen Designer gelten auch für diese Quellen.

Für den Symcon-Objektbaum gilt:

Wählen Sie als Wurzel eine Kategorie oder Instanz. Für den gesamten Objektbaum
aktivieren Sie stattdessen „Symcon-Wurzel verwenden“; das Objekt-Auswahlfeld
wird dann deaktiviert. Eine dort zuvor gewählte Kategorie oder Instanz bleibt
gespeichert und wird beim Ausschalten der Option wieder verwendet. Ohne diese
Option und ohne ausgewähltes Objekt bleibt die Instanz unkonfiguriert.
Unter der aktiven Wurzel werden Kategorien, Instanzen und Variablen in der
Symcon-Reihenfolge aufgenommen; Skripte, Links,
Medien und andere Objekttypen bleiben außen vor. Ausgeblendete Objekte und
deren Zweige erscheinen standardmäßig nicht. „Ausgeblendete Objekte
einbeziehen“ hebt diesen Filter auf. Maximale Tiefe und Knotenzahl begrenzen
die Darstellung auf 1–12 Nachkommensebenen beziehungsweise 10–3000 Knoten.
Die gewählte Wurzel zählt als Ebene 0. Bestehende Instanzen behalten ihre
gespeicherten Grenzen; der Standard bleibt drei Nachkommensebenen und 80 Knoten.
Wenn die Knotengrenze weitere passende Objekte abschneidet, zeigt das
Diagramm eine Meldung. Beide Ausgabewege verwenden dieselbe Wurzel und
dieselben Grenzen.

Mit „Variablenname enthält“ lässt sich der Baum auf Variablen mit einem
bestimmten Namensbestandteil einschränken, beispielsweise „Batterie“. Die
Suche unterscheidet nicht zwischen Groß- und Kleinschreibung. Sichtbar bleiben
die Treffer sowie ihre übergeordneten Kategorien und Instanzen; andere
Variablen und leere Zweige entfallen. Bei keinem Treffer bleibt nur die
gewählte Wurzel sichtbar. Ein leeres Feld zeigt den bisherigen vollständigen
Teilbaum. Der Filter respektiert Tiefe und ausgeblendete Objekte. Die
Knotengrenze gilt für das gefilterte Ergebnis, nicht für die zuerst
durchsuchten Objekte. Für sehr große Hierarchien ist der Suchdurchlauf auf
30 000 Objekte begrenzt; wird er überschritten, wählen Sie eine engere Wurzel
oder geringere Tiefe.

Jeder Knoten trägt seinen aktuellen Symcon-Namen. Bei Variablen zeigt der
Tooltip zusätzlich den formatierten aktuellen Wert; dieser kann optional auch
an der Beschriftung stehen. Gleiche Variablennamen werden nicht
zusammengeführt. Änderungen an Namen, Struktur und Werten werden spätestens
mit der nächsten Aktualisierung nach 60 Sekunden sichtbar. Die Abfrage liest
Objekte und Werte ausschließlich; sie verändert die ausgewählte Hierarchie
nicht.

Für eine Bewertung ausgewählter Zustandsvariablen mit Warn- und Kritisch-Schwellen
verwenden Sie das separate Modul [`EChartsTreeSystemHealth`](../EChartsTreeSystemHealth/README.md).

Der Kacheldesigner bietet orthogonale oder radiale Anordnung. Für orthogonale
Bäume sind vier Richtungen verfügbar, für die Verbindungslinien Kurven oder
Polylinien. Anfangs geöffnete Ebenen, Verschieben/Zoomen, Knotengröße,
Beschriftung, Farben, Theme und Animation lassen sich einstellen. Knoten
können in der Laufzeitansicht per Klick auf- und zugeklappt werden.
Anfänglich geöffnete Ebenen (0–12) sind unabhängig von der maximal geladenen
Tiefe: Eingeklappte Zweige können später geöffnet werden, ohne die Auswahl
zu verändern. Geöffnete Zweige und Pan/Zoom bleiben bei Aktualisierung und
Größenänderung erhalten; eine neue Wurzel, Anordnung oder Anfangstiefe setzt
die Ansicht zurück. Lange Namen bleiben vollständig im Tooltip erreichbar.
Der unabhängige IPSView-Designer besitzt dieselben Optionen; standardmäßig
übernimmt er das Kacheldesign. Beide Designer zeigen eine unmittelbar auf
Formularänderungen reagierende schematische Vorschau. Ohne gültige Objektbaum-Wurzel
verwendet diese im Objektmodus ausdrücklich eine Beispielstruktur. Ungültige
JSON-Entwürfe zeigen stattdessen eine Fehlermeldung.
Die Vorschau zeigt echte Eltern-Kind-Verbindungen mit höchstens 80 Knoten
und berücksichtigt die anfänglich geöffneten Ebenen. Sie ist eine begrenzte
Struktur-/Designvorschau, keine vollständige Zustands- oder Objektübersicht.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren die
erzeugte Variable in einem HTML-Widget. Das WebContent-Dokument wird bei
Datenaktualisierungen nicht neu geladen. Der Baum und das Design werden im
bestehenden Diagramm aktualisiert.

## Konfigurationsreferenz und Skriptzugriff

### Eigene Hierarchie als JSON

1. **Gespeichertes JSON (Datei, Text oder Skript)** als Datenquelle wählen.
2. Das [Netzwerkbeispiel](../docs/examples/tree-network-1.0.json) in das Feld
   **JSON-Dokument des Baums** kopieren oder unter **Baum-JSON prüfen oder
   übernehmen** eine `.json`-Datei auswählen.
3. **JSON-Text prüfen** beziehungsweise die Dateiprüfung ausführen. Bei Bedarf
   lokale Objektbindungen ausdrücklich freigeben und die aufgelösten IDs/Namen prüfen.
4. Eine gültige Datei mit **Geprüfte Datei in Entwurf übernehmen** kopieren.
   Auswahl und Prüfung speichern noch nichts. Erst **Anwenden** speichert den Entwurf.

#### So muss die JSON-Datei aufgebaut sein

Die Datei beschreibt einen Baum: oben steht genau ein Startpunkt (die
**Wurzel**), darunter stehen Geräte, Gruppen oder Werte. Jeder Eintrag heißt
**Knoten**. Alle Knoten stehen gemeinsam in der Liste `nodes`; ihre Verbindung
entsteht durch `parentId`, nicht durch verschachtelte Listen.

Dieses vollständige Beispiel können Sie als `mein-baum.json` speichern und
direkt importieren. Es benötigt keine vorhandenen Symcon-Objekte:

```json
{
  "format": "de.burki24.symconecharts.tree",
  "formatVersion": "1.0",
  "rootId": "netzwerk",
  "nodes": [
    { "id": "netzwerk", "parentId": null, "name": "Mein Netzwerk", "kind": "category" },
    { "id": "router", "parentId": "netzwerk", "name": "Router", "kind": "instance" },
    { "id": "switch-buero", "parentId": "router", "name": "Switch Büro", "kind": "instance" },
    { "id": "verbindung", "parentId": "switch-buero", "name": "Verbindung", "kind": "variable", "displayValue": "1 Gbit/s" }
  ]
}
```

Damit entsteht die Folge **Mein Netzwerk → Router → Switch Büro → Verbindung**.
Die Datei besteht aus vier Pflichtfeldern:

| Feld | Einfach erklärt |
| --- | --- |
| `format` | Immer genau `"de.burki24.symconecharts.tree"` übernehmen. |
| `formatVersion` | Immer den Text `"1.0"` einschließlich Anführungszeichen verwenden. |
| `rootId` | Die `id` des obersten Knotens, hier `"netzwerk"`. |
| `nodes` | Die Liste aller Knoten einschließlich der Wurzel. |

Jeder Knoten benötigt diese vier Angaben:

| Feld | Einfach erklärt |
| --- | --- |
| `id` | Selbst gewählte, eindeutige Textkennung, etwa `"switch-buero"`. Keine Symcon-Objekt-ID. Bei Aktualisierungen beibehalten. |
| `parentId` | Die `id` des direkt übergeordneten Knotens. Nur die Wurzel erhält `null` ohne Anführungszeichen. |
| `name` | Der sichtbare Name; Umlaute und Leerzeichen sind erlaubt. |
| `kind` | Darstellungsrolle: `"category"` für Gruppen, `"instance"` für Geräte oder `"variable"` für Werte. Dadurch werden keine Symcon-Objekte angelegt. |

Optional ergänzt `displayValue` einen festen Werttext wie `"1 Gbit/s"`.
Er steht im Tooltip; **Werte anzeigen** blendet ihn auch an der Beschriftung ein.
Statt eines festen Textes kann `objectId` ein vorhandenes lokales Symcon-Objekt
binden. Dafür müssen lokale Objektbindungen ausdrücklich freigegeben sein;
`objectId` und `displayValue` dürfen nie gemeinsam an einem Knoten stehen.
Für den Einstieg genügt das Beispiel ohne Objektbindungen.

Beim Erstellen eigener Dateien beachten:

- Als **UTF-8 ohne BOM** mit der Endung `.json` speichern. JSON verwendet doppelte
  Anführungszeichen; Kommentare und ein zusätzliches Komma nach dem letzten
  Listeneintrag sind ungültig.
- Jede `id` darf nur einmal vorkommen. Sie beginnt mit einem Buchstaben oder
  einer Ziffer und enthält nur `A–Z`, `a–z`, `0–9`, Punkt, Unterstrich,
  Doppelpunkt oder Bindestrich; maximal 128 Zeichen. Groß-/Kleinschreibung zählt.
- Genau ein Knoten hat `parentId: null`; seine `id` muss zu `rootId` passen.
  Jede andere `parentId` muss auf einen vorhandenen Knoten zeigen. Ein Knoten
  darf weder sein eigener Elternknoten sein noch über andere Knoten zu sich
  selbst zurückführen.
- Gleiche sichtbare Namen sind erlaubt, gleiche IDs nicht. Geschwister werden
  in ihrer Reihenfolge innerhalb von `nodes` dargestellt. Namen dürfen bis zu
  120, feste Werttexte bis zu 100 Zeichen lang sein; Steuerzeichen sind unzulässig.
- Nur die beschriebenen Felder verwenden. Die vollständige Datei darf höchstens
  1 MiB, 3000 Knoten und zwölf Ebenen unterhalb der Wurzel enthalten. Die
  eingestellten Ansichtsgrenzen können weniger Knoten laden.

Für ein weiteres Gerät ergänzen Sie einen neuen Eintrag in `nodes`, vergeben
eine neue `id` und tragen bei `parentId` beispielsweise `"router"` ein. So
erscheint das Gerät neben dem Switch unter demselben Router. Knoten und
Werttexte sind Daten, keine ausführbaren ECharts-Einstellungen.

Der [vollständige JSON-Vertrag 1.0](../docs/TREE_JSON_CONTRACT.md) beschreibt
Felder, lokale `objectId`-Bindungen und Fehlermeldungen. Für eigene
UniFi-Topologien erstellt ein eigenes Skript das Dokument; das Modul verbindet
sich nicht selbst mit dem UniFi-Controller. Ein Gerät mit mehreren Eltern
oder freien Querverbindungen benötigt das Graph-Modell.

Für automatische Aktualisierungen wählen Sie **JSON-Stringvariable** und eine
bestehende Stringvariable. Ein Producer schreibt jeweils das vollständige
Dokument in diese eigene Variable. Änderungen aktualisieren den Baum ohne
Anwenden seiner Konfiguration; schnelle Folgewerte werden zusammengefasst.
Das Modul schreibt niemals in die Quellvariable.

Ungültige Aktualisierungen zeigen während derselben Modul-Laufzeit den letzten
gültigen Stand mit einem dauerhaften Veraltet-Hinweis und dessen ursprünglichem
Zeitpunkt. Ohne gültigen Stand, insbesondere nach einem Neustart mit ungültiger
Quelle, erscheint ein Fehler. Ein gültiger Folgeinhalt behebt ihn automatisch.
JSON-Eingang, Konfiguration und Ausgaben haben zusätzlich Bytebudgets; die
bisherigen zwölf Nachkommensebenen, maximal 3000 Knoten, Default 3/80 und
Vorschaugrenze von 80 bleiben erhalten. Ansichtsbegrenzungen überspringen keine
Eingangsprüfung. Objektbaumfilter bleiben im JSON-Modus gespeichert, aber inaktiv.

Die [Feldreferenz](../docs/KONFIGURATION.md#echartstree) enthält
Formularbeschriftungen, technische Namen, Typen, Standardwerte und Auswahllisten.
Für Skriptänderungen gelten die dort erläuterten JSON- und Anwenderegeln.

### Ausgabevariable

| Ident | Typ / Darstellung | Bedeutung | Einheit | Bedienbar |
| --- | --- | --- | --- | --- |
| `IPSViewTree` | String / WebContent | HTML-Dokument für das optionale IPSView-Widget | keine | Nein |

Die Variable entsteht bei aktivierter IPSView-Ausgabe. Nach deren Abschalten
bleibt sie erhalten, wird aber nicht weiter aktualisiert. Die ausdrückliche
Löschaktion im Formular entfernt sie samt ihrer bisherigen Verknüpfbarkeit.
Quellvariablen und deren Archive bleiben unverändert. Weitere Statusvariablen
werden für das reguläre Diagramm nicht angelegt.

### Öffentliche PHP-Funktionen

`$InstanzID` bezeichnet immer diese Modulinstanz, nicht eine Quellvariable
oder das Gateway. Alle aufgeführten Argumente beim Symcon-Aufruf übergeben.

| Signatur | Rückgabe und Wirkung |
| --- | --- |
| `ECTR_CopyTileDesignToIPSView(int $InstanzID): void` | Kopiert das gespeicherte Kacheldesign, ersetzt die unabhängigen IPSView-Designwerte und wendet sie an; aktiviert unabhängiges Design. |
| `ECTR_GetTreeData(int $InstanzID): string` | Liest das reguläre konfigurierte Diagrammmodell als JSON; keine Änderung von Quellen oder Archivwerten. |
| `ECTR_GetTreeDiagnostic(int $InstanzID): string` | JSON-Momentaufnahme der Konfigurationsprüfung und letzten Fehlermeldung; kein Nachweis einer erfolgreichen Datenabfrage. |
| `ECTR_GetIPSViewHTML(int $InstanzID): string` | Vollständiges HTML-Dokument für IPSView; der Rückgabewert bestätigt keine Anzeige in einem Client. |
| `ECTR_RefreshTree(int $InstanzID): void` | Aktualisiert die Diagrammausgaben aus den konfigurierten Quellen. Kein Erfolgswert und keine Änderung von Quellen/Archiven. |
| `ECTR_ValidateTreeJSON(int $InstanzID, string $JSON): string` | Prüft das rohe JSON lesend gegen Vertrag, Grenzen und Bindungsfreigabe. JSON-Bericht mit `valid`, Fehlern, geladenen/gesamten Knoten und Dokumenthash. |
| `ECTR_SetTreeJSON(int $InstanzID, string $JSON): string` | Übernimmt ausschließlich im Modus `json` das geprüfte Dokument. JSON-Antwort mit `applied`, `unchanged` oder `rejected`. Ausstehende Konfigurationsänderungen verhindern die Übernahme. |

Für Skriptübernahmen muss die Instanz bereits **Gespeichertes JSON** verwenden.
`$JSON` ist der rohe Dokumenttext, nicht Base64. Ein identischer kanonischer
Inhalt wird nicht erneut angewandt. Die Funktion verändert nur `JsonDocument`;
Design, Quellenwahl und fremde Objekte bleiben erhalten. Der Prüfbericht
benennt Fehlerklasse und JSON-Pointer. Nach einem nicht eindeutig bestätigten
Speicherfehler wird eine `PERSISTENCE_FAILED`-Ausnahme gemeldet; dann vor einer
Wiederholung aktive und geplante Konfiguration prüfen. Bei häufigen
Aktualisierungen ist die Stringvariable der passendere Eingabeweg.
Die Kernel-Getter lesen nur aktive Werte. Der Setter prüft deshalb vor dem
Vormerken auf ausstehende Änderungen und nach dem Anwenden die gesamte aktive
Konfiguration. Gleichzeitige Formular- oder Skriptänderungen an derselben Instanz
mit dem Producer abstimmen; die Settersperre schützt nur weitere Setter-Aufrufe.

Die Formularfunktionen `ECTR_TreeJSONFromForm(int $InstanzID, string $FileData,
string $Document, string $Configuration, bool $TakeOver): string` und
`ECTR_UpdateTreeJSONSourceAvailability(int $InstanzID, string $SourceMode,
bool $UseSymconRoot): void` bedienen ausschließlich den Formularentwurf.
`ECTR_RefreshTreeJSON(int $InstanzID): void` ist der begrenzte
Aktualisierungs-Timercallback für JSON-Quellen.

Beispiel mit synthetischer Instanz-ID (durch die eigene ID ersetzen):

```php
$instanceID = 12000;
$model = json_decode(ECTR_GetTreeData($instanceID), true, 512, JSON_THROW_ON_ERROR);
```

Die fachlichen Grenzen dieser Anleitung gelten auch bei Skriptaufrufen.
Ungültige Konfigurationen, fehlende Quellen oder ein nicht verfügbares Gateway
können eine Ausnahme beziehungsweise einen ausdrücklich nicht verfügbaren
Zustand liefern. Solche Rückgaben nicht als leeren oder mit null aufgefüllten
Messbestand auswerten. Archivabfragen können den kurzzeitigen Gateway-Cache
nutzen; ein Aufruf garantiert keinen neu protokollierten Messwert.

## Lizenz

Die eigenen Beiträge stehen unter der
[PolyForm Noncommercial License 1.0.0](../LICENSE).
Für Fremdkomponenten gelten deren [Originallizenzen](../THIRD_PARTY_NOTICES.md).
