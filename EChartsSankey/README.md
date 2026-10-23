# EChartsSankey

Sankey stellt aktuelle Mengenflüsse zwischen manuell angelegten Knoten dar.
Die Breite jeder Verbindung entspricht dem aktuellen Wert einer ausgewählten
Integer- oder Float-Variable. Das Modul bietet eine native Symcon-Kachel und
optional ein unabhängig gestaltbares IPSView-HTML-Widget.

Legen Sie zuerst 2 bis 100 Knoten mit einem eindeutigen Schlüssel und einer
Beschriftung an. Schlüssel beginnen mit einem Buchstaben und enthalten bis zu
40 Buchstaben, Ziffern, Unterstriche oder Bindestriche. Die sichtbare
Beschriftung darf bis zu 80 Zeichen enthalten und bei mehreren Knoten gleich
sein. Beispiel:

| Schlüssel | Beschriftung |
| --- | --- |
| solar | Photovoltaik |
| grid | Netzbezug |
| house | Haus |
| heating | Heizung |

Legen Sie anschließend 1 bis 200 gerichtete Verbindungen an. Start- und
Zielknoten werden aus den Knotenlisten ausgewählt, auch während der
Bearbeitung noch nicht gespeicherter Knoten. Ordnen Sie jeder Verbindung eine
numerische Quellvariable zu, beispielsweise `solar → house` die aktuelle
PV-Leistung und `house → heating` die Heizungsleistung. Übernehmen Sie die
Konfiguration nach Abschluss der Eingaben.

Alle Flüsse müssen dieselbe fachliche Größe und Einheit verwenden. Die
optionale Einheit, zum Beispiel `W`, wird angezeigt; sie rechnet Werte nicht
um. Eine Variable kann bewusst mehreren Verbindungen zugeordnet werden;
auf jeder gilt dann ihr vollständiger Wert. Eingehende und ausgehende Summen
müssen vom Anwender sinnvoll gewählt werden. Unterschiede werden nicht
automatisch ausgeglichen. Ein Knotenwert entspricht der größeren dieser
beiden Summen, nicht einem zusätzlichen Messwert.

Negative Werte sind standardmäßig unzulässig. Kreise, Selbstverbindungen und
doppelte gerichtete Knotenpaare sind weiterhin unzulässig. Die Diagnose nennt
bei fehlerhaften Einträgen nach Möglichkeit die Zeile.
Nullflüsse und Knoten ohne positive Verbindung bleiben unsichtbar. Sind alle
Werte null, erscheint ein entsprechender Hinweis. Wertänderungen aktualisieren
das vorhandene Diagramm automatisch.

## Optionale Umkehr negativer Flüsse

Aktivieren Sie in einer Verbindung **„Negative Flüsse umkehren“**, wenn deren
Quellvariable bewusst beide Richtungen beschreibt, zum Beispiel Netzbezug und
Einspeisung. Ohne diese Auswahl bleibt die bisherige Ablehnung negativer Werte.
Die Auswahl gilt je Verbindung, auch wenn dieselbe Variable mehrfach verwendet wird.

- Positive Werte fließen weiterhin vom eingetragenen Start zum Ziel.
- Negative Werte vertauschen nur für die Darstellung Start und Ziel. Die
  Verbindungsbreite und Knotensummen verwenden den positiven Betrag.
- Nullflüsse bleiben unsichtbar; sie werden nicht umgekehrt.

Beispiel: `Netz → Haus` mit `500 W` zeigt Netzbezug. Bei aktivierter Umkehr
zeigt `−200 W` einen Fluss `Haus → Netz` mit Breite `200 W`. Der Tooltip nennt
die tatsächliche Richtung, den Originalwert `−200 W` und die konfigurierte
Verbindung. Dauerhafte Verbindungsbeschriftungen zeigen wie bisher die Breite.
Knotenwerte sind Flussbeträge, keine vorzeichenbehaftete Nettobilanz.

Die konfigurierte Struktur muss weiterhin kreisfrei und eindeutig sein –
auch wenn eine Umkehr aktuell einen konfigurierten Kreis auflösen würde.
Zusätzlich werden alle tatsächlichen Richtungen nach der Umkehr geprüft,
einschließlich der unveränderten Richtung unsichtbarer Nullflüsse. Ein dabei
entstehender Kreis oder ein doppeltes gerichtetes Paar wird abgewiesen;
das Diagramm zeigt einen Hinweis statt einer Teilmenge oder veralteter Flüsse.
Ein Vorzeichenwechsel kann daher eine gültige Darstellung ungültig machen;
ein später wieder gültiger Wert stellt sie automatisch wieder her.

Kachel, IPSView und beide Vorschauen verwenden dieselbe Richtungsprüfung.
Die Vorschau erhält den Originalwert im SVG-Titel der umgekehrten Verbindung.
Gespeicherte Knoten, Verbindungen, Quellwerte und Designs werden nicht verändert.
Einheitenprüfung, Schutzgrenzen und Ablehnung nichtendlicher Werte bleiben
erhalten. Eine Umrechnung findet nicht statt; berechnete Bilanzflüsse lassen
sich unabhängig davon je Knoten ausdrücklich aktivieren.

## Optionale Rest-/Bilanzknoten

Aktivieren Sie bei einem Knoten **„Berechneten Bilanzfluss ergänzen“**, wenn
dessen nicht zugeordnete Differenz sichtbar werden soll. Standardmäßig bleibt
die Auswahl aus; bestehende Modelle werden nicht automatisch ausgeglichen.
Die Berechnung verwendet ausschließlich die gemessenen Verbindungen nach
einer gegebenenfalls aktivierten Kantenumkehr:

- Mehr Zufluss als Abfluss: Ein zusätzlicher Fluss führt zu
  **„Nicht zugeordnet (berechnet)“**. Beispiel: `1000 W` hinein und `750 W`
  hinaus ergeben `250 W` nicht zugeordneten Abfluss.
- Mehr Abfluss als Zufluss: Ein zusätzlicher Fluss kommt von
  **„Ungeklärter Zufluss (berechnet)“**. Bei `1000 W` hinein und `1200 W`
  hinaus sind dies `200 W`.
- Gleiche Summen erzeugen keinen zusätzlichen Knoten oder Nullfluss.

Die Auswahl gilt nur für den jeweiligen Knoten, auch für einen ausdrücklich
gewählten Anfangs- oder Endknoten. Berechnete Verbindungen fließen nicht erneut
in die Berechnung benachbarter Knoten ein. Es handelt sich um lokale
Differenzen, nicht um eine zusätzlich gemessene Gesamtbilanz.

Berechnete Knoten sind im Namen gekennzeichnet. Ein dauerhafter Hinweis
**„Berechnete Flüsse · keine Messwerte“** bleibt auch bei ausgeschalteten
Beschriftungen und Tooltips sichtbar. Der Tooltip nennt außerdem den
zugehörigen Knoten sowie dessen gemessenen Zu- und Abfluss mit Einheit.
Die Vorschau verwendet dieselben Werte und kennzeichnet die Berechnung
im SVG-Titel und unter dem Diagramm.

Eine Differenz ist **kein nachgewiesener Verlust und keine Messung**.
Unvollständige Gerätezuordnung, verschiedene Aktualisierungszeitpunkte und
Messabweichungen können ebenfalls Differenzen erzeugen. Die aktuellen
Quellwerte sind keine zeitlich synchronisierte Messaufnahme. Es werden weder
Variablen angelegt noch Quellwerte, Einheiten oder gespeicherte Designs geändert.
Reine Gleitkomma-Rundungsreste bis zu einem relativen Anteil von `10⁻¹²` an
der größeren Knotensumme erzeugen keinen Bilanzfluss. Das ist keine einstellbare
Messtoleranz; die gemessenen Werte selbst bleiben unverändert.

Jede von null verschiedene Differenz benötigt einen zusätzlichen Knoten und
eine zusätzliche Verbindung. Diese zählen **innerhalb** der bisherigen Grenzen
von insgesamt 100 Knoten und 200 Verbindungen, einschließlich konfigurierter
unsichtbarer Einträge. Planen Sie daher Reserven ein: Bei 100 angelegten Knoten
ist kein weiterer berechneter Knoten möglich. Überschreitet eine aktuelle
Differenz das Budget, erscheint eine Diagnose statt eines unvollständigen
Diagramms. Einheitenprüfung, Kreisfreiheit und alle weiteren Schutzregeln
bleiben erhalten.

## Einheitenmodell und optionale Quellenprüfung

Das gemeinsame Einheitenmodell gilt für Kachel, IPSView und beide Vorschauen:

| Modus | Verhalten |
| --- | --- |
| Manuelle Anzeigeeinheit (Standard) | Verwendet wie bisher Ihre optionale Einheit, ohne die Quelleinheiten zu prüfen. Bestehende Konfigurationen bleiben unverändert nutzbar. |
| Automatische gemeinsame Quelleinheit (geprüft) | Ermittelt die Einheit aller Verbindungsquellen aus deren Symcon-Darstellung beziehungsweise Legacy-Profil. Alle müssen übereinstimmen; diese Einheit wird angezeigt. Das manuelle Feld wird ausgeblendet, aber nicht gelöscht. |
| Manuelle erwartete Einheit (geprüft) | Vergleicht jede Quelleinheit mit Ihrer eingetragenen Einheit. Leer erwartet ausdrücklich einheitenlose Quellen. |

Native `SUFFIX`-Angaben haben Vorrang vor dem Suffix eines referenzierten
Legacy-Profils. Äußere Leerzeichen werden entfernt; Groß-/Kleinschreibung und
Einheitenpräfixe bleiben relevant: `W`, `kW` und `kWh` sind verschieden.
Ein leeres oder fehlendes Suffix bedeutet einheitenlos. Automatisch ist eine
vollständig einheitenlose Quellenmenge gültig; eine Mischung aus `W` und leer
wird abgewiesen. Nicht lesbare oder fehlerhafte Metadaten sind dagegen ein
Prüffehler, kein Ersatz für eine einheitenlose Quelle.

Geprüft werden alle konfigurierten Verbindungen, auch aktuell ausgeblendete
Nullflüsse. Eine mehrfach verwendete Variable behält ihre ID und ihren vollen
Wert an jeder Verbindung. Die Diagnose nennt die betroffene Verbindungszeile.
Fehlerhafte geprüfte Modelle werden nicht teilweise gezeichnet. Einheitentexte
sind weiterhin auf 24 Zeichen begrenzt.

Die Prüfung vergleicht Einheitenkennzeichen, nicht physikalische Dimensionen
oder die Richtigkeit der Variablenbeschriftung. Alle Flüsse müssen weiterhin
dieselbe fachliche Größe und Rohwertbasis darstellen. Es erfolgen **keine
Umrechnung, Skalierung oder Normalisierung** und kein automatischer
Bilanzausgleich. Eine geänderte Variablendarstellung wird bei der nächsten
Modellprüfung beziehungsweise Datenaktualisierung erneut berücksichtigt.

## Darstellung

Die Designer bieten waagerechte oder senkrechte Anordnung, Knotenausrichtung,
Knotenbreite und -abstand, Verschieben der Knoten, Beschriftungen, Werte,
Schriftgrößen, Farben, Tooltip und Animation. Flüsse erhalten die Farbe ihres
Start- oder Zielknotens, einen Farbverlauf oder eine feste Farbe. Deckkraft und
Krümmung sind einstellbar. Bei vielen Knoten reduziert die Darstellung den
Abstand bei Bedarf. Verschobene Positionen werden nicht gespeichert.

Unter „Knotengestaltung“ können Sie einzelne Knoten gezielt einfärben sowie
Rahmenbreite, Rahmenfarbe, Eckenradius, Deckkraft und Schatten einstellen.
Individuelle Farben haben Vorrang vor der gemeinsamen Knotenfarbe und dem
Theme. Die Zuordnung verwendet den Knotenschlüssel, nicht die Beschriftung;
gleichnamige Knoten bleiben damit unabhängig. Pro Schlüssel ist genau eine
Farbzeile zulässig. Farben derzeit nicht vorhandener Knoten werden ignoriert.

Für Knotenbeschriftungen stehen automatische oder feste Positionen, Abstand,
Fettschrift und eine maximale Breite zur Verfügung. Breite `0` wählt den
responsiven Standard. Lange Texte lassen sich kürzen oder an Wörtern bzw.
Zeichen umbrechen. Überlappende Knotenbeschriftungen können ausgeblendet
werden. Beschriftungen werden außerdem auf die tatsächlich verfügbare
Knotenbreite bzw. -höhe begrenzt. Sehr schmale Verbraucher erhalten deshalb
gegebenenfalls keine dauerhafte Beschriftung; Tooltip und Hervorhebung zeigen
weiterhin den vollständigen Namen und Wert, sofern eingeschaltet.
Verbindungswerte lassen sich zusätzlich mit eigener Schriftgröße und
Farbe anzeigen; sie verwenden ebenfalls die eingetragene Einheit.

Die Hervorhebung beim Berühren eines Knotens oder einer Verbindung kann
ausgeschaltet werden, direkte Verbindungen oder vollständige Flusspfade
zeigen. Die Deckkraft hervorgehobener und übriger Flüsse ist einstellbar.
Layout-Optimierungsdurchläufe beeinflussen die automatische Anordnung:
Standard sind 32, bei `0` bleibt die Reihenfolge der Knotenliste innerhalb
der Flussstufen erhalten.

IPSView übernimmt standardmäßig das Kacheldesign. Alternativ können Sie es
kopieren und unabhängig bearbeiten. Die Formularvorschauen reagieren auf
ungespeicherte Einstellungen. Beim Öffnen und Bearbeiten lesen sie die
aktuellen Quellwerte und zeigen dieselben Knoten und Verbindungen wie der
Entwurf, bis zu den vollständigen 100 Knoten und 200 Verbindungen. Nullflüsse
werden wie im Diagramm ausgeblendet. Fehlende, ungültige oder negative Werte
erscheinen als Hinweis statt als erfundene Beispielwerte. Die Vorschau speichert
keine Einstellungen und verändert weder Instanzstatus noch Quellvariablen.
Die SVG-Vorschau stellt Schriftumbruch und Überlappung schematisch dar;
Layout-Optimierung und interaktive Hervorhebung werden erst im Diagramm
angewendet. Die tatsächlichen Textmaße bestimmt der Browser.

Das Modul benötigt ein aktives EChartsGateway. Es liest Werte und verändert
weder Quellvariablen noch Archivdaten.
