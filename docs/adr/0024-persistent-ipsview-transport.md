# ADR 0024: IPSView-Diagramme persistent aktualisieren

- Status: Angenommen
- Datum: 2026-10-06
- Entscheider: Burki24
- Ersetzt: ADR 0008 und ADR 0022 hinsichtlich der Aktualisierung bei Wertänderungen

## Kontext

Die IPSView-Ausgabe liegt als vollständiges HTML-Dokument in einer stabilen
WebContent-Variable. Bisher wurde diese Variable bei jeder Wertänderung neu
geschrieben. IPSView lädt dabei das gesamte Dokument samt ECharts-Runtime,
Canvas, SVG-Assets und aktuellem Datenmodell erneut. Das erzeugt sichtbares
Flackern und verwirft den lokalen Browserzustand wie Zoom oder eine im
Echtzeitmodus aufgebaute Punktfolge.

Die nativen Kacheln verwenden dagegen bereits `UpdateVisualizationValue()`
und aktualisieren das bestehende ECharts-Objekt. Das als Referenz geprüfte
JSLive/Chart.js hält sein Browserdokument ebenfalls offen und transportiert
Messwertänderungen über einen WebSocket.

## Entscheidung

- Die WebContent-Variable bleibt der stabile IPSView-Einstieg und enthält
  weiterhin ein vollständiges, eigenständig startfähiges HTML-Dokument.
- Konfigurationsänderungen, Aktivierung, manuelle Regeneration und ein
  Kernelstart dürfen dieses Dokument neu erzeugen. Normale Messwert- und
  Archivaktualisierungen schreiben die Variable nicht mehr neu.
- `EChartsGateway` registriert zentral `/hook/SymconECharts`. Der Gateway ist
  für WebHook, WebSocket-Verteilung und die Weiterleitung einer aktuellen
  Zustandsabfrage zuständig; familienbezogene Zustände verbleiben in den
  jeweiligen Gerätemodulen.
- Jedes Gerät besitzt einen zufälligen, persistenten 128-Bit-Kanalbezeichner.
  Er wird weder als Property noch in Debugausgaben geführt. Gateway und Gerät
  prüfen Instanz-ID, exakten Kanal und aktivierte IPSView-Ausgabe, bevor ein
  Zustand ausgeliefert wird.
- Das Datenprotokoll Version 1 wächst additiv um `ipsview.push` und
  `ipsview.state`. Bestehende Operationen und Datenfluss-IDs bleiben
  unverändert.
- Beim Öffnen und nach einer Wiederverbindung lädt der Browser einen frischen
  Gesamtzustand. Danach empfängt er vollständige Zustände oder – bei Time
  Series in `raw` und `realtime` – einzelne `append`-Nachrichten über den
  WebSocket und übergibt sie an den vorhandenen `handleMessage()`-Renderer.
- Der eingebettete Bootstrap-Zustand bleibt als funktionsfähiger Rückfall
  erhalten, falls der Transport vorübergehend nicht erreichbar ist.
- PHP-seitige Transportsemantik und Browser-Verbindungslogik liegen
  repositoryweit zentral in `libs/EChartsIPSViewTransport.php` und
  `libs/echarts/ipsview-transport.js`. Chartmodell und Rendering bleiben in
  den Familienmodulen.

### Adressauflösung in eingebetteten Dokumenten

WebContent kann als `data:`-Dokument mit opakem Ursprung eingebettet sein.
Der Zugriff auf `window.top.location` ist dann trotz fehlendem Sandbox-Attribut
verboten; aus der eigenen `data:`-Adresse lässt sich kein WebHook-Host bilden.

Der gemeinsame Browsertransport verwendet deshalb den ersten gültigen
HTTP(S)-Ursprung aus eigener Dokumentadresse, browserseitig aufgelöstem
`document.baseURI`, zugänglicher Elternadresse, `document.referrer`,
`location.ancestorOrigins` und zuletzt der zugänglichen obersten Adresse.
Basis- und Einbettungsmetadaten müssen auf den Symcon-Server verweisen.
Pfad, Suchparameter und Fragment werden nicht übernommen; nicht absolute,
opake und mit Zugangsdaten versehene Adressen werden verworfen.
HTTP-Zustandsabfrage und WebSocket verwenden denselben ermittelten Ursprung,
bei HTTPS mit WSS. Die bestehende Fetch-Credential-Policy `same-origin`,
Kanalprüfung und serverseitige CORS-Antwort bleiben unverändert.

Ohne brauchbare Serveradresse bleibt der Bootstrap sichtbar und die begrenzte
Wiederverbindung aktiv. Es wird weder ein Host geraten noch ein Basis-Element
eingefügt. Hosts ohne zugängliche Elternadresse, Basisadresse, Referrer und
Ancestor-Ursprung können keine Live-Verbindung herstellen. Neue Properties,
Konfigurationsmigrationen oder Änderungen an Quellen sind nicht erforderlich.

## Folgen

IPSView behält dasselbe Dokument und dasselbe ECharts-Objekt über
Wertänderungen hinweg. Dadurch bleiben Zoom und Echtzeitpunkte erhalten und
das durch vollständige WebContent-Neuladungen verursachte Flackern entfällt.
Ein Konfigurationswechsel lädt die Seite weiterhin bewusst neu, weil Runtime,
Theme, CSS oder SVG-Assets betroffen sein können.

Der Gateway erhält eine neue familienübergreifende Infrastrukturaufgabe und
benötigt den vorhandenen WebHook Control. Fällt der WebSocket vorübergehend
aus, verbindet sich der Browser mit begrenztem Backoff neu und synchronisiert
anschließend den vollständigen Zustand.

Lokale PHP- und JavaScript-Tests prüfen Routing, Kanaltrennung, unveränderte
WebContent-Dokumente bei Wertänderungen sowie Zustands- und
WebSocket-Nachrichten. Der Eigentümer bestätigt den regulären Betrieb im tatsächlichen IPSView.
Das belegt nicht jede Transportfehler- oder Wiederverbindungsmatrix; deren
Nachweise bleiben nach Testebene getrennt.
