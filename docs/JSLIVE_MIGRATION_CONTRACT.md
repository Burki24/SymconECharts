# JSLive → SymconECharts: Export-/Importvertrag 1.0

- Vertragskennung: `de.burki24.jslive-echarts`
- Formatversion: `1.0`
- Status: spezifiziert am 31.10.2026; erster JSLive-Producer und gemeinsame
  Darstellungsnormalisierung lokal implementiert. Reguläre Helper-Verteilung,
  reale Exportabnahme, weitere Konvertierungsregeln, Importer und neue Zielmodelle
  sind noch offen.
- Entscheidungen: [ECharts ADR 0109](adr/0109-jslive-migration-contract.md) und
  [JSLive ADR 0007](https://github.com/Burki24/JSLive/blob/dev/docs/adr/0007-echarts-migration-contract.md).
- Kanonische Quelle ist dieses Dokument im Repository SymconECharts. JSLive
  referenziert denselben Vertrag; es gibt keine unabhängig gepflegte Zweitfassung.

## 1. Ziel und unveränderliche Bedingungen

Bestehende JSLive-Charts sollen ohne vollständigen manuellen Neuaufbau als
ECharts-Diagramme übernommen werden. Quellen, historische Datenbedeutung und
Darstellung haben gleichrangige Erhaltungsanforderungen. Ein optisch ähnliches
Diagramm mit anderen Berechnungen ist keine vollständige Übernahme.

JSLive erzeugt das Paket. SymconECharts prüft, plant, zeigt Vorschau und Bericht
und erzeugt nach ausdrücklicher Bestätigung neue Zielinstanzen. Export und
Analyse sind lesend. Bestehende JSLive-/ECharts-Konfigurationen, Quellvariablen,
Variablen-IDs, Werte, Actions, Profile, native Darstellungen, Archivierung,
Rohhistorien und Aggregate bleiben unverändert. Auch fremde Ausgabevariablen,
Links, Skripte und IPSView-Platzierungen werden nicht umgeschrieben.

Die Profilübersetzung erzeugt profilunabhängige Darstellungsdefinitionen für
die importierten Ziele. Sie ist keine Umstellung der bestehenden Quellvariablen.
Neue, tatsächlich benötigte eigene Ausgabe-/Steuervariablen verwenden native
Darstellungen und vorhandene Helper. Keine Kopie der Messvariablen, keine neu
erfundenen Archivquellen und keine automatischen Legacy-Profil-Neuanlagen.

Eine vollständige automatische Übernahme darf nur für nachgewiesene Varianten
angeboten werden. Beliebige eigene HTML-/JavaScript-/PHP-Vorlagen sind kein
Versprechen pixelgleicher oder verlustfreier automatischer Konvertierung.

## 2. Kompatibilität, Verantwortlichkeit und gemeinsame Bausteine

Der bisherige JSLive-Vertrag `ExportConfiguration`, `exportConfiguration`,
`GetConfigurationLink` und `LoadConfigurationFile` bleibt erhalten, einschließlich
gefilterter/vollständiger Exporte und ausdrücklicher Skriptauswahl. Das neue
Paket erhält einen getrennten, ausdrücklich bezeichneten Exportweg. Dieses
Dokument legt noch keine neuen öffentlichen PHP-Funktionsnamen oder Modul-GUIDs fest.

Ein altes JSON mit `ModuleID`, `ModuleName`, `Config` ist kein Paket 1.0. Ein
späterer Legacy-Adapter muss separat erkennbar sein und fehlende Zeitsteuerung,
Profile oder Bindungen melden. Er darf diese nicht durch Defaults erfinden.

Die erste Implementierung muss den vorhandenen vollständigen JSLive-Export
als Quelle charakterisieren; lokaler Analyse-Ausgangsstand ist JSLive 0.105,
Commit `456ac79`. Das ist keine Behauptung, diese Version exportiere Format 1.0.
Die erste ausliefernde Version und unterstützte ältere Konfigurationen werden
erst mit bestandenem Hersteller-/Verbraucher-Vertragstest eingetragen.

Beide Libraries bleiben eigenständig. JSLive besitzt Erfassung und Export;
SymconECharts besitzt Mapping, Zielvalidierung und Importjournal. Quellenprüfung,
Zeitplanung, Formatierung und Vorschau verwenden vorhandene ECharts-Bausteine.
Gemeinsame reine Präsentationskonvertierung oder Paketvalidierung gehört bei
tatsächlicher Nutzung beider Libraries in Symcon_ModuleHelper, einschließlich
Vertrag, Tests und regulärer Helper-Synchronisierung. Keine Helper-Schattenkopien.
Zielgeometrie und fachliche Berechnungen bleiben bei der Diagrammfamilie.

## 3. Paket, Versionierung und Grenzen

Ein Paket ist ein UTF-8-JSON-Dokument ohne BOM mit Dateiendung
`.jslive-echarts.json`. Es enthält keine ausführbare Hülle und keine automatisch
zu entpackenden Archive. Binärressourcen stehen Base64-kodiert in `assets`.

Verbindliche Hauptfelder:

| Feld | Typ und Bedeutung |
| --- | --- |
| `format` | Exakt `de.burki24.jslive-echarts` |
| `formatVersion` | Exakt `1.0` für diese Vertragsrevision |
| `exportId` | UUID dieses Exportvorgangs; unverändert bei erneuter Ausgabe desselben erfassten Pakets |
| `createdAt` | UTC-Zeitstempel nach RFC 3339 |
| `producer` | Objekt mit `libraryId`, `libraryVersion`, `build`, `exporterVersion`, `symconVersion`, `phpVersion` |
| `sourceInstallation` | Objekt mit zufälliger persistenter `id` (UUID), keine Adresse, Hardwarekennung oder Zugangsdaten |
| `charts` | Geordnete Liste der Chart-Objekte aus Abschnitt 4 |
| `assets` | Liste der Ressourcen aus Abschnitt 8, gegebenenfalls leer |
| `issues` | Geordnete Liste der Erfassungsprobleme aus Abschnitt 9, gegebenenfalls leer |
| `extensions` | Objekt für benannte optionale Erweiterungen, gegebenenfalls leer |

Jede zukünftige kompatible Ergänzung erhöht die Minor-Version. Ein Leser muss
eine Version ausdrücklich unterstützen; unbekannte Versionen werden vor jeder
Schreibaktion abgelehnt. Keine stillschweigende Auslegung als 1.0. Änderungen an
Feldbedeutung, Datentyp, Priorität oder Berechnungsregeln benötigen eine neue
Major-Version. Erweiterungen sind nur ignorierbar, wenn sie ausdrücklich keine
Auswirkung auf Werte, Darstellung oder Wiederholung haben; sonst blockieren sie.

JSON-Namen sind case-sensitive. Doppelte Objektschlüssel, ungültiges UTF-8,
NaN/Infinity, unerwartete Typen und nicht endliche Zahlen sind ungültig.
Boolean false, Integer 0, null, leere Zeichenfolge und fehlendes Feld sind
verschiedene Zustände. Dezimalzahlen und Integer dürfen nicht in Strings
umgewandelt werden. IDs bleiben Integer; Reihenfolge und Leerzeichen in
Beschriftungen, Präfix/Suffix und Originalstrings bleiben erhalten.

Für Fingerabdrücke gilt eine gemeinsame PHP-Kanonisierung: Objektschlüssel
rekursiv nach UTF-8-Bytes aufsteigend sortieren, Listenreihenfolge beibehalten,
leere Objekte von leeren Listen unterscheiden, mit `JSON_UNESCAPED_UNICODE`,
`JSON_UNESCAPED_SLASHES`, `JSON_PRESERVE_ZERO_FRACTION` und `JSON_THROW_ON_ERROR`
ohne Einrückung kodieren und SHA-256 über die UTF-8-Bytes bilden. Keine Unicode-
oder Textnormalisierung. Die unveränderliche Paketkennung ist der Hash über
das vollständige Paket; Vergleich des fachlichen Revisionsstands lässt dagegen
exakt `/exportId`, `/createdAt` und `/charts/*/reference` aus. Diese Referenzen
sind Vergleichsdaten, keine Zielkonfiguration. Der Plan nennt beide Hashes.
Steuerzeitpunkte wie `StartDate` werden niemals entfernt. Integer außerhalb
von −(2^53−1) bis 2^53−1 werden abgelehnt; Fließkommazahlen müssen endlich sein
und verlustfrei als unterstützter Float hin-/zurückkodierbar bleiben. Der
gemeinsame PHP-Kodierer verwendet `serialize_precision=-1` für diesen Vorgang.

Die persistente Installations-UUID wird im vorgesehenen JSLive-Lebenszyklus
außerhalb des lesenden Exports initialisiert. Es gilt genau eine Kennung für
die Installation über alle JSLive-Splitter und Charts hinweg; ein Parentwechsel
oder Modulupdate erzeugt keine neue Herkunft. Ohne gültige Kennung ist nur ein
Fehlerbericht, kein formal gültiges Paket 1.0 zulässig. Auch eine passende
Kennung entbindet nicht von aktueller Quellen-/Archivprüfung; nach Wiederherstellung oder Kopie einer
Installation ist die tatsächliche Zielzuordnung ausdrücklich zu bestätigen.

Paketgrenzen für 1.0: 16 MiB Dateigröße, 32 Verschachtelungsebenen in der
JSON-Hülle, 100 Charts, insgesamt 3200 Reihen/Bindungen, 512 Ressourcen,
1 MiB dekodiert pro Ressource und insgesamt 8 MiB dekodierte Ressourcen.
Ein `original.configuration`-Objekt umfasst höchstens 512 KiB; eingebettete
JSON-Propertystrings werden getrennt begrenzt geprüft, nicht rekursiv ausgeführt.
Größere Bestände werden ausdrücklich auf mehrere Pakete verteilt, nie gekürzt.
Diese Transportgrenzen erhöhen keine Quellen-, Archiv-, SVG- oder HTML-Grenze
eines Zielmoduls. Ein Überschreiten blockiert den betroffenen Importplan.

## 4. Chart-Objekt und vollständige Erfassung

Jedes Chart enthält folgende Felder. Leere Listen sind ausdrücklich zulässig,
wo die jeweilige Familie keine Achsen, Historie oder Ressourcen benötigt.

| Feld | Bedeutung |
| --- | --- |
| `chartKey` | Paketweit eindeutig: `instance:<Ursprungsinstanz-ID>` |
| `origin` | `instanceId`, `moduleId`, `moduleName`, `instanceName`, `family` und `templateVariant` |
| `original` | Vollständige fachliche `configuration` mit originalen JSON-Propertystrings; `runtimeState` und `excluded` gemäß unten |
| `sources` | Quellobjekte mit `sourceKey=variable:<ID>`, `variableId`, `variableType`, `label`, `presentation`, `archive` |
| `bindings` | Geordnete Reihenzuordnungen mit `bindingKey`, `sourceKey`, `role`, `enabled`, `settings`, `appearance` und `originalPointer` |
| `axes` | Eigene Achsenidentitäten samt Reihenfolge, Einheit, Bereich, Seite, Beschriftung, Schrittweite und Gruppierung |
| `time` | Zeitsteuerung und Abfrageregeln aus Abschnitt 6; null bei reinen aktuellen Werten |
| `appearance` | Vollständig aufgelöste wirksame Gestaltung aus Abschnitt 7 |
| `assetRefs` | Referenzen auf `assets[].assetId`, ohne automatische Fremdabrufe |
| `coverage` | Vollständige Feldbilanz aus Abschnitt 9 |
| `reference` | Optionaler begrenzter Vergleichsstand aus Abschnitt 6; sonst null |

`family` bezeichnet das JSLive-Fachmodell: `cartesian-history`, `pie`,
`gauge`, `radar-history` oder `progress`. Das ist keine automatische Zusage
eines heute vorhandenen ECharts-Zielmoduls.

`runtimeState` enthält typisierte Werte der zur Darstellung gehörenden
Steuervariablen einschließlich Idents und IDs, nicht nur Propertywerte. Bei
Chart sind dies mindestens `Period`, `Now`, `Relativ`, `Offset`, `StartDate`,
bei Radar mindestens `Period` und `Relativ`. Ein fehlender benötigter Wert
erzeugt einen blockierenden Erfassungsfehler. Exportzeitpunkt, aufgelöste
Fenstergrenzen und Zeitzone werden zusammen erfasst.

`original.globalConfiguration` und `appearance.global` erhalten zusätzlich die
wirksamen Parentfelder `DataMode`, `RefreshTime`, `EnableViewport`,
`viewport_content` und `CreateIPSView`. Diese Auswahl wird vor/nach der Erfassung
verglichen; Transportkennwörter und Adressen werden niemals mitkopiert.
Explizite Ausgabe-/Sichtbarkeitsflags des Kindes bleiben in der fachlichen
Originalkonfiguration erhalten.

Erfassung erfolgt ohne Ausführen eigener Templates/Skripte. Der Exporter liest
fachliche Konfiguration und Steuerzustand vor und nach der Erfassung erneut.
Bei Änderung verwirft er den Versuch; höchstens zwei Wiederholungen, danach
`SOURCE_CHANGED`. Laufende Messwertwechsel sind erlaubt, deshalb sind optionale
Vergleichswerte zusätzlich einzeln zeitgestempelt und keine atomare Aufnahme.

`excluded` benennt jeden ausgelassenen technischen Propertypfad samt Grund.
Transportkennwörter, Authentifizierungs-URLs, Debug-/Cacheinhalte, generierte
HTML-Ausgaben, `LastUploadedConfig` und eingebettete alte Importpakete gehören
nicht zum fachlichen Original. Es wird kein vollständiger Splitterexport
angehängt. Fachliche Werte werden nicht über `ignoreExport` entfernt.
Unbekannte fachliche Properties bleiben im Original und werden klassifiziert.
Fehlende Ressourcen/Metadaten dürfen in einem Diagnosepaket beschrieben werden;
ein solches Chart ist nicht als vollständig importierbar zu kennzeichnen.

Quellen sind allein durch Installationskennung und Variablen-ID identifiziert.
Namen und Pfade sind Anzeige-/Zuordnungshilfen, keine Ersatzschlüssel. Dieselbe
Quelle darf mehrere Bindungen haben, etwa Istwert und Vorjahresversatz.
`bindingKey` besteht aus `chartKey` und dem JSON-Pointer der Originalzuordnung,
beispielsweise `instance:12000#/Datasets/0`. Er ist keine neue Quellidentität.
Achsen gleicher Einheit bleiben getrennt, wenn JSLive sie getrennt konfiguriert.

## 5. Profilinformationen vollständig in native Darstellungen übersetzen

### 5.1 Darstellungsobjekt und Prioritäten

`sources[].presentation` enthält `original`, `effective`, `native` und
`conversion`. `original` bewahrt System-/Custom-Darstellung, Profilreferenzen
und vollständige Definitionen sämtlicher tatsächlich verwendeter Profile
einschließlich separat an Achsen/Reihen gewählter Profile. Ein Name allein reicht
nicht. Native Templates werden zu ihren wirksamen Werten aufgelöst; eine fremde
Template-ID allein ist ebenfalls kein portabler Vertrag.

`effective` enthält typisierte Daten für `kind`, `prefix`, `suffix`, `digits`,
`minimum`, `maximum`, `stepSize`, `icon`, `color`, `percentage`, `usageType`,
`decimalSeparator`, `thousandsSeparator`, `options`, `intervals` und `extra`.
Nicht anwendbare skalare Felder sind null, Listen leer. `extra` erhält weitere
native Parameter, etwa Datum/Uhrzeit, Dauer, Farbverlauf oder Multiline. Unbekannte
Parameter sind nicht stillschweigend ignorierbar. Optionen/Intervalle erhalten
Typen, Reihenfolge, Grenzinklusion, Umrechnungsfaktoren und alle Override-Schalter.

Die Quellbasis wird über `IPS_GetVariablePresentation` und die zugehörigen
Metadaten aufgelöst. System-/Custom-Informationen werden nicht pauschal blind
zusammengeführt. Auf dieser Basis gelten die im jeweiligen JSLive-Pfad tatsächlich
ausgewerteten Achsen-/Reihenprofile und expliziten Chart-Overrides. Jedes
wirksame Feld bekommt einen Herkunftspfad. Explizite Null, false, leere Texte
und transparente Farben dürfen nicht als fehlend behandelt werden. Eine
globale Vermutung wie „Custom-Profil schlägt immer Chart-Achsenprofil“ ist unzulässig.

Ein Quellobjekt beschreibt die Quellbasis. Unterschiedliche Formate derselben
Quelle je Bindung/Achse erhalten zusätzlich ihre eigenen Darstellungsobjekte
unter `bindings[].settings.presentation` beziehungsweise `axes[].presentation`.

`native` besteht aus `presentationId` (native GUID) und `parameters` (vollständig
aufgelöste gültige SDK-Parameter ohne `PRESENTATION`). Im Ziel wird daraus das
native Array mit `PRESENTATION`. Es enthält weder `PROFILE` noch eine Legacy-
Darstellungs-GUID oder eine Abhängigkeit von einem Quellprofil/-template.
JSON-Listen wie `OPTIONS`/`INTERVALS` bleiben im Austauschmodell echte Listen;
erst der SDK-Adapter kodiert sie entsprechend der Symcon-Schnittstelle.

`conversion` enthält `status=exact|blocked`, `ruleVersion`, `fieldCoverage`
und `issues`. Nur eine vollständige, geprüfte Umsetzung darf `exact` heißen.
Ein teilweise übersetztes Profil, eine unbekannte Darstellungsart oder fehlende
Profildefinition blockiert die Freigabe, statt auf „einheitenlos“ zurückzufallen.
Die reine Ablage des Originalprofils erfüllt die Übersetzungsanforderung nicht.
Bei `blocked` darf `native` null sein; fehlende Definitionen werden in `original`
als nicht auflösbare Referenzen mit Problem erfasst. Bei `exact` sind sämtliche
für diese Darstellungsart relevanten Felder und die native Definition Pflicht.

### 5.2 Verbindliche Zuordnung und Abgrenzung

| Ausgangsinformation | Zielvertrag |
| --- | --- |
| Präfix/Suffix | `PREFIX`/`SUFFIX` ohne Trimmen, automatische Leerzeichen oder Einheitenumrechnung |
| Genauigkeit/Trennzeichen | `DIGITS`, `DECIMAL_SEPARATOR`, `THOUSANDS_SEPARATOR`; Client-Vorgaben bleiben als solche erkennbar |
| Min/Max | Native Bereiche, soweit anwendbar, zusätzlich unveränderte fachliche Chart-Skala; 0/0 ist kein erfundener Bereich 0–100 |
| Schrittweite | `STEP_SIZE` nur bei einer geeigneten nativen Eingabe-/Sliderdarstellung; bei lesender Wertanzeige als Metadatum/Chart-Tickregel erhalten, nicht als erfundener SDK-Parameter |
| Standard-Icon/-Farbe | Native Vorgaben und ihre tatsächlichen Override-Regeln; fehlende Icons werden gemeldet |
| Boolean-/String-Assoziationen | Native Wertanzeige mit typisierten `OPTIONS`, Caption, Icon- und Farbüberschreibungen |
| Numerische Zustandsassoziationen | Native Konstantenintervalle für lesende Werte, geprüfte Originaltrefferregeln und Grenzen; keine automatisch bedienbare Enumeration |
| Numerische Format-/Farbbereiche | Native `INTERVALS`, einschließlich Konstanten, Faktoren, Präfix/Suffix, Genauigkeit, Icons, Farben und Grenzpriorität |
| Bestehende native Darstellungen | Aufgelöst mit allen relevanten Parametern erhalten; keine Rückübersetzung in ein Profil |
| Spezielle Legacy-Profile | Eigene geprüfte Regeln für Datum/Uhrzeit, Dauer, Farbe oder Webinhalt; keine allgemeine Zahlen- oder Slider-Vermutung |

Assoziationsnamen sind keine Wertumrechnungen. Texte, Icons und Farben müssen
auch für historische Werte funktionieren, nicht nur für den aktuellen Wert.
Bei numerischen Assoziationen muss die Implementierung exakte Treffer,
Zwischenwerte, Randwerte und Werte außerhalb der Liste gegen Symcon nachweisen.
Eine ungeprüfte Wahl von „nächstem Wert“, Schwellen oder Nullbreitintervallen
ist keine vollständige Übersetzung. Nicht abbildbare Regeln sind `blocked`.

`percentage` unterscheidet drei Dinge: das Suffix `%`, eine tatsächlich aktive
native Prozentformatierung und eine ausdrücklich gewählte Chart-Normalisierung.
Ein Suffix allein aktiviert keine neue Skalierung. Ein LQI-Rohwert von 128 im
Bereich 0–255 bleibt 128; nur eine bereits wirksame Prozentformatierung zeigt
gegebenenfalls den entsprechenden Prozenttext. Darstellung und Chartgewicht
werden getrennt geprüft. Es findet keine doppelte Normalisierung statt.

Auch eine native Intervallformatierung W→kW verändert ausschließlich den
formatierten Text. Rohwert, Archiv, Flächengewicht, Achsenzuordnung und
Berechnung bleiben unverändert, sofern JSLive nicht ausdrücklich eine eigene
fachliche Transformation anwendet. Diese wird dann separat ausgewiesen.

Native Wertanzeigen/Chart-Metadaten verleihen keine neue Bedienbarkeit. Die
Importerzeugung fügt keine Action an eine bestehende Quelle an und entfernt
keine bestehende Action. Ein read-only Zustand wird nicht durch eine
Enumeration mit Schaltflächen ersetzt. Slider-/Schalterdefinitionen vorhandener
Quellen werden erfasst, aber nicht auf diese Quellen zurückgeschrieben.

Profile und Darstellungsdefinitionen sind Export-Snapshots. Das Ziel verwendet
die bestätigten wirksamen Einstellungen; spätere Änderungen eines alten
JSLive-Profils ändern das importierte Design nicht heimlich. Eine spätere
Neusynchronisierung benötigt einen neuen geprüften Plan.

### 5.3 Quellen und bestehende Lücken

Verwendete offizielle Einstiege, geprüft am 31.10.2026:

- [IPS_GetVariablePresentation](https://www.symcon.de/en/service/documentation/command-reference/management-variables/ips-getvariablepresentation/)
- [IPS_GetVariableProfile](https://www.symcon.de/en/service/documentation/command-reference/management-variables/variable-profiles/ips-getvariableprofile/)
- [Value Presentation im SDK](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/presentations/value-presentation/)
- [Bedeutung von Prozentformatierung und Intervallgrenzen](https://www.symcon.de/en/service/documentation/components/object-presentation/value-presentation/)
- [Enumeration im SDK](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/presentations/enumeration/)
- [Slider im SDK](https://www.symcon.de/en/service/documentation/developer-area/sdk-tools/sdk-php/presentations/slider/)

Die aktuelle `EChartsVariablePresentation`-Auflösung deckt nur Min/Max,
Suffix und Genauigkeit ab. Der bestehende `VariablePresentationHelper` bietet
native Bausteine, ist aber kein vollständiger Profilkonverter. Insbesondere
SDK-/Helper-Abweichungen bei Intervallfarbfeldern (`Color`/`ColorValue`) müssen
vor Nutzung am tatsächlichen Ziel-SDK geprüft und zentral geklärt werden.
Die Quellen belegen die nativen Mechanismen, nicht eine bereits vollständige
Migration aller Profilvarianten. Diese bleibt Implementierungs-/Abnahmeumfang.

Die [synthetischen Präsentationsbeispiele](jslive-migration/presentation-examples.v1.json)
konkretisieren Präfix-/Leerzeichenerhalt, typisierte Boolean-Zustände mit Farben
und Prozenttext ohne zusätzliche Skalierung. Sie sind keine Importpakete,
keine vollständigen Familien-Fixtures und kein getesteter Konverteroutput.
`native.parameters` verwendet dort die Listenform des Austauschmodells; vor
SDK-Aufrufen werden `OPTIONS`/`INTERVALS` als JSON-Strings kodiert. Nicht gesetzte
Trennzeichen bleiben Client-Vorgaben; die Temperaturassertion fixiert für den
Vergleich ausdrücklich den Dezimalpunkt. Das LQI-Beispiel beschreibt die
wirksame JSLive-Zahlenformatierung, keine allgemeine Behauptung über Symcons
Legacy-Prozentdarstellung. Echte native Prozentformatierung bleibt separat.

## 6. Archivverträge und Zeitsteuerung

`sources[].archive` erfasst `archiveId`, `logging`, `aggregationType`,
`counterSettings` und `availability`, jeweils mit nachgewiesenen Werten oder
einem ausdrücklichen Problem. Ein aktueller Wert ersetzt keine Archivdefinition.

`time` enthält `timezone` (IANA), `selection` (originale Steuerwerte),
`resolvedWindow` mit `fromInclusive`/`untilExclusive` in Unix-Sekunden,
`rulesVersion` und die Liste `queries`. Jede Abfrageregel nennt Bindung,
Quellfenster, Anzeigeversatz, Roh-/Aggregatmodus, Aggregationsstufe, Reducer,
HighRes-Umschaltbedingung, Ausdünnung, Rundung, Vorgänger-/Endpunktverhalten,
Lückenregel und Behandlung laufender Intervalle. Die Originalparameter bleiben
zusätzlich erhalten. Die halboffene Planung ist an bestehenden inklusiven
Archiv-APIs ausdrücklich und getestet zu adaptieren, nicht blind weiterzureichen.

Kalenderjahr, Quartal und Monat sind Kalenderperioden in der Quellzeitzone,
keine festen Sekundenmultiplikatoren. Aufgelöste Fenster bei Exportzeitpunkt
sind Vergleichsdaten; eine gleitende Auswahl bleibt im Ziel gleitend.
Rückschauversatz wird anhand der ursprünglichen Periodenregel geplant. Ein
Zeitpunkt der Quelle und sein verschobener Anzeigepunkt bleiben unterscheidbar.

Historie wird auf derselben Installation anhand derselben Quell- und Archiv-IDs
gelesen. Keine Archivkopie, Reaggregation, Aktivierung/Deaktivierung oder
Typänderung. Bei fremder/unbestätigter Installationskennung müssen Quellen und
Archive ausdrücklich zugeordnet werden; gleichnamige Pfade reichen nicht.
Fehlende fremde Historie blockiert Gleichheitszusagen. Vollständiger
installationsübergreifender Archivtransfer ist nicht Teil von Format 1.0.

Zählerdifferenzen, Reset-/Betragsregeln, laufende Aggregate, Rundung und
Ersatzwerte werden nicht durch einen passenden klingenden Modusnamen ersetzt.
Bekannte Beispiele: JSLiveChart bildet rohe Zählerdifferenzen anders und kann
bei leerem Archiv aktuelle Ersatzlinien erzeugen; Radar-`counter` zählt in
bestimmten Pfaden Archivdatensätze, nicht Energieverbrauch. Solche Regeln
erhalten eine versionierte fachliche Kennung und einen unabhängigen Test.
Ohne gleichwertige Zielregel ist der Import `blocked`; eine sichere Abweichung
kann nur mit konkretem Bericht ausdrücklich als unvollständige Annäherung
bestätigt werden. Ungültige Werte/fehlende Quellen sind nicht überstimmbar.

`reference` kann ein begrenztes Vergleichsfenster enthalten: Erfassungszeit,
Quell-/Anzeigestempel, Originalwerte, protokollierte Verarbeitungsschritte,
berechnete Werte und formatierte Texte. Höchstens 2000 Punkte je Bindung und
32000 je Paket; ein größeres Fenster wird als nicht vollständig markiert oder
gezielt verkleinert. Ein Vergleichsstand ist keine portable Vollhistorie und
wird niemals in Symcon-Archive zurückgeschrieben. Referenzdaten sind optional,
der fachliche Regel-/Metadatenexport ist verpflichtend.

Vor Laden/Import werden sämtliche Zielbudgets geprüft. Keine verdeckte
Quellenkürzung, kein Wechsel auf gröbere Aggregate nur zum Bestehen der Prüfung
und keine Erhöhung von Schutzbudgets durch den Import. Ein bewusst geänderter
Zeitraum oder eine Aufteilung benötigt einen neuen sichtbaren Plan.

## 7. Darstellungstreue und Familienzuordnung

`appearance` wird pro Ausgabekontext aufgelöst. Es enthält Titel/Sichtbarkeit,
Schriftfamilien, Größen in ursprünglichen Einheiten, Farben und Alpha getrennt,
Hintergrund, Abstände, Größen-/Seitenverhältnis-Vorgaben, Legende, Tooltip,
Wertbeschriftung, Linien/Punkte/Füllungen, Skalen/Ticks, Stapelung und Animation.
Reihen-/Achsen-Overrides und versteckte Elemente bleiben erhalten. Originale
Winkel und Einheiten werden samt dokumentierter Zielumrechnung erfasst.

JSLive besitzt keine zwei gleichartigen nativen Kachel-/IPSView-Designer wie
SymconECharts. Ein JSLive-Ausgangsdesign dient daher als ausdrücklich geprüfte
Basis für beide neuen Ausgaben; bestehende eigene ECharts-Designs werden nicht
überschrieben. Kachelheader-Inset und IPSView-Frame sind Ausgabeadapter, keine
Anweisung, alte Inhalte abzuschneiden. Explizite Altfarben/-schriften haben
Vorrang vor automatischen ECharts-Themes. Größenvergleiche erfolgen bei gleichen
Inhaltsabmessungen, Theme, Schriftressourcen und Pixelverhältnis.

| JSLive-Variante | Geplante Zuordnung und bekannte Lücke |
| --- | --- |
| Chart Linie/Fläche | TimeSeries; Achsenidentität, Mehrfachbindungen, Zeitversatz und vollständiges Altstyling ergänzen |
| Chart Balken | BarHistory; Stapelung, Achsen und individuelle Reihenregeln abgleichen |
| Chart gemischt Linie/Balken | Gleichzeitiges historisches Mischmodell fehlt; eigene Architekturentscheidung vor Implementierung, kein automatisches Aufteilen in getrennte Bilder |
| DoughnutPie ein Kreis/Ring | Pie mit Originalreihenfolge, Grenzen, Farben und Formatierung |
| DoughnutPie mehrere Ringe | Mehrfachringmodell fehlt; Ringe nicht zu einem Kreis zusammenwerfen |
| Gauge radial | GaugeSingle als Grundlage; Ticks, Highlights, Winkel und automatische Wertkonvertierung abgleichen |
| Gauge linear/Kompass | Gleichwertige Geometrie-/Skalenprüfung erforderlich; keine pauschale Ersetzung durch radiales Gauge |
| RadarChart historisch | Historisches Klassen-/Referenzmodell fehlt; kein Mapping auf das heutige aktuelle Radar |
| Progressbar Fill/SVG | PictorialBar nur bei gleicher Füllgeometrie und Formatierung |
| Progressbar Stroke | Pfadfortschritt gesondert abbilden; Symbolfüllung allein ist nicht gleichwertig |

ColorPicker, DateTimePicker, AdvTextfield und beliebige Custom-Ausgaben bleiben
als nicht abgedeckte Funktionen in JSLive erhalten. Namen von Zielmodulen in
dieser Tabelle vergeben keine neuen GUIDs/Präfixe und erweitern keine bestehenden
Familienverträge ohne gesonderte Entscheidung.

## 8. Ressourcen und eigene Anpassungen

Ein Asset nennt `assetId` (SHA-256 der Bytes, mit Präfix `sha256:`), `kind`,
`mediaType`, `byteLength`, `sha256`, `contentBase64` und `origin` einschließlich
Herkunft und Lizenz, soweit bekannt. Mehrfachnutzung referenziert dieselben
Bytes. Länge und Hash werden vor Nutzung geprüft; Hashes sind keine Signaturen
und kein Vertrauensnachweis. UTF-8-Text wird nicht vor Hashprüfung normalisiert.

Lokale benötigte SVGs/Bilder/Schriften werden vollständig eingebunden oder als
fehlend gemeldet. Standardressourcen dürfen über eine fest versionierte,
hashgeprüfte Zuordnung ersetzt werden, wenn deren Lieferung im Ziel gesichert
ist. Ein JSLive-Hookpfad ist keine dauerhafte Zielabhängigkeit. Fremde URLs
werden weder beim Export noch beim Import automatisch abgerufen.

Eigene Templates und Bibliotheksskripte werden nur nach ausdrücklicher Auswahl
als inerte Textressourcen beigefügt; andernfalls nennt der Bericht die bewusst
fehlenden Inhalte. Sie werden weder ausgeführt noch ungeprüft in HTML, CSS,
ECharts-Optionen oder PHP übernommen. Ein eigener Templateverweis blockiert
die Behauptung vollständiger automatischer Darstellungstreue, bis sein Einfluss
geprüft beziehungsweise die Abweichung ausdrücklich akzeptiert wurde.

Es werden keine Chart.js-/CanvasGauges-/Loading-Bar-Engines als vermeintliche
ECharts-Ziele importiert. SVG- und Bildadapter behalten ihre strengeren
Schutzregeln. Die unterschiedlichen Library-/Ressourcenlizenzen werden bei
tatsächlicher Code-/Assetübernahme separat geprüft. Lizenzunbekannte Ressourcen
werden nicht stillschweigend als mitlieferbar behandelt.

## 9. Vollständigkeitsbilanz und Importbericht

`coverage` klassifiziert jedes fachliche Originalfeld, jeden wirksamen Default,
jeden erforderlichen Steuerwert und jede Abhängigkeit über einen JSON-Pointer:
`exact`, `approximate`, `unsupported` oder `not-applicable`. Zu jedem Eintrag
gehören Zielpfad, Regelkennung und Begründung. Ein unbekanntes Feld ist niemals
automatisch `not-applicable`. Nicht gelesene Daten erhalten keinen Erfolgsstatus.

Ein Problem hat `code`, `severity=info|warning|error`, `chartKey`, `pointer`,
`message`, `effect` und `resolution`. Mindestcodes: `FORMAT_UNSUPPORTED`,
`PACKAGE_INVALID`, `LIMIT_EXCEEDED`, `SOURCE_CHANGED`, `SOURCE_UNRESOLVED`,
`ARCHIVE_UNAVAILABLE`, `PROFILE_UNRESOLVED`, `PRESENTATION_UNSUPPORTED`,
`CALCULATION_UNSUPPORTED`, `DESIGN_APPROXIMATED`, `ASSET_UNAVAILABLE`,
`CUSTOM_CODE_INERT`, `TARGET_CHANGED`. Meldungen enthalten keine Zugangsdaten.

Der Importer prüft die Exportbilanz unabhängig erneut und bildet drei getrennte
Urteile für Quellen/Archiv, native Darstellungen und Chartgestaltung. Eine
optionale Darstellungsannäherung erhält eine konkrete, einzeln bestätigte
Abweichung und niemals das Gesamturteil „vollständig übernommen“. Fehlende
Quellen, ungültige Pakete, unvollständig übersetzte Profile oder Schutzverletzungen
bleiben blockierend. Berechnungsannäherungen müssen zusätzlich Original- und
Zielregel samt Auswirkung nennen; keine pauschale Freigabe aller Warnungen.

## 10. Vorschau, Bestätigung, Wiederholung und Rückfall

1. **Analysieren:** Paket lokal prüfen, Quellen/Archive lesen, Ressourcen und
   Zielunterstützung prüfen. Keine Zielinstanz, kein Gateway und kein Skript
   wird hierbei erzeugt. Metadaten-Snapshots mit dem aktuellen Zustand vergleichen.
2. **Planen:** Quellenzuordnung, native Darstellungen, Zielkonfigurationen,
   Budgets und komplette Feldbilanz erzeugen. Neue Ziele und gemeinsame
   Gateway-Verwendung ausdrücklich anzeigen. Vorschau nutzt genau diesen Plan.
3. **Vergleichen:** Original-/Zielwerte und Darstellung im gleichen Fenster
   gegenüberstellen. Synthetische Entwürfe deutlich von echten Daten-/Bild-
   Nachweisen unterscheiden. Vorschau führt kein eigenes Fremdtemplate aus.
4. **Bestätigen:** Eigentümer bestätigt den konkreten Plan einschließlich
   aufgeführter Abweichungen. Planfingerabdruck bindet Paket, Quellenzuordnung,
   Regelversion, Zieldefinition und Darstellungen. Neue Änderungen machen ihn
   ungültig und verlangen eine neue Vorschau.
5. **Anlegen:** Unter Sperre prüfen, ob der Plan weiterhin gilt, dann neue
   Instanzen erzeugen, vorhandenes Gateway auswählen oder ein ausdrücklich
   geplantes neues verbinden, native Ausgaben konfigurieren und Ergebnis prüfen.
6. **Abschließen:** Ursprungs-/Zielzuordnung und Ergebnis dauerhaft speichern.
   Original bleibt nutzbar; neue Ausgabe-IDs/Links und manuelle Platzierung in
   Kachel/IPSView werden angezeigt, keine alten Widgetreferenzen überschrieben.

Wiederholungsidentität ist `(Installationskennung, Ursprungsinstanz-ID)`; der
Export-/Planfingerabdruck bezeichnet die jeweilige Revision. Neue Export-UUIDs
rechtfertigen keine doppelten Ziele. Dasselbe bestätigte Paket liefert nach
Erfolg dieselben Ziel-IDs. Ein geänderter Export oder ein inzwischen manuell
geändertes Ziel überschreibt nichts: neuer Vergleich und ausdrückliche
Entscheidung über separate Neuanlage; Zielaktualisierung ist nicht Teil von 1.0.

Ein dauerhaftes Importjournal speichert je Chart `planned`, `created`,
`configured`, `verified`, `complete` beziehungsweise `failed` und die erzeugten
IDs. Erfolg wird erst nach Verifikation markiert. Ein Wiederanlauf prüft die
erfassten Objekte und setzt den passenden Schritt fort. Die Anlageabsicht wird
vor dem ersten Erzeugungsaufruf dauerhaft gespeichert; Besitzer-/Ursprungsmarker
werden unmittelbar danach gesetzt. Ein Abbruch zwischen Erzeugung, Markierung
und Journalfortschritt rechtfertigt keinen erneuten blinden Erzeugungsaufruf.
Nicht eindeutig rekonstruierbare Restobjekte blockieren den Wiederanlauf bis
zur ausdrücklichen Zuordnung; gleiche Namen sind kein Nachweis. Eine einzelne
Symcon-Erzeugung wird nicht als atomare Transaktion mit dem Journal vorausgesetzt.

Teilimporte sind pro Chart nachvollziehbar, nie pauschal erfolgreich. Keine
automatische Löschung vorhandener oder zwischenzeitlich bearbeiteter Ziele.
Ein ausdrücklich bestätigtes Entfernen ausschließlich eigener unveränderter
Importobjekte ist ein separater Rückfallschritt. JSLive-Originale, gemeinsame
Gateways und Quellen werden niemals Teil einer automatischen Aufräumaktion.

## 11. Abnahmekriterien vor Veröffentlichung

Die Spezifikation ist keine Implementierungs- oder Live-Abnahme. Folgende
Nachweise sind vor Freigabe der jeweiligen Variante verpflichtend:

| Kennung | Nachweis |
| --- | --- |
| C01 | Hersteller-/Verbraucher-Vertrag mit vollständigen anonymisierten Familien-Fixtures; Versions-/Typ-/Grenzfehler ohne Schreibaktion |
| C02 | Vollständige fachliche Propertybilanz einschließlich deaktivierter Reihen, Defaults, JSON-Strings und fehlender Abhängigkeiten |
| P01 | System-/Custom-/Achsenprofile und bereits native Darstellungen mit gleicher wirksamer Formatierung; keine Zielabhängigkeit vom Quellprofil |
| P02 | Boolean/String, numerische Zustände, Grenz-/Zwischen-/Außenwerte, Icon, Farbe, Transparenz, Präfix/Suffix-Leerzeichen, Genauigkeit, Schrittweite und Trennzeichen |
| P03 | Prozent 0–100, LQI 0–255, negative Temperatur, W/kW-Intervalle und Konstantentexte ohne Änderung der Rohwerte oder doppelte Skalierung |
| P04 | Native SDK-Felder am Ziel geprüft, einschließlich Intervallfarben und korrekter read-only/Action-Trennung; unbekannte Profile blockieren |
| A01 | Identische unabhängige Roh-/Aggregatfixtures durch echte JSLive- und ECharts-Datenpfade; Fenster, Zeitstempel, Lücken, Rundung und Zählerregeln vergleichen |
| A02 | DST, Schaltjahr, Monats-/Jahreswechsel, historische Grenzen, laufende Intervalle, Reihenversatz und doppelte Quellbindung |
| A03 | Quellen-/Archiv-/Design-Snapshots vor/nach Export, Vorschau, Erfolg und Fehler unverändert; keine SetValue-/AC-Schreiboperationen |
| V01 | Reale Standardvorlagen mit festem Datenstand und gleichen Abmessungen/Schriften in Kachel und IPSView; jede sichtbare Abweichung bewertet |
| V02 | Mehrere Achsen gleicher Einheit, gemischte/gestapelte Reihen, mehrere Donut-Ringe, Gauge-Varianten, historisches Radar und SVG-Fill/Stroke getrennt |
| I01 | Zweimalimport, neuer Export derselben Ursprungsinstanz, parallele Bestätigung und Abbruch nach jeder Anlage-/Konfigurationsphase ohne Dubletten |
| I02 | Veränderte Quellen/Profile/Ziele zwischen Vorschau und Bestätigung verhindern veraltete Schreibpläne; Original bleibt funktionsfähig |
| S01 | Fremdinstallation verlangt explizite Zuordnung; falsche IDs/Typen/Archive blockieren statt Namensersatz |
| S02 | Skripte/HTML/CSS/URLs, ungültige SVGs, unbekannte Erweiterungen, übergroße/defekte Ressourcen, doppelte JSON-Schlüssel und Hashfehler kontrolliert ablehnen |

Quellwerte/IDs und Zeitstempel sind exakt zu vergleichen. Berechnete numerische
Werte dürfen nur eine pro fachlicher Regel dokumentierte Rechentoleranz nutzen;
kein globales großzügiges Epsilon oder Rundungsverschleiern. Texte, Zustände,
Einheiten und Reihenfolge müssen exakt sein. Bildvergleiche trennen tolerierbare
Kantenglättung von verschobener Geometrie, fehlenden Reihen oder falschen Farben.

## 12. Umsetzungsfolge und Freigabestatus

1. Vertrag und beidseitige ADRs: mit diesem Schritt spezifiziert.
2. Gemeinsame Profil-/Darstellungsnormalisierung und vollständiger JSLive-Export:
   erster Producer für die fünf Chartfamilien und gemeinsame Snapshots lokal
   implementiert; konkrete öffentliche APIs und Prüfgrenzen stehen im
   [Exportbericht](../../JSLive/docs/ECHARTS_EXPORT.md). Jeder Aufruf erzeugt ein
   vollständiges Einzelchart-Paket. Weitere Konvertierungsregeln (insbesondere
   Sonderprofile und dynamische Assoziationstexte), reguläre Helper-Verteilung
   und reale Formular-/HTTP-Abnahme bleiben offen.
3. Lesender ECharts-Parser, Vollständigkeitsbericht und konkrete Vorschau: offen.
4. Fehlende Ziel-Fachmodelle und familienweise geprüfte Mapper: offen.
5. Bestätigte Anlage, Importjournal, Wiederanlauf und gemeinsame Live-Abnahme:
   offen.

Die öffentliche Übernahmezusage ist damit noch nicht erfüllt. Die erste
SymconECharts-Beta bleibt auf Eigentümerentscheidung bis zur vereinbarten
Migrationsabdeckung zurückgestellt. Vor Einreichung sind tatsächlicher Umfang,
offene Varianten und Release-Metadaten erneut abzugleichen. Es gibt durch diese
Spezifikation keine neue Library-Version, keinen Termin und keine Veröffentlichung.
