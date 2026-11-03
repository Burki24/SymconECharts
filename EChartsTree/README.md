# EChartsTree

`EChartsTree` zeigt einen ausgewählten Teil des IP-Symcon-Objektbaums als
interaktiven Apache-ECharts-Baum in einer nativen Kachel oder einem optionalen
IPSView-HTML-Widget. Benötigt werden IP-Symcon 9.0/9.1 und ein verbundenes,
aktives `EChartsGateway`. Ein Archiv ist nicht erforderlich.

Installation: Im **Symcon-Modulstore** nach **SymconECharts** suchen.
[Voraussetzungen und Einrichtung](../README.md#installation-und-erstes-diagramm)
(IP-Symcon 9.0/9.1, PHP 8.5, gemeinsames Gateway).
Alle technischen Felder, Standardwerte und Quellenparameter stehen in der
[Konfigurationsreferenz](../docs/KONFIGURATION.md#echartstree).

## Einrichtung und Darstellung

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
Formularänderungen reagierende schematische Vorschau. Ohne gültige Wurzel
verwendet diese ausdrücklich eine Beispielstruktur.
Die Vorschau zeigt echte Eltern-Kind-Verbindungen mit höchstens 80 Knoten
und berücksichtigt die anfänglich geöffneten Ebenen. Sie ist eine begrenzte
Struktur-/Designvorschau, keine vollständige Zustands- oder Objektübersicht.

Für IPSView aktivieren Sie die optionale WebContent-Ausgabe und platzieren die
erzeugte Variable in einem HTML-Widget. Das WebContent-Dokument wird bei
Datenaktualisierungen nicht neu geladen. Der Baum und das Design werden im
bestehenden Diagramm aktualisiert.

## Konfigurationsreferenz und Skriptzugriff

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
