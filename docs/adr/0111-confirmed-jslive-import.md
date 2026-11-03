# ADR 0111: Bestätigte JSLive-Neuanlage und familienbezogene Altmodelle

- Status: Import, Wiederanlauf und Farb-/SVG-Darstellung bestätigt; J01 nach Modulupdates unter 1.386 per MCP live abgenommen
- Datum: 2026-10-31
- Ergänzt: ADR 0109, ADR 0110 und Vertrag 1.0

## Auftrag und Grenzen

Der aktuelle, nach Prüfarten getrennte Gesamtstand steht in der
[Migrationsabnahme](../JSLIVE_MIGRATION_ABNAHME.md). Die 0.113-Standardvorlage ist
nach inhaltlichem Vergleich mit exakten LF-/CRLF-Hashes ergänzt; unbekannte
Vorlagen bleiben gesperrt. Die Live-Abnahme von Verlauf und Zahlen unter 1.382
ist erledigt und wird nicht erneut verlangt.

### Ergänzung: validierte Progressbar-Farben und SVG-Füllbilder

Der Importauftrag umfasst nun die zuvor gesperrten Fortschrittsfarben und
SVG-Füllbilder. `EChartsProgress/JSLivePaint.php` ist der familienlokale Adapter:
keine alte Ressourcenfunktion wird ausgeführt und keine URL nachgeladen.
Der Vertrag 1.0 und alle veröffentlichten Settings bleiben unverändert.
Neue Modelle enthalten zusätzlich `progressPaints`; beim Lesen wird dieser
abgeleitete Inhalt vollständig aus den eingefrorenen Settings neu geprüft.
Abweichende URI-, Typ- oder Geometriedaten werden abgewiesen. Alte Modelle ohne
aktive Farb-Overrides bleiben unverändert nutzbar.

Der Adapter akzeptiert Hex-Farben, die explizite loading-bar-Gradientgrammatik
mit 2–16 Hex-Farben, 0–360° und 0–3600 Sekunden sowie eingebettete SVG-Bilder.
Die mathematische Gradientgeometrie folgt der festgeschriebenen MIT-Ressource;
deren Engine wird nicht ausgeliefert. Dauer und Originaltext bleiben erhalten.
Seit der Korrektur der zeitlichen Darstellung bewegt ein begrenzter nativer
Canvas die Palette mit einem einzigen, auf 30 Zeichnungen pro Sekunde begrenzten
Frame-Zyklus je Diagramm. Verdeckte Dokumente zeichnen nicht; Disposal beendet
den Zyklus. Polling und Größenänderungen erhalten die Phase. Dauer null und
ausgeschaltete Zielanimationen bleiben statisch. Der Plan verlangt für getrennte
Startphasen und Browser-Timing `native-progress-cyclic-gradient-1` statt der
bisherigen Standbild-Abweichung. Vorhandene Journalbestätigungen werden nicht
umgeschrieben. Er meldet außerdem
mit `native-progress-svg-route-1`, wenn der alte, erweiterungslose SVG-Dateipfad
schwarz rendern kann und das Ziel stattdessen das geprüfte Bild zeichnet.
Explizites Füll-Override gewinnt vor dem Dateibild; eigene SVG-Formen behalten
ihre eigenen Farben. Auch inaktive Angaben werden auf Zulässigkeit geprüft.

`EChartsSvgImage::ImportPaint()` teilt die bestehende SVG-Prüfung, erlaubt
zusätzlich statische lokale Muster und erhält begrenzte intrinsische Bildmaße.
Der bisherige `Import()`-Vertrag wird nicht gelockert. Muster dürfen einander
weder enthalten noch referenzieren; alle Farb-IDs müssen eindeutig und lokal
auflösbar sein. Rotation und Translation sowie maximal 64 Kacheln je Achse
begrenzen den neuen Teilvertrag. Datei 256 KiB, 512 Elemente, Tiefe 32, Modell
512 KiB, Zustandsdaten 512 KiB und HTML 1 MiB gelten unverändert. Die neue
Rasterauflösung ist auf 4096 Pixel je Achse begrenzt; die inverse Musterskalierung
erhält deren ursprüngliche Koordinaten und Wertunabhängigkeit.

Konfiguration, Bestätigung und Journal laufen unverändert durch den regulären
Import. Originalinstanzen, Quellen, Darstellungen und Archive werden nicht
geschrieben. Ein Modellupdate importiert gesperrte Fälle nicht automatisch.

Die Quellvorlage liest ihre sichtbaren Präfix-/Suffix-Texte ausschließlich aus
`VariableProfile`. Neue Modelle speichern diese Literale als `progressLabel`;
native Formate und Custom-Profile bleiben vollständig für Tooltips erhalten.
Altmodelle ohne dieses Feld lösen nur diese beiden Texte lesend zur Laufzeit
auf. Dabei werden weder Modellproperties noch Quellprofile geschrieben.

Der Eigentümer hat die vollständige Übernahme einschließlich der fehlenden
Zielmodelle für die 40 JSLive-Testvarianten beauftragt. Die reine Vorschau aus
ADR 0110 genügt dafür nicht. Unbekannte Varianten, ausführbare Fremdtemplates,
ungültige Quellen und nicht vollständig übersetzbare Darstellungen bleiben
gesperrt. Eine Bestätigung darf diese Sperren nicht aufheben.

Historische Datenverarbeitung und Gestaltung sind getrennte Nachweise. Auch
eine erfolgreich angelegte und technisch verifizierte Instanz ist kein Beleg
pixelgleicher Darstellung. Abweichungen benötigen einzeln benannte Regeln und
eine an den aktuellen Plan gebundene Bestätigung.

## Persistenz und Wiederanlauf

`EChartsJSLiveJournal` besitzt die versionsgebundene Zustandsmaschine, ein
expliziter Kerneladapter die Schreibgrenze. Das Gateway hält das Journal als
Attribut; normale Modulupdates führen keinen Import aus. Pakete werden für
einen Wiederanlauf erneut ausgewählt, nicht in Debug, Properties oder Journal
dupliziert. Einträge enthalten Identität, Revision, Zieldefinition-Fingerabdruck,
Objektzuordnung, Anlageabsicht, Besitzmarker, geprüfte Ziel-Snapshots und Ergebnis.
Ein Journal ist auf 1000 Einträge und 4 MiB begrenzt; es wird nie still gekürzt.
Eine monotone, lückenlose Sequenz erhält die Revisionsreihenfolge unabhängig von
der kanonischen Sortierung zufälliger Journal-IDs.

Eine Sperre serialisiert Importausführungen. Vor der ersten Erzeugung wird die
Absicht dauerhaft geschrieben. Nach Rückkehr des Erzeugungsaufrufs werden ID
und unveränderter Ausgangszustand gespeichert, dann der Besitzmarker gesetzt.
Ein Absturz zwischen Erzeugung und dieser Speicherung bleibt ausdrücklich
mehrdeutig: Der Wiederanlauf erzeugt kein weiteres Objekt. Eine eigene Aktion
muss das Restobjekt zuordnen oder dessen geprüfte Abwesenheit bestätigen.
Gleiche Namen reichen niemals als Zuordnungsgrund.

Vor SetConfiguration und Apply wird die Aktivierungsabsicht persistiert.
`IPS_SetConfiguration()` setzt ausschließlich geplante Einstellungen;
`IPS_GetConfiguration()` und die Snapshot-Prüfung lesen weiterhin den aktiven
Stand. Das Journal hält deshalb den aktiven Ausgangszustand in `snapshot` und
den erwarteten aktivierten Zustand in `pending`. `configured` bezeichnet diese
dauerhafte Absicht, noch keinen Erfolg. Erst nach Apply müssen alle aktiven
Konfigurations- und Objektfelder mit dem erwarteten Ergebnis übereinstimmen.

Ein bereits vorgemerkter Entwurf wird beim Wiederanlauf nicht erneut gesetzt.
Öffentliche Getter liefern keine vollständige geplante Konfiguration. Der
Wiederanlauf aktiviert daher einen protokollierten offenen Schritt ohne
Korrekturschreiben und prüft danach das vollständige aktive Ergebnis. Eine
abweichende Aktivierung wird beibehalten und mit `TARGET_CHANGED` gesperrt,
niemals durch den Importplan überschrieben. Ein neuer Entwurf nach erfolgter
Aktivierung, ein nicht protokollierter Entwurf oder fremde aktive Änderungen
werden bereits vor Set/Apply abgewiesen. Abgeschlossene Ziele erhalten bei
Wiederholung weder SetConfiguration noch ApplyChanges.

Version 1 des Journalformats bleibt erhalten. Alte Fehlerstände mit
`phase=created`, `applyIntent=false`, unverändertem aktivem Snapshot und dem
erwarteten Konfigurations-Snapshot in `pending` werden erst beim ausdrücklich
bestätigten Wiederanlauf kompatibel fortgesetzt. Ziel-/Journal-ID, Marker,
Revision und Zielhash bleiben unverändert; der ursprüngliche Erzeugungsnachweis
bleibt dokumentiert. Das gilt auch,
wenn ein Modulupdate die vorgemerkten Einstellungen bereits aktiviert oder
verworfen hat. Kein Journal-Reset und keine Migration beim bloßen Bibliotheks-
Update; unbekannte oder geänderte Zustände bleiben gesperrt.

### Erstellungsnachweis und bestehende Neusiegelungen

Create registriert `JSLiveCreationGuard` mit dem Hash des vollständigen
Ausgangszustands als Standardwert. Es liest oder schreibt das Attribut dabei
nicht aktiv. So übernimmt die native Attributwiederherstellung beim Modulreload
den gespeicherten Nachweis, statt ihn durch den aktuellen Zustand zu ersetzen.
Die Testlaufzeit bildet Registrierung und wiederhergestellte Attribute getrennt
ab und lässt aktive Create-Schreibzugriffe die Wiederherstellung überlagern.

Für bereits unter dem alten Ablauf neu gesiegelte Ziele gibt es eine enge
Version-1-Kompatibilität ausschließlich im bestätigten, gesperrten Wiederanlauf:
Der gespeicherte und der aktuelle Eigentumsmarker müssen zum Journaleintrag
passen. Der neue Nachweis muss exakt dem alten Create-Neusiegelungsmuster
entsprechen, also `CreationHash` des aktuellen vollständigen Snapshots sein.
Alle Objekt-/Konfigurationsfelder müssen mit dem gespeicherten Ausgangszustand
oder dem gültig protokollierten Aktivierungsziel übereinstimmen; neue Kinder
benötigen weiterhin die geschützten Ausgabenachweise. Erst dann werden die
Snapshot-Nachweise im Journal angeglichen. Das optionale Feld
`originalCreationGuard` bewahrt den ursprünglichen Nachweis. Formatversion,
Ziel-/Journal-ID, Revision, Zielhash und Marker bleiben erhalten. Keine Reparatur
von Zielattributen, Konfigurationen oder Ausgaben; geänderte und unzugeordnete
Objekte sowie beliebige andere Nachweisabweichungen bleiben gesperrt.

### Begrenzte Journal-Leseansichten

`EChartsJSLiveJournalView` liefert für öffentliche Aufrufe, Formular und
Gateway-übergreifende Reservierungsprüfung dieselbe Metadatenprojektion.
`GetJSLiveImportJournalPage(Offset, Limit, Revision)` verlangt alle Argumente,
liefert höchstens 50 Einträge/128 KiB und chronologisch geordnete Listen mit
expliziter `entryID`. Konfigurations-/Ressourcen-Snapshots bleiben intern.
Ein Fingerabdruck des gesamten Journals bindet alle Seiten an einen konsistenten
Stand; jede Änderung verlangt einen Neustart bei Offset 0. Gesamtzahl,
Sequenzen und Folgeseiten werden vor einer Peer-Auswertung geprüft.
Das Formular blättert in 25er-Seiten und zeigt veraltete Ansichten ausdrücklich
an. Bei einem Konflikt auf einer späteren Peer-Seite bleibt die gesamte
Neuanlage gesperrt. Die installationsweite Schreibsperre bleibt unverändert.

Der alte parameterlose Vollabruf bleibt bis 256 KiB formgleich erhalten und
meldet darüber `LIMIT_EXCEEDED` mit Verweis auf die Seitenfunktion. Das ist eine
explizite Transportbegrenzung unter Symcons realer 1-MiB-Rückgabegrenze, keine
Kürzung oder Verkleinerung des unveränderten 4-MiB-/1000-Einträge-Journals.

Ausschließlich moduleigene neue
IPSView-Ausgaben erhalten im Ziel geschützte Eigentumsnachweise einschließlich
stabiler Objekt-/Variablenmetadaten. Sie erlauben nach unterbrochenem Apply die
Übernahme erwarteter neuer Kinder. Fehlender Nachweis oder nachträgliche Änderung
stoppt den Wiederanlauf; dynamische Werte und HTML-Inhalte sind keine Identität.

Jede Konfigurationsänderung erhält einen vorher gespeicherten erwarteten
Folgezustand. Ein Wiederanlauf akzeptiert ausschließlich den zuletzt geprüften
Ausgangszustand oder exakt diesen Folgezustand. Manuelle Änderungen, fehlende
Ziele und geänderte Ausgabeidentitäten verhindern eine Überschreibung.
`ApplyChanges()` darf nur auf dem eigenen unveränderten Neuziel wiederholt
werden. Bestehende Gateways, JSLive-Instanzen, Quellen, Darstellungen, Actions
und Archive sind nie Schreibziele. Automatisches Aufräumen gibt es nicht.

Die dauerhafte Identität ist Installation plus Ursprungsinstanz; Export-UUID
und Erfassungszeit sind keine neue Identität. Eine neue Revision verlangt einen
frischen Vergleich und eine ausdrückliche Entscheidung für eine separate
Neuanlage. Das alte Ziel bleibt erhalten. Wiederholung einer abgeschlossenen
Revision liefert dieselben IDs und wendet ihre Konfiguration nicht erneut an.

Auch die unveränderte Bestätigung mit `separateFrom` muss dieselbe getrennte
Neuanlage wiederfinden und nach einem Abbruch fortsetzen. Neue Journaleinträge
speichern deshalb optional ihren bestätigten Vorgänger unter `separateFrom`;
ohne getrennte Anlage ist der Wert `null`. Journalversion 1 bleibt erhalten.
Ein Verweis muss auf einen früheren Eintrag derselben Herkunft zeigen. Bei
vorhandenen Version-1-Einträgen ohne dieses Feld gilt ausschließlich der direkt
vorhergehende Eintrag derselben Herkunft als kompatibler Nachweis. Der aktuelle
Planfingerabdruck und sämtliche Ziel-/Eigentums-/Snapshot-Prüfungen bleiben
erforderlich. Falsche oder veraltete Vorgänger schaffen keine weitere Kopie;
Erzeugungslücken verlangen weiterhin eine ausdrückliche Zuordnung.

Ein Codeupdate verändert den über alle Importregeln gebildeten Fingerabdruck.
Alte Bestätigungen werden weiterhin abgewiesen; der Anwender muss den aktuellen
Plan prüfen und bestätigen. Die Journalzuordnung verlangt dagegen keinen
historischen Plan-Hash als aktuellen Hash: Bei gleicher Herkunft und Revision,
unveränderter vollständiger Zieldefinition und gültigem Vorgänger wird derselbe
Eintrag fortgesetzt. Aktuelle Plan-/Quellenprüfung unter Sperre und sämtliche
Eigentums-/Snapshot-Prüfungen bleiben erhalten. Der historische `planHash` wird
nicht umgeschrieben; weder Journalversion noch Bestände benötigen eine Migration.

Die J01-Regression prüft abgeschlossene und unterbrochene getrennte Anlagen
mit gleicher und neuer Revision, neuen und alten Version-1-Journalen an allen
23 dauerhaften Schritten, mit gleichen und aktualisierten Regeln (184 Fälle).
Ungültige Vorgänger, unbestätigte Pläne, geänderte Zieldefinitionen und
veränderte oder fehlende Ziele bleiben gesperrt. Der öffentliche Gateway-Test
prüft zusätzlich Wiederaufnahme und identische Bestätigungswiederholung mit
dem konkreten Kerneladapter und einem aus einem früheren Regel-Fingerabdruck
berechneten historischen Plan; veraltete Regeln und Bestätigungen bleiben
gesperrt. Ein abgeschlossener Wiederaufruf schreibt keine
nativen Einstellungen. Die Live-Nachprüfung nach Eigentümer-Update ist unter
1.386 bestanden: 53 erhaltene Testziele aus sechs Familien wurden je zweimal
mit aktuell bestätigtem Plan und `separateFrom` erfolgreich aufgerufen.
Ziel-/Journal-IDs, geschützte Konfigurationen und das vollständige Journal
blieben unverändert. Alte Bestätigungen und ungültige Vorgänger wurden korrekt
abgewiesen. Einzelheiten und verbleibende Testlücken stehen in der
[Migrationsabnahme](../JSLIVE_MIGRATION_ABNAHME.md).

## Familienintegration

### Automatischer Initialisierungs- und Timer-Wiederanlauf

Alle sechs Import-Zielfamilien teilen den Startpfad in `EChartsJSLiveTarget`.
Die Initialisierung hält nur einen flüchtigen Bereitschaftsmarker im Modulbuffer;
Properties, persistierte Formate und Quellenverträge werden nicht ergänzt.
Ein gültiges Modell plant bei bereitem Kernel bereits vor den Gateway-/Quell-
Prüfungen den bestehenden `JSLiveImportRefresh`-Timer. Dessen bisheriger Takt
aus `RefreshTime`, begrenzt auf 1–300 Sekunden, bleibt unverändert. Bei einem
vorübergehenden Fehler bleibt Status 298 mit geplanter Wiederholung erhalten.
Der tatsächliche Timer-Callback wiederholt zunächst die offene Initialisierung;
bei Erfolg geht er ohne ApplyChanges in die normale Datenaktualisierung über.
Gesunde Modelle initialisieren Referenzen nicht bei jeder Aktualisierung neu.

Bis dahin melden Daten-, Kachel- und IPSView-Ausgaben denselben Fehlerzustand,
ohne Live-/Archivdaten eines noch nicht initialisierten Modells zu lesen.
Ungültige Modelle bleiben ohne Polling-Timer gesperrt; das Entfernen des Modells
stoppt den Importtimer. Vor KR_READY bleibt der Timer aus, IPS_KERNELSTARTED
übernimmt den regulären Start. Spätere reine Datenlesefehler behalten den bereits
geplanten Takt und erholen sich beim nächsten erfolgreichen Abruf. Keine
Konfigurationsschreibzugriffe, Profil-/Archivänderungen oder erneute Importe
aus Timer oder ApplyChanges; Ausgabe-IDs und Erstellungsnachweise bleiben stabil.

Die Importausführung bleibt gemeinsam; Berechnung und ECharts-Geometrie bleiben
bei den Familien. Bestehende Konfigurationen behalten ihre bisherigen Defaults,
Grenzen und Ausgabewege. Erweiterungen für Mehrfachbindungen, laufende
Altintervalle und Rohwertregeln sind ausdrücklich versionierte Importmodelle,
keine stillen Änderungen der bisherigen Datenregeln. TimeSeries und BarHistory
verwenden denselben importierten historischen Datenpfad; ein gemischtes
Linien-/Balkenbild bleibt ein Bild in TimeSeries. Die alte Quellen-ID-Eindeutigkeit
bleibt für normale Konfigurationen erhalten; importierte Bindungen haben zusätzlich
ihren unveränderten Bindungsschlüssel. Pie erhält ein getrennt validiertes
Importmodell für mehrere Ringe mit Originalgewichten und eigener Formatierung.
Es aktiviert niemals still eine Normalisierung. GaugeSingle erhält die vier
Altgeometrien über native ECharts-Grafikprimitive; keine CanvasGauges-Engine.

Historisches Radar wird als eigene Familie `EChartsRadarHistory`, Präfix ECRH,
GUID `{A1ABAD81-1878-4F35-A6EE-29B1DE1BCF2D}` implementiert. Pfadfortschritt
und Füllgeometrien liegen getrennt vom Symbolbalken in `EChartsProgress`, Präfix
ECPG, GUID `{FEF19240-5D04-4DE0-9AE9-D55DECBC2BD2}`. Beide folgen demselben
Gateway-Datenfluss und beginnen mit dem importierten Modell. Die bestehenden
Radar-/PictorialBar-Instanzen und deren öffentliche Verträge werden nicht umgestellt.

Die gemeinsame zusätzliche Operation `archive.snapshot.read` ist ein begrenzter,
lesender Transport für explizite Archiv-IDs, Boolean-Rohwerte und unverarbeitete
Roh-/Avg-Zeilen. Sie enthält keine familienbezogene Reduktion. Jede Abfrage
behält höchstens 2000 Zeilen plus Überschreitungserkennung; die Geräte planen
höchstens 63 Abfragen und 32000 Zeilen gemeinsam. Typ-/Zählerpolitikwechsel und
gekürzte Antworten bleiben Fehler. Der bisherige `archive.read`-Vertrag bleibt
unverändert. Es werden weder Archive aktiviert noch Schutzbudgets angehoben.

## Importierte Designer und Ressourcen

Importmodelle erhalten die vollständigen ursprünglichen Einstellungen getrennt
von den regulären Familien-Properties. Beide Ausgabewege bieten zusätzliche
Titel-/Hintergrund-/Schrift- und Sichtbarkeitsanpassungen. Der Standardhintergrund
bleibt transparent wie die ungefüllten Quellseiten; Container können sich
unterscheiden, was der Bericht ausdrücklich nennt. Die vollständigen Quellstile
werden nicht verlustbehaftet in die bisherigen Designer zurückübersetzt.

Abweichend von der synthetischen Voll-SVG-Vorschau in ADR 0036 zeigen importierte
Modelle eine klar bezeichnete schematische Farbkarte und native HTML-Downloads
für die ungespeicherten Formularentwürfe. Diese Downloads nutzen dieselben
echten Quellen-/Archivleser und Renderer wie die Kachel und IPSView. Die
Ausnahme vermeidet eine zweite unzuverlässige Geometrie für alle Altvarianten.
Normale Modelle und ihre Vorschauen bleiben unverändert.

Standardschriften werden ausschließlich nach bekannten SHA-/Familien-/Lizenz-
Manifesten übernommen. Standardtemplates sind inerte Hashreferenzen; eigene
Templates und unbekannte ausführbare Inhalte bleiben gesperrt. Sichere SVGs
verwenden EChartsSvgImage; importierte Strichpfade erhalten zusätzliche Grammatik-
und Geometriebudgets. Es werden keine alten Rendering-Engines ausgeliefert.

## Nachweisstand

### Geometrie-Regressionen F01 und F02

Der Original-/Ziel-Kachelvergleich unter 1.373 zeigte eine zu kurze
Fortschrittsstrecke bei mehrteiligen Bogenpfaden und eine gespiegelte
Radar-Klassenfolge. Die Zahlenwerte waren korrekt. F01 liegt im Teilstrecken-
Längenlauf für Bogenkommandos der festgeschriebenen Runtime. Der lokale
Progress-Adapter spielt Strichpfade deshalb über die öffentliche Canvas-
Schnittstelle mit höchstens 16 kubischen Segmenten je Bogen ab. So bleiben
native Teilstrecken, Strichmuster und Übergänge nutzbar. Füllpfade und sichere
SVG-Bilder behalten ihren bisherigen Zeichenpfad; Runtime, Ressourcen und
2048-Segment-/8192-Koordinaten-Eingangsgrenzen werden nicht geändert.
RadarHistory verwendet die vorhandene native Option `clockwise=true`, ohne
Klassen, Werte oder deren Tooltip-Reihenfolge umzusortieren.

`tests/jslive_geometry.js` reproduzierte F01 im echten ausgelieferten
CanvasRenderer mit exakt halber Kreisstrecke bei 86,2 Prozent. Der getrennte
F02-Lauf scheiterte am tatsächlich projizierten Ost-Punkt auf der Westseite.
Analytische Längen/Endpunkte, gedrehte Ellipsen, Aktualisierungen, eigene
Strichmuster, Umkehr sowie native Radar-Koordinaten sichern beide Korrekturen
in der regulären Suite. Importmodelle und Designs werden vor/nach dem Rendern
verglichen. Am 01.11.2026 bestehen 82 Fortschrittspfad-/Updatefälle, die Radar-
Koordinatenprüfungen sowie alle 120 vorhandenen SSR-Renderings. Zusätzlich
bestehen 80 isolierte Chromium-Prüfungen mit anonymen lokalen Dokumenten
(40 Varianten je Kachel und IPSView), einschließlich Aktualisierung, Schriften,
vorübergehender Datenfehler und Mausrad-Smoke. Unter 1.374 wurden alle acht
betroffenen Original-/Ziel-Kachelpaare visuell und alle 16 Kachel-/IPSView-
Renderer per MCP bestätigt. Die 16 Konfigurationen, fünf Quellen und die
Archivmetadaten/-anzahlen blieben unverändert. Eine erneute native IPSView-App-
Prüfung wurde nicht ausgeführt; weitere optische Unterschiede bleiben offen.

### Gauge- und Datenlabel-Regressionen F03 und F04

Die Gauge-Familie projiziert die vier Quellvorlagen mit deren wirksamen Canvas-
Maßen: vertikal maximal halbe Höhe als Breite, horizontal maximal ein Viertel
der Breite als Höhe. Radiale Schriften beziehen sich auf Radius/200, lineare
Skalenschriften auf die nach Titel-/Einheiten-/Wertzeilen verfügbare Querbreite.
Bei leerer Einheitenschrift bleibt wie beim Canvas-Font-Setter die zuvor aktive
Zahlen-/Titelschrift beziehungsweise die Standardschrift der gecachten Platte
wirksam, ohne die gespeicherte Schriftangabe zu verändern.
Platten-/Zeigergeometrie, Naben, Skalenseiten, Textbegrenzung und Verlaufsausrichtung
bleiben lokal im Gauge-Renderer. Die erforderlichen aus Canvas Gauges 2.1.7
adaptierten Koordinatenregeln behalten ihren MIT-Hinweis; keine alte Engine
wird ausgeliefert. Persistierte Modelle, Quellen und Designs ändern sich nicht.

Die linearen Quellvorlagen setzen keine Endfarben für Grund-/Fortschrittsbalken;
beide verwenden deshalb Vollfarben samt konfigurierter Transparenz. Ein
zusätzlicher Verlauf ins Transparente wird nicht erzeugt. `GenerateTicks`
rundet zunächst auf die Quellpräzision; `json_encode_advanced` schreibt diese
numerischen Strings jedoch ohne Anführungszeichen als Zahlen. Nur die Skalentexte bilden
diese Zahlenkonvertierung unabhängig von lokalisierten Client-Ziffern und
Minuszeichen nach; Wertbox-Präzision und Originalwerte bleiben
erhalten. Innere und äußere Nabenkreise teilen den vorhandenen Zeigerschatten
(10 Pixel Unschärfe, je 2 Pixel Versatz und konfigurierte Farbe/Transparenz).
Die familienlokale Schattenfunktion wird wiederverwendet; der zentrale
Seiten-/Ressourcen-Helper bleibt unverändert.

Die Quelle zeichnet bei ausdrücklich numerischen Exact-Ticks keine benannten
Kompass-Hauptticks. Diese Abwesenheit bleibt ohne NaN-Geometrie erhalten.
Minderstriche verwenden die ursprünglichen Schritte und bei extrem dichten
Skalen eine regelmäßige rein visuelle Ausdünnung auf höchstens 2000 Positionen
über den vollständigen Bereich; Hauptticks und Werte werden nicht gekürzt.

`format.dataLabel` löst den automatischen Hintergrund-/Rand-Fallback zentral
auf. Pie übergibt dafür die Farben jedes einzelnen Segments, andere Familien
verwenden ihre Serienfarben. Ausdrückliche Farben samt Alpha null haben Vorrang.
Pie-Anker und Textausrichtung sind getrennte radiale Größen; native Labelrahmen
erhalten Quellpadding und Zeilenhöhe. Nullsegmente zeigen keine leeren Kästen.

`tests/jslive_design_parity.js` reproduzierte beide Fehler vor der Korrektur.
Die 459 unabhängigen, eingefrorenen Messungen, 81 Text-/Schriftzuordnungen und
72 Farb-/Schattenprüfungen stammen aus dem unveränderten Original-Canvas-Gauges-
Renderer für acht anonymisierte Varianten in drei Größen.
Der optionale Direktvergleich mit dem lesenden JSLive-Checkout besteht ebenfalls;
die reguläre Suite benötigt diesen Checkout nicht. Native Datenlabelmodelle,
tatsächlich gezeichnete Rahmen und Positionen sowie unveränderte Eingaben werden
gesondert geprüft. Die korrigierte Referenz zeichnet die tatsächlich numerisch
serialisierten Quellticks; vorher wurden an dieser Testgrenze fälschlich
Festkommastrings übergeben. Die Restfehler reproduzierten getrennt RED:
transparenter Verlauf statt Vollfarbe, `25.00` statt `25` und fehlende
Schattenattribute. Canvas-Prüfungen umfassen Datenupdates, null/volle Balken,
ausgeschaltete Balken/Zeiger und ausdrücklich transparente Farben. Der lokale
Chromium-Lauf prüft die tatsächlichen Cyan-/Grundstreckenpixel in beiden
linearen Ausrichtungen sowie sichtbare Schattenpixel radialer/Kompass-Naben.
F04 ist unter 1.375 an allen vier Pie-Paaren live bestätigt. Die erneute
Live-Abnahme der drei F03-Restkorrekturen ist unter 1.376 an allen acht Gauge-
Paaren per MCP und Original-/Ziel-Bildvergleich bestätigt;
andere optische Befunde werden damit nicht als erledigt markiert.

### Progress-Farben und Quellgeometrie F05 und F06

Die Quellengine färbt eigene SVG-Bilder und ihr festes Text-Preset nicht mit
der Progress-Füllfarbe um. Der Renderer behält deshalb die geprüfte Original-
SVG-URI als Vordergrund und begrenzt ausschließlich deren sichtbaren Ausschnitt.
Die feste Textform verwendet das ursprüngliche inerte SVG mit Arial, zentraler
Glyphen-Basislinie und standardmäßig schwarzer Schrift. Nur der Hintergrund
wird aus dem Bildalpha, konfigurierter Hintergrundfarbe und Dilatation erzeugt.
Benutzer-SVGs durchlaufen weiterhin unverändert `EChartsSvgImage`; insbesondere
wird dessen Verbot von Text, Filtern, Bildern und aktiven Inhalten nicht gelockert.
Der bereits ausdrücklich bestätigte Stroke-zu-Text-Fill-Fall 37 bleibt bestehen.

Die familienlokale Einpassung teilt für alle Progressformen die wirksamen
`ResizeProgressbar`-/SVG-Regeln: Widgetbreite minus 40, beschriftungsabhängige
Höhe auch bei unsichtbarem Label, acht Pixel Quellrand, `xMidYMid meet` und
1,5-mal größte Strich-/Trail-/Extrusionsbreite als ViewBox-Rand. Die
Einpassung berücksichtigt, dass der gemeinsame HTML-Adapter den acht Pixel
Quellrand bereits bereitstellt; er wird nicht ein zweites Mal abgezogen.
Die zusätzliche Kachel-Kopfzeile bleibt unabhängig reserviert. Die Preset-
ViewBox bleibt von den gezeichneten Pfadgrenzen getrennt; ein benutzerdefinierter
Pfad mit Breite oder Höhe null erhält wie die Quelle den 100×100-Fit-Fallback.
SVG-Bilder verwenden ihre natürlichen Bildmaße statt ViewBox-Einheiten; bei
fehlenden Größen wird das Seitenverhältnis in den 300×150-Standard eingepasst.
Explizite Maße und physische CSS-Einheiten werden berücksichtigt. Die Position
der unteren Wertanzeige folgt dem vollständigen Widget und seinem SVG-
Grundlinienabstand, die mittige Wertanzeige dessen Mitte. Leere Schriftfamilien
behalten den Browser-Serif-Fallback. Native Titel-/Schriftanpassungen bleiben möglich.

Das Quelltemplate übergibt den falsch geschriebenen Schlüssel `stroke-lincap`,
den die Engine nicht auswertet. Die tatsächlich gezeichneten Linienenden bleiben
deshalb flach, auch bei gespeicherter Auswahl „rund“. Die persistierte Auswahl
wird nicht korrigiert oder neu angewendet. F01-Pfadlängen, Strichmuster, Umkehr,
Werte, Ressourcen, Quellbezüge, Designs und Schutzbudgets bleiben erhalten.
Es gibt keine neue gemeinsame Helper-Abstraktion ohne weiteren Nutzer: SVG-
Prüfung, HTML-/Ressourcen-/Frameadapter und Übergänge werden weiterverwendet,
Progress-Geometrie verbleibt im eigenen Renderer. Die adaptierten Regeln und
die feste Textform behalten den ursprünglichen MIT-Hinweis von loading.io.

`tests/jslive_progress_parity.js` reproduzierte F05 und F06 getrennt vor dem Fix.
63 eingefrorene unabhängige Messungen aus dem unveränderten JSLive-Checkout
decken alle 18 Varianten in drei Größen sowie zusätzliche Grenzfälle ab.
Der optionale Referenzlauf führt die echte Engine, Größen-/Label-Funktionen
des Quelltemplates und dessen CSS in einem isolierten lokalen Chromium aus;
alle drei SHA-256-Werte werden vor dem Vergleich geprüft. Reguläre CI benötigt
nur die anonymen Messwerte. 24 Clipfälle prüfen Originalbilder, beide Endwerte
und vier Richtungen ohne Modelländerungen; die ausgelieferte Canvas-Runtime
zeichnet weiterhin die 82 F01-Pfadfälle und jetzt auch überprüfte flache Enden.
36 vollständige Kachel-/IPSView-Browserausgaben bestehen Fontladen, Refresh,
Fehlererholung, Titel und synthetischen Mausrad-Smoke-Test. 96 zusätzliche
Rastervergleiche prüfen schwarze Originalglyphen, eigene SVGs, Verläufe und
Transparenz gegen direkt gezeichnete Originalbilder. Die Live-Abnahme von
F05 ist unter 1.377 an den Text-/SVG-Vergleichen live bestätigt. Die dortige
lesende Prüfung aller 18 Progress-Paare bestätigt auch die korrigierte
Einpassung und die flachen Linienenden. Sie zeigte jedoch noch zwei F06-
Restfehler: graue Zusatzstriche für flächenlose line-/rainbow-Füllungen und zu
schmale Hintergründe anderer Pfadfüllungen.

Die Füllhintergründe verwenden jetzt denselben familienlokalen, fest erzeugten
Alpha-Maskenadapter wie SVG-Hintergründe. Eine maskierte Hintergrundfläche
ersetzt die bisherige native Kontur. Pfadmasken behalten den ursprünglichen
weißen 1-Pixel-Strich vor der Morphologie sowie den Objektbereich des Filters
(`x=y=-1`, `width=height=3`). Positive Extrusion erweitert, negative verkleinert
die Maske. Pfade mit Breite oder Höhe null blenden beide Formgrafiken aus;
die optionale Wertanzeige und der unveränderte Fit-Fallback bleiben erhalten.
Die echten Pfadgrenzen steuern die Füll-Clips, Preset-/Fit-Grenzen weiterhin
die Einpassung. Sichtbarkeit wird bei Grafikupdates ausdrücklich gesetzt,
damit beim Wechsel zwischen gefüllten und leeren Formen nichts zurückbleibt.
Eigene SVG-Farben, Pfad-/Strichmodelle, Originalwerte und Schutzgrenzen bleiben
erhalten; der Importvalidator erlaubt weiterhin keine Benutzerfilter.

Der Quellreferenzlauf friert zusätzlich 24 tatsächliche Hintergrund-SVGs aus
der unveränderten Engine ein. Entfernt werden nur Vordergrund und Label;
generierte Zufalls-IDs werden normalisiert. Die reguläre Suite vergleicht deren
Masken-/Filter-/Flächengrenzen und zeichnet sieben Nullflächen mit der echten
Canvas-Runtime einschließlich Hin-/Rückwechseln ohne erfundene Striche.
`tests/jslive_progress_fills_browser.js` rastert Original-SVG und natives Ergebnis
unabhängig in zwei Größen, mit leerer Fläche, Randbreiten null/positiv/negativ,
starker Extrusion sowie eigenen Kurven, Ecken und Teilpfaden. Ein schwarzer
Diagnosehintergrund macht auch kleine Randfehler sichtbar. Alle 24 Fälle bestehen
mit höchstens einem Pixel Antialiasing-Spielraum an der Kante. Der vorherige
Renderer scheitert getrennt mit 1272 erfundenen Nullflächenpixeln beziehungsweise
162 Randpixeln jenseits dieses Spielraums. Die gespeicherten Eingaben bleiben
identisch. Die neue lesende Live-Abnahme der F06-Restkorrekturen liegt unter
1.378 an den betroffenen Vergleichspaaren vor; physische native App-
Bediennachweise bleiben getrennt offen. Der anschließende JSLive-Vorlagenfix
verwendet selbst den bestehenden Text-Fill-Fallback. Der unabhängige
Referenzlauf entfernt deshalb seine bisher ausdrücklich gesetzte Type-
Umstellung; alle eingefrorenen Layouts und Masken bleiben identisch.
Originalinstanzen und produktive Systeme wurden nicht verändert.

### Import und Wiederanlauf

Der isolierte Journaltest prüft bestätigte Neuanlage, Wiederholung ohne erneutes
Anwenden, Zieländerungen, getrennte Revisionen, Sperren und Unterbrechungen nach
jedem Persistenz-/Kernelaufruf, einschließlich bei Apply erzeugter Ausgaben.
Die Kernel-Doppel bilden getrennte geplante und aktive Konfigurationen ab;
das frühere sofortige Aktivieren in SetConfiguration verdeckte den Live-Fehler.
Die Regression reproduzierte vor dem Fix denselben `TARGET_CHANGED`-Abbruch
im konkreten Kerneladapter. Sechs historische Wiederaufnahmeszenarien und
23 Unterbrechungspunkte prüfen jetzt den korrigierten Ablauf. Der reale
gesicherte Fehlerdatensatz wurde zusätzlich außerhalb des Repositorys lokal
mit noch geplantem, verworfenem und bereits aktiviertem Stand wiederaufgenommen;
dies ist kein erneuter Live-Schreiblauf.
40 anonymisierte Pakete bestehen native Modelle und beide HTML-Ausgaben,
vollständige Feldpläne, den konkreten Kerneladapter und die tatsächlichen sechs
Modulklassen mit ausgeführten nativen Formularcallbacks. 520 unabhängige
Quellvergleiche prüfen Zeit-/Radarregeln; 120 ECharts-SSR-Renderings und 80
isolierte Chromium-Dokumente prüfen die neue native Laufzeit. Begrenzte
64-MiB-Berichtsprüfung, Quellen-/Ressourcenfehler und unvollständige Archivantworten
sind gesondert abgesichert. Befehle und Grenzen stehen in tests/README.md.

Die MCP-Prüfung unter 1.371 bestätigte 40 vollständige Export-/Importpläne,
39 Neuanlagen mit jeweils idempotenter Wiederholung, verfügbare Daten und beide
HTML-Ausgaben sowie unveränderte geschützte Bestände. Die verbliebene
Wiederaufnahme scheiterte am neu gesiegelten Erstellungsnachweis; der große
Journalabruf überschritt außerdem die native Rückgabegrenze. Beide Fehler
sind lokal reproduziert und korrigiert: Reload aller sechs echten Modulklassen,
alte Neusiegelungen, öffentliche native APIs/Formularcallbacks und Peer-Konflikte
auf späteren Seiten sind regressionsgeprüft. Der gesicherte reale Fehlerstand
besteht die lokale Wiederaufnahme in drei Updatezuständen ohne neue Instanz.
Die MCP-Prüfung unter 1.372 bestätigte die Fortsetzung des erhaltenen Ziels,
40 vollständige Importe mit idempotenten Wiederholungen und 80 HTML-Ausgaben.
Die Metadatenseiten mit Größen 1, 7, 25 und 50 waren vollständig, chronologisch
und revisionsstabil; fehlerhafte oder veraltete Fortsetzungen wurden abgelehnt.
Geschützte Bestände und Konfigurationen der fertigen Ziele blieben unverändert.
Nach dem Update standen jedoch acht Gauge- und vier Pie-Instanzen mit Status
298 und Timerintervall 0 still, obwohl Daten bereits verfügbar waren. Ein
manueller Refresh korrigierte nur den Status; erst erneutes Anwenden des
unveränderten Stands reaktivierte die Timer. Danach waren alle 40 Ziele und
Timer aktiv. Diese Intervention ersetzt keine automatische Wiederanlaufabnahme.

Die Regression reproduzierte den Stillstand lokal vor dem Fix. Alle 40 echten
Modulvarianten durchlaufen jetzt einen Reload mit zunächst fehlendem Gateway,
mehrere tatsächliche registrierte Timer-Callbacks und die Erholung ohne erneutes
ApplyChanges. Kernelstart, ungültige/entfernte Modelle, korrigierte Quelltypen
und spätere Datenflussfehler sind ebenfalls geprüft; Konfigurationen, Referenzen,
Erstellungs-/Ausgabenachweise sowie Quell-/Archivdaten bleiben erhalten.
Unter 1.373 wurde die automatische Timerfortsetzung aller acht Gauge- und vier
Pie-Ziele nach dem Eigentümer-Update ohne zusätzliches ApplyChanges per MCP
bestätigt. Alle 40 Ziele lieferten bereite Daten; Konfigurationen und geschützte
Bestände blieben unverändert. Der anschließende Original-/Ziel-Kachelvergleich
lieferte die oben beschriebenen F01-/F02-Befunde. Deren erneute Live-Bildabnahme
nach dem nächsten Update, weitere optische Unterschiede sowie die physische
native App-/Touchbedienung bleiben offen. Frühere Eigentümerabnahmen normaler
Modelle bleiben gültig. Diese ADR hebt die Beta-Sperre des Eigentümers nicht auf.
