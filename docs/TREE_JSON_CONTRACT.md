# EChartsTree: JSON-Vertrag 1.0

**Stand: implementiert; MCP-Abnahme unter 1.403/1.404 sowie funktionale
Eigentümerabnahme in Kachel und IPSView abgeschlossen (05.11.2026).**
Die folgenden Properties, Funktionen und Eingabewege gelten für Tree-JSON 1.0.
Die bisherige Objektbaumfunktion bleibt unverändert. Architekturentscheidung:
[ADR 0113](adr/0113-tree-json-data-source.md).

## 1. Modul und Zweck

Die Funktion bleibt Teil von `EChartsTree`. Kachel, IPSView und Vorschau
verwenden dieselbe validierte Hierarchie mit den vorhandenen Designoptionen.
Ein neues Gerätemodul, ein anderer Modulname oder ein neues Präfix entsteht nicht.

Das Format eignet sich beispielsweise für eine von einem Skript ermittelte
Netzwerkhierarchie. Jedes Gerät hat genau einen Elternknoten. Alternative
Uplinks, redundante Pfade oder freie Querverbindungen sind kein Baum und
gehören zum Graph-Modell. Ein eigener UniFi-Client ist nicht Bestandteil
dieses Vertrags; das liefernde Skript übernimmt die Datenbeschaffung.

## 2. Datenquelle und Kompatibilität

Die folgenden neuen Properties sind additiv registriert; bestehende Properties
werden weder umbenannt noch umgeschrieben.

| Property | Typ / Default | Vertrag |
| --- | --- | --- |
| `SourceMode` | String / `objects` | `objects`, `json` oder `variable`; genau eine aktive Quelle. |
| `JsonDocument` | String / leer | Geprüftes, gespeichertes JSON für `json`. |
| `JsonVariableID` | Integer / `0` | Bestehende Stringvariable für `variable`; keine Namens-/Pfadsuche. |
| `UseJsonObjectBindings` | Boolean / `false` | Explizite Freigabe optionaler lokaler `objectId`-Bindungen. |

`objects` behält Wurzelwahl, `UseSymconRoot`, Reihenfolge, `IncludeHidden`,
`VariableNameFilter`, Statusregeln und den bisherigen Suchlauf. Die neuen
Eingaben werden in diesem Modus nicht ausgewertet. Umgekehrt bleiben
`RootObjectID`, `UseSymconRoot`, `IncludeHidden` und `VariableNameFilter` im
JSON-Modus gespeichert, aber ohne Wirkung und im Formular deaktiviert.
Eine Rückkehr zu `objects` stellt keine früheren Werte auf Defaults zurück.

`MaxDepth`, `MaxNodes` und sämtliche Designwerte gelten in allen Modi.
Anfangstiefe und Datenumfang bleiben getrennt. `EChartsTreeSystemHealth`
behält ausschließlich seinen bisherigen Objekt- und Zustandsvertrag.

## 3. Dokumentformat

[JSON-Schema](schemas/tree-data-1.0.schema.json) und
[vollständiges synthetisches Beispiel](examples/tree-network-1.0.json).
Die folgende kleine Hierarchie ist auf jeder Installation ohne Objektbindung gültig:

```json
{
  "format": "de.burki24.symconecharts.tree",
  "formatVersion": "1.0",
  "rootId": "network",
  "nodes": [
    { "id": "network", "parentId": null, "name": "Mein Netzwerk", "kind": "category" },
    { "id": "gateway", "parentId": "network", "name": "Gateway", "kind": "instance" },
    { "id": "switch-office", "parentId": "gateway", "name": "Switch Büro", "kind": "instance" }
  ]
}
```

### Dokumentfelder

| Feld | Pflicht | Bedeutung |
| --- | --- | --- |
| `format` | Ja | Exakt `de.burki24.symconecharts.tree`. |
| `formatVersion` | Ja | Exakt der String `1.0`; unbekannte Versionen werden abgewiesen. |
| `rootId` | Ja | ID des einzigen Knotens mit `parentId: null`. |
| `nodes` | Ja | Geordnete Liste mit 1–3000 Knoten einschließlich Wurzel. |

### Knotenfelder

| Feld | Pflicht | Bedeutung |
| --- | --- | --- |
| `id` | Ja | 1–128 ASCII-Zeichen gemäß `[A-Za-z0-9][A-Za-z0-9._:-]{0,127}`; dokumentweit eindeutig und groß-/kleinschreibungssensitiv. |
| `parentId` | Ja | `null` für die Wurzel, sonst die vorhandene ID genau eines Elternknotens. |
| `name` | Ja | Nicht nur Leerraum; 1–120 Unicode-Codepoints, keine Steuerzeichen. |
| `kind` | Ja | `category`, `instance` oder `variable`; nutzt die bestehenden drei Farb-/Darstellungsrollen. |
| `displayValue` | Nein | Statischer Klartext mit höchstens 100 Unicode-Codepoints, ohne Steuerzeichen; leer ist zulässig. |
| `objectId` | Nein | Positive lokale Symcon-Objekt-ID; nur mit expliziter Bindungsfreigabe gemäß Abschnitt 5. |

Die Rollen bezeichnen bei ungebundenen JSON-Knoten nur die Darstellung;
sie erzeugen keine Symcon-Objekte. Statische Werte dürfen auch an einem
Geräteknoten stehen. `ShowValues` bestimmt wie bisher die Beschriftung;
vorhandener Werttext bleibt im Tooltip verfügbar. `displayValue` und `objectId`
sind gegenseitig ausgeschlossen. Nicht gesetzte Werte werden nicht als `0`
oder leerer Messwert erfunden. Unbekannte Felder werden auf jeder Ebene
abgewiesen, damit Tippfehler keine scheinbar erfolgreiche Übernahme ergeben.

Alle Namen und Werttexte sind inerte Daten, auch wenn sie HTML, Markdown oder
Ausdruckszeichen enthalten. Sie werden für HTML/SVG/JavaScript passend
serialisiert beziehungsweise escaped und niemals ausgeführt. Layout, Farben,
Icons, Bild-URLs, ECharts-Optionsobjekte, Actions und Templates gehören nicht
in dieses Format. Ein Text wie `<script>` bleibt sichtbarer Klartext.

## 4. Struktur, Reihenfolge und Identität

Vor jeder Projektion muss das gesamte Dokument bestehen:

1. Genau eine Wurzel, passend zu `rootId`; alle übrigen Knoten haben einen Elternbezug.
2. Eindeutige IDs, vorhandene Eltern, keine Selbstbezüge und keine Kreise,
   auch nicht in einem von der Wurzel getrennten Teil.
3. Jeder Knoten ist von der Wurzel erreichbar. Mehrere Wurzeln, Waisen und
   Mehrfach-Eltern werden nicht repariert oder durch neue Wurzeln verbunden.
4. Höchstens zwölf Nachkommensebenen ab Wurzel 0. Ein Pfad darf somit
   dreizehn Knoten enthalten. Größere Eingaben werden vollständig abgewiesen.

Eltern dürfen in `nodes` nach ihren Kindern stehen. Die relative Reihenfolge
der Geschwister entspricht ihrer Reihenfolge in `nodes`; es wird nicht nach
Name, ID oder Objektposition sortiert. Die Ausgabe wird in dieser Reihenfolge
tiefenorientiert begrenzt, sodass jeder ausgegebene Kindknoten seinen Elternknoten hat.

JSON-IDs sind technische Identitäten von Knoten-Vorkommen. Gleiche Namen
bleiben getrennt. Ein Umbenennen oder Umhängen bei gleichbleibender ID erzeugt
keine neue Identität; gelöschte Knoten verlieren ihren Aufklappzustand.
Der Renderer verwendet einen kollisionsfreien Namensraum getrennt von
`object-<ID>` und synthetischen Vorschauknoten. Quellenmodus, Variablen-ID,
Wurzel-ID und die bestehenden Layout-/Anfangstiefenmerkmale bestimmen den
Ansichtskontext. Ein Wechsel dieses Kontexts setzt Aufklappen und Pan/Zoom
zurück. Inhaltsaktualisierungen derselben Quelle erhalten diese Ansichten
für fortbestehende IDs wie bisher.

## 5. Optionale lokale Symcon-Bindungen

Ohne `UseJsonObjectBindings` ist jedes `objectId` ein Fehler, kein ignorierter
Hinweis. Bei Freigabe muss das Objekt existieren und zur Rolle passen:
Kategorie für `category`, Instanz für `instance`, Variable für `variable`.
Das JSON liefert die Elternstruktur und den Anzeigenamen; der Objektbaum
wird weder durchlaufen noch verändert. Bei einer Variablenbindung stammt
der Wert ausschließlich aus dem aktuellen `GetValueFormatted`-Vertrag.
Seine Anzeige folgt der bisherigen Grenze von 100 Zeichen; die Quellwerte
selbst werden dadurch nicht verändert.
Native Darstellungen und kompatible Legacy-Profile bleiben dabei unverändert.
Keine Profile, Darstellungen, Actions oder Archivierungen werden geschrieben.

Mehrere JSON-Knoten dürfen dasselbe Objekt binden, weil sie verschiedene
Vorkommen darstellen. Objekt-/Variablenlesungen und Abonnements werden nach
Symcon-ID dedupliziert; Namen oder Pfade sind keine Ersatzschlüssel.
Links werden nicht aufgelöst, Skripte/Medien nicht gebunden und Objektaktionen
nicht ausgeführt. Die JSON-Quellvariable sowie eigene Tree-Ausgabevariablen
dürfen nicht zugleich gebundene Wertquellen sein.

ObjectIDs sind ausschließlich installationslokal. Der Datei-/Text-Prüfbericht
zeigt deshalb bei Bindungen die aufgelösten lokalen Objekte vor der Übernahme;
es gibt kein automatisches Mapping anhand eines Namens. Dokumente mit fremden
IDs benötigen eine ausdrückliche Anpassung durch den Anwender. Im
Stringquellenmodus umfasst die aktive Bindungsfreigabe die nachfolgenden
gültigen lokalen Bindungen dieses Datenlieferanten. Entfernte oder falsch
typisierte Bindungen sind Fehler des ganzen Aktualisierungsstands, auch
außerhalb eines später gekürzten Ansichtsausschnitts.
`objectId` wird als JSON-Ganzzahl ohne Dezimalteil oder Exponent verlangt;
die allgemeine JSON-Schema-Kategorie `integer` allein prüft diese Schreibweise
noch nicht. Namen oder Zeichenketten mit Ziffern werden nicht zu IDs konvertiert.

## 6. Datei, Texteingabe und Stringquelle

**Datei/Text:** Die Datei-Auswahl liefert ein flüchtiges, strikt Base64-dekodiertes
UTF-8-Dokument. Dateiname und Erweiterung sind keine Typprüfung. Es wird kein
serverseitiger Dateipfad, keine URL und kein später erneut gelesener Dateiverweis
gespeichert. Datei und Texteingabe verwenden dieselbe Prüfung. „Prüfen“ und
Vorschauen schreiben nichts. „In Entwurf übernehmen“ ersetzt nach erfolgreicher
Prüfung ausschließlich den JSON-Inhalt des Formularentwurfs. Erst das normale
Anwenden speichert `JsonDocument` und aktiviert den ausdrücklich ausgewählten
Modus. Eingaben dürfen nicht stillschweigend eine bestehende Konfiguration ersetzen.

**Stringquelle:** `JsonVariableID` muss eine bestehende Stringvariable sein.
Es wird der vollständige rohe Stringwert gelesen, nicht dessen formatierte
Darstellung und kein Verweis auf einen Dateipfad. Ein Producer kann nach
erfolgreichem JSON-Aufbau seinen vollständigen Stand einmalig mit `SetValue`
in seine eigene Datenvariable schreiben. Teilstücke oder JSON-Patches gehören
nicht zum Vertrag. Das Tree-Modul schreibt niemals in diese Variable.

VM_UPDATE der ausgewählten Variable stößt eine erneute Prüfung an. Schnelle
Folgeänderungen werden auf höchstens eine Verarbeitung pro Sekunde begrenzt;
der zuletzt gelesene vollständige Wert muss nach Ruhe des Producers erscheinen.
Identische kanonische Daten erzeugen keine unnötige Neuinitialisierung.
Der bestehende 60-Sekunden-Timer bleibt als Nachprüfung erhalten. Optionale
Wertbindungen verwenden dieselben deduplizierten aktuellen Referenzen und
werden bei Datenänderungen wieder lesend aktualisiert. Andere Stringvariablen
oder fremde Objektzweige werden nicht durchsucht.

## 7. Öffentlicher Skriptzugriff

**Öffentliche Signaturen; verfügbar nach Installation dieses Modulstands.**
Beide Funktionen verlangen alle genannten Argumente. `$InstanceID` bezeichnet
die vorhandene EChartsTree-Instanz; `$JSON` ist der rohe Dokumenttext ohne
Base64- oder zusätzliche String-JSON-Verpackung.

| Signatur | Vertrag |
| --- | --- |
| `ECTR_ValidateTreeJSON(int $InstanceID, string $JSON): string` | Prüft nur lesend gegen Version, Struktur, aktuelle Grenzen und Bindungsfreigabe dieser Instanz. Liefert einen JSON-Bericht. Kein Quellenmoduswechsel, kein Persistieren, keine Objekt-/Archivänderung. |
| `ECTR_SetTreeJSON(int $InstanceID, string $JSON): string` | Ausdrückliche Übernahme ausschließlich bei aktivem `SourceMode=json`. Validiert vor dem Schreiben und speichert nur `JsonDocument`; wendet die unveränderte übrige Konfiguration an. Antwort als JSON mit `applied`, `unchanged` oder `rejected`. |

Der Prüfbericht enthält `valid`, bei gültigen Dokumenten `documentHash`,
`rootId`, `totalNodes`, `maximumDepth`, `visibleNodes`, `truncated`, sowie
`errors` und `warnings`. Meldungen besitzen `code`, `pointer` (JSON-Pointer)
und einen lokalisierten Klartext `message`. Der Bericht ist auf 20 Meldungen
begrenzt; `additionalMessages` zeigt weitere Befunde an. Er enthält kein
vollständiges Dokument oder Variablenwertabbild. `documentHash` ist SHA-256
der kanonischen Daten und dient der Wiederholungserkennung, nicht als Geheimnis.
Die additive Bindungsübersicht enthält `bindingCount`, `bindingsTruncated`
und `bindings` mit höchstens 100 aufgelösten lokalen Objektnamen und IDs.
Größere Bindungsmengen erhalten einen deutlichen Berichtshinweis; ihre
fachliche Prüfung bleibt vollständig, auch hinter dieser Berichtsgrenze.
`visibleNodes` zählt die nach MaxDepth/MaxNodes geladenen Knoten vor dem
Auf-/Zuklappen; es ist keine Zählung der gerade gezeichneten Beschriftungen.
`tree.count` behält dieselbe Bedeutung wie bisher. Eine reine Tiefenauswahl
setzt `truncated` nicht auf `true`.

Beim Setter enthalten `applied` und `unchanged` denselben Dokumenthash;
`rejected` enthält strukturierte Meldungen. In `objects` oder `variable`
meldet er `SOURCE_MODE_MISMATCH`, ohne Quellenwahl oder Inhalt zu ändern.
Ungültiger Inhalt wird vor jeder Konfigurationsänderung abgewiesen.
Vor dem Speichern werden Modus, Grenzen und Bindungen unter einer
instanzbezogenen Sperre erneut geprüft. Ausstehende Property-Änderungen
führen zu `CONFIGURATION_PENDING`; der Setter darf sie nicht beiläufig
anwenden. Die bereits angewandte Konfiguration und geplante Kernel-Properties
werden dafür getrennt betrachtet; ein Entwurf ist keine aktive Quellenwahl.
Die letzte Prüfung erfolgt nach allen Quellenlesungen einschließlich des
Vergleichs mit dem bisherigen Dokument. `IPS_GetConfiguration` und
`IPS_GetProperty` liefern aktive Werte, keine vorgemerkten Properties.
Nach dem Vormerken müssen die aktive Konfiguration unverändert und Änderungen
vorhanden sein. Nach dem Anwenden muss die vollständige aktive Konfiguration
dem erwarteten Ergebnis entsprechen und `IPS_HasChanges` wieder false liefern.
Parallele Aufrufe dieses Setters werden serialisiert; der zuletzt
erfolgreich angenommene Aufruf gewinnt, ohne Felder verschiedener Dokumente
zu mischen. Eine fachliche Versionsreihenfolge muss der Producer durch einen
geordneten Schreibpfad gewährleisten. `unchanged` schreibt nichts und führt
kein zusätzliches `ApplyChanges()` aus.

Formulare und andere Skripte verwenden diese Settersperre nicht automatisch.
Der Kernel bietet über die öffentlichen Getter keine atomare Prüfung fremder
vorgemerkter Werte zwischen eigenem Vormerken und Anwenden. Gleichzeitige
externe Konfigurationsschreiber müssen daher mit dem Producer abgestimmt werden.
Eine erkennbare Abweichung der aktiven Konfiguration oder ein neuer Entwurf
nach dem Anwenden führt zu `PERSISTENCE_FAILED`, niemals zu einer behaupteten
erfolgreichen Übernahme. Ein bereits aktivierter fremder Stand wird nicht durch
eine automatische Korrektur aus einem älteren Snapshot überschrieben.

Nach einem erfolgreichen Speichern kann ein späterer Transportfehler auftreten:
der Bericht unterscheidet deshalb `applied` von der aktuellen Anzeigeverfügbarkeit.
`dataQuality` bezeichnet den Zustand der Datenbasis; `displayConfirmed=false`
stellt klar, dass die Funktion keine Darstellung im Client bestätigt.
Eine angewandte gültige Konfiguration wird nicht allein wegen einer unterbrochenen
Visualisierung zurückgeschrieben. Ein nicht sicher bestätigter Speicherfehler
wird als solcher gemeldet; die Funktion behauptet dann weder Erfolg noch
einen nachgewiesenen unveränderten Bestand. Wiederholung muss den tatsächlichen
gespeicherten Hash prüfen. Es werden nie Designerwerte oder Quellenmodi aus
dem importierten Dokument abgeleitet.

Ein Produzent für häufige Aktualisierungen verwendet stattdessen den
Stringquellenmodus: dort ist ein `SetValue` der ausgewählten eigenen
Datenvariable ausreichend und benötigt kein Anwenden der Tree-Konfiguration.

Beispiel für die einmalige beziehungsweise seltene Übernahme
eines gespeicherten Dokuments; die Instanz muss zuvor auf `json` gestellt sein.
Die ID ist ein synthetischer Platzhalter:

```php
$instanceID = 12000;
$document = json_encode([
    'format' => 'de.burki24.symconecharts.tree',
    'formatVersion' => '1.0',
    'rootId' => 'network',
    'nodes' => [
        ['id' => 'network', 'parentId' => null, 'name' => 'Netzwerk', 'kind' => 'category'],
        ['id' => 'gateway', 'parentId' => 'network', 'name' => 'Gateway', 'kind' => 'instance']
    ]
], JSON_THROW_ON_ERROR);
$check = json_decode(ECTR_ValidateTreeJSON($instanceID, $document), true, 32, JSON_THROW_ON_ERROR);
if (!$check['valid']) {
    throw new RuntimeException('Tree-JSON ist ungültig; Prüfbericht auswerten.');
}
$result = json_decode(ECTR_SetTreeJSON($instanceID, $document), true, 32, JSON_THROW_ON_ERROR);
if ($result['status'] === 'rejected') {
    throw new RuntimeException('Übernahme abgewiesen; Meldungen auswerten.');
}
```

## 8. Budgets und Ansichtsbegrenzung

| Grenze | Vertrag |
| --- | --- |
| JSON-Eingabe und kanonisch gespeichertes Dokument | Je höchstens 1048576 Bytes, vor dem Parsen beziehungsweise Speichern geprüft. |
| Gespeicherte JSON-Konfiguration | Im Modus `json` muss auch die vollständige JSON-Serialisierung der Instanzkonfiguration einschließlich escaped `JsonDocument` innerhalb des 1-MiB-Ausgabepuffers bleiben; Prüfung vor Übernahme. Für bestehende Objektbaum-Konfigurationen entsteht keine neue Prüfung. |
| Datei-Aktionsargument | Höchstens 1398104 Base64-Zeichen; zusätzlich strikte Dekodierung und dekodiertes Bytebudget. |
| JSON-Parser | Bestehender strikter Leser: UTF-8 ohne BOM, keine doppelten Schlüssel, höchstens 32 Verschachtelungen und 100000 JSON-Werte; keine stillen Typkonvertierungen. |
| Gesamtes JSON-Modell | 1–3000 Knoten, höchstens zwölf Nachkommensebenen; keine Reparatur oder stille Kürzung ungültiger Eingaben. |
| Flüchtiger Laufzeitsnapshot | Höchstens 1 MiB einschließlich vollständig aufgelöster Werttexte und Metadaten; Prüfung auch außerhalb des Ansichtsausschnitts. |
| Konfigurierte Ansicht | `MaxDepth` 1–12 / Default 3; `MaxNodes` 10–3000 / Default 80, auch für kleine gültige JSON-Bäume. |
| Vorschau | Bestehende Obergrenze 80 gezeichnete Knoten, zusätzlich konfigurierte Grenzen und Anfangstiefe. |
| Ausgabe | Bestehende Kompressionsschwelle 200000 Bytes und 1-MiB-Symcon-Ausgabepuffer; komprimierte Ausgabe ist kein größeres Eingangslimit. |
| Objektmodus | Unverändert höchstens 30000 besuchte Objekte im Filter-Suchlauf; JSON löst keinen solchen Lauf aus. |

Alle Grenzen wirken gemeinsam; 3000 Knoten mit maximal langen Texten sind
nicht unabhängig von Bytebudget und Konfigurations-/Ausgabegröße zugesichert.
Knoten-/Tiefenprojektion erfolgt
erst nach vollständiger struktureller und fachlicher Prüfung. Eine konfigurierte
MaxNodes-Kürzung meldet sichtbare und gesamte Knoten sowie `truncated=true`.
MaxDepth ist wie bisher eine gewählte Ansicht. Ausgabegrößen müssen vor der
Aktivierung für beide Ausgabewege geprüft werden; ein zu großer Stand ersetzt
keinen funktionierenden Stand. Formularvorschauen dürfen auch in einem später
nicht gezeichneten Zweig keine ungültige Struktur übersehen.

## 9. Fehler, letzter gültiger Stand und Wiederanlauf

Ein neuer Stand wird zuerst vollständig eingelesen, validiert und projiziert.
Erst dann wird er als letzte gültige Datenbasis aktiv. Bei leerem/ungültigem
String, Lesefehler oder ungültiger Bindung bleiben Daten und Zeitpunkt des
letzten gültigen Stands erhalten, aber sichtbar als **veraltet** gekennzeichnet.
Eine alte Datenbasis darf nicht mit einem aktuellen Erfolgszeitpunkt erscheinen.
Ohne gültige Daten erscheint eine verständliche Fehlermeldung, kein leerer
oder künstlich ergänzter erfolgreicher Baum.

Der letzte gültige Stand ist ein einzelner begrenzter flüchtiger
Laufzeitsnapshot einschließlich bereits gelesener Werttexte und Annahmezeitpunkt.
Es wird keine zweite dauerhafte Dokument-/Wertkopie in Attributen geführt.
Sein Kontext enthält Quellenmodus, Stringvariablen-ID beziehungsweise
gespeicherten Dokumenthash und Bindungsfreigabe. Bei Quellenwechsel darf er
nicht als Daten der neuen Quelle erscheinen. Nach einem Neustart wird aus
`JsonDocument` beziehungsweise der Stringvariable frisch gelesen und geprüft.
Ist diese Quelle dann ungültig, erscheint bis zur gültigen Folgeänderung ein
Fehler, da kein bestätigter Laufzeitstand mehr vorliegt. Ein neuer Browser
kann während derselben Modul-Laufzeit den gekennzeichneten Snapshot erhalten.
Reine Designänderungen können dieselben Daten neu darstellen. Neue strengere
Ansichtsgrenzen werden auch auf den Snapshot angewandt. Ein Bindungsfehler
erlaubt höchstens dessen deutlich veraltete Anzeige ohne fehlerhafte
Wertnachladung; es werden keine aktuellen Werte vorgetäuscht.

Die additive `tree.source`-Metadatenstruktur enthält im darstellbaren
JSON-Ausgabemodell `mode`, `quality` (`ready` oder `stale`), `acceptedAt`
(Unix-Sekunden des letzten gültigen Stands) und `diagnostic` (Fehlercode oder
`null`). `GetTreeDiagnostic` erhält zusätzlich dieselbe Quelleninformation
als `source`; dort ist bei fehlender gültiger Datenbasis auch `quality=error`
mit `acceptedAt=null` möglich. Der bestehende Datenabruf darf in diesem Fall
weiterhin fehlschlagen, statt ein erfolgreiches leeres Modell zu liefern.
Der äußere bestehende Transportstatus `ready`/`error` bleibt erhalten: `stale`
hat weiterhin einen darstellbaren Baum und einen dauerhaften sichtbaren
Warnhinweis. Die bisherigen Symcon-Statuscode-Bedeutungen werden nicht
umnummeriert; Konfigurationsfehler und fehlgeschlagene Datenlesung bleiben
unterscheidbar. Ein Fehler wird durch eine alte Anzeige nicht zu Erfolg.
JSON-Transportzustände enthalten außerdem einen additiven `sourceContext`-
Fingerabdruck. Beim Wechsel dieses Kontexts darf die Browseransicht keinen
alten Baum durch ihre bisherige Transient-Fehlerbehandlung weiter anzeigen.
Im Symcon-Objektmodus bleiben die bestehenden Fehlerpfade unverändert.
Eine gültige Folgeänderung behebt den Fehler automatisch. Vorschauen zeigen
ungültige JSON-Entwürfe als Fehler und verwenden keine unmarkierte Beispielstruktur.

Fehlerklassen umfassen `JSON_INVALID`, `FORMAT_UNSUPPORTED`, `LIMIT_EXCEEDED`,
`DUPLICATE_ID`, `ROOT_INVALID`, `PARENT_MISSING`, `CYCLE_DETECTED`,
`OBJECT_BINDING_INVALID`, `SOURCE_UNAVAILABLE` und `SOURCE_MODE_MISMATCH`.
Der Skriptsetter meldet zusätzlich `CONFIGURATION_PENDING` bei
noch nicht angewandten Properties, einschließlich eines ausstehenden JSON-Entwurfs.
Unbekannte Felder/Typen erhalten den betroffenen JSON-Pointer. Logs enthalten
höchstens Fehlerklasse und bereinigten Pfad, keine Dokumente, Gerätekennungen,
Werttexte oder Netzwerkdaten. Es gibt keinen automatischen Download externer
Dateien und keine Änderung von Quellen, Archiven oder gebundenen Objekten.

## 10. Wiederverwendung und verpflichtende Nachweise

Der JSON-Adapter und die beiden Eingabeadapter liefern ein gemeinsames,
familienlokales Modell. Eine gemeinsame reine Projektion kann aus
`TreeHierarchy` extrahiert werden; die Objektlesung und Health-Bewertung
verbleiben bei ihren bisherigen Zuständigkeiten. JSON benötigt keine neuen
ECharts-Abhängigkeiten. `EChartsTreePreview`, `echarts-tree.js`, Theme,
Designer, Titelabstände, HTML-SDK, IPSView-Transport, Frame-/Mausradbehandlung
und Startzustandskompression werden wiederverwendet. Vorhandene Helper werden
regulär eingebunden und nicht lokal verändert.

| Bereich | Abnahmefälle für Implementierung und Live-Prüfung |
| --- | --- |
| Vertrag | Schema-/Beispielprüfung und semantische Prüfung; doppelte JSON-Schlüssel, UTF-8, unbekannte Version/Felder, falsche Typen, große Zahlen und Byte-/Parsergrenzen. |
| Struktur | Ein-Knoten-Baum, Eltern nach Kindern, Namensgleichheit, verschiedene ID-Schreibweisen, Waisen, mehrere Wurzeln, Kreise außerhalb der Wurzel, Ebene 12/13 und 3000/3001 Knoten. |
| Ansicht | Vollprüfung vor Kürzung; MaxNodes, MaxDepth, Vorschaugrenze, deterministische Reihenfolge und beide Ausgabepuffer. |
| Bindungen | Ohne Freigabe abgewiesen; gleiche Symcon-ID mehrfach dargestellt, Werte nach ID gelesen, Umbenennung, Löschung, Typwechsel, falsche Objekttypen und keine Schreibaktion. |
| Datei/Text/API | Identisches Modell aus allen Wegen; Entwurf ohne Persistieren, eindeutige Bestätigung, Setter im falschen Modus, identischer Wiederaufruf ohne Apply, konkurrierende Aktualisierungen und kontrollierte Speicher-/Transportfehler. |
| Variable/Snapshot | VM_UPDATE, schnelle Folgewerte, leer/ungültig/entfernt, automatischer Rückweg zu ready, Start ohne gültigen Stand, Neustart mit gültiger/ungültiger Quelle, Quellenwechsel, stale-Zeitpunkt und unveränderte Quellvariable. |
| Kompatibilität | Alte Properties/Designs/IDs unverändert; Objektfilter und 30000er-Budget; TreeSystemHealth unverändert nach gemeinsamer Extraktion. |
| Renderer | Kachel, IPSView und Vorschau; LR/RL/TB/BT/radial, sichere Werttexte, Aufklappen nach ID, neue Wurzel, Resize, Pan/Zoom und Mausrad/Scrollbedienung. |
| Live | Temporäre Testinstanz mit anonymem Netzwerkbeispiel, Datei/Text, Skriptfunktion und Stringvariable; gültig/ungültig/gültig, Wiederanlauf und Nachweis unveränderter Bestände. |

Die lokale Implementierung ist mit Modell-, Modul-, Callback-, Renderer- und
Chromium-Prüfungen abgesichert. Befehle und die Trennung zwischen lokaler
Einbettung und echter Host-Abnahme stehen in [ENTWICKLUNG](ENTWICKLUNG.md).
Die funktionale MCP-Abnahme und die Eigentümerbestätigung für JSON-Daten in
Kachel und IPSView sind abgeschlossen. Frühere Objektbaum-Abnahmen bleiben
unabhängig davon erhalten.

### Abnahmestand und verbleibende Testlücken

| Nachweis | Ergebnis |
| --- | --- |
| MCP, Version 1.403, Symcon 9.1 / PHP 8.5.8 | Datei-/Text-Callbacks mit vollständigen Argumenten, Entwurfsübernahme ohne Speichern, Stringquelle mit automatischem VM_UPDATE, schnelle Folgewerte, ungültig/gültig, alter Stand samt unverändertem Zeitpunkt, Quellenwechsel und erneutes Anwenden geprüft. Bindungen, eigene Ausgabesperren und strukturelle Grenzen geprüft. |
| MCP, Version 1.404, Symcon 9.1 / PHP 8.5.8 | `ECTR_SetTreeJSON`: Erstübernahme `applied`/`ready`, identische Wiederholung `unchanged`, ausstehende Änderungen bei gleichem und neuem Dokument `CONFIGURATION_PENDING`. Aktive Konfiguration und fremder Entwurf bleiben erhalten; der Entwurf lässt sich anschließend unverändert anwenden. Zweite Dokumentübernahme und Ablehnung ungültigen JSONs bestanden. |
| Bestandsschutz | Temporäre Testobjekte entfernt. Alle 139 bestehenden ECharts-Instanzen bei der Setter-Abnahme hinsichtlich Konfiguration, Änderungsstatus, Verbindung, Name, Parent und Ident unverändert. Keine Quellen oder Archive geändert. |
| Tatsächliche Ausgabe | Eigentümer bestätigt die JSON-Datenquelle in Kachel und IPSView. Lokale Renderer-/Chromium-Prüfungen bleiben davon getrennte Nachweise. |

Nicht durch diese funktionale Abnahme belegt sind ein vollständiger
Symcon-Prozessneustart mit gültiger beziehungsweise ungültiger JSON-Quelle,
echte gleichzeitige Fremdschreibzugriffe während der Aktivierung und reale
Speicher-/Transportausfälle; die zugehörigen Fehlerpfade sind lokal geprüft.
Ein Langzeit-/Volllasttest mit 3000 Knoten, eine gesonderte Symcon-9.0-Prüfung
und eine vollständige Geräte-/Touchmatrix bleiben offen. Die lokale
Mausradprüfung ist belegt; ein eigener JSON-spezifischer Praxisnachweis der
Mausrad-/Scrollbedienung in beiden tatsächlichen Clients ist nicht gesondert
protokolliert. Diese Testlücken heben die bestätigte Funktionsabnahme nicht auf.
