# ADR 0111: Bestätigte JSLive-Neuanlage und familienbezogene Altmodelle

- Status: Implementiert und lokal geprüft; gemeinsame Live-Abnahme offen
- Datum: 2026-10-31
- Ergänzt: ADR 0109, ADR 0110 und Vertrag 1.0

## Auftrag und Grenzen

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

## Familienintegration

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
Die erneute MCP-Fortsetzung nach Eigentümer-Update, direkte Original-/Ziel-
Bildabnahme und die physische native App-/Touchbedienung der importierten
Modelle stehen aus. Frühere Eigentümerabnahmen normaler Modelle
bleiben gültig. Diese ADR hebt die Beta-Sperre des Eigentümers nicht auf.
