# ADR 0107: Gemeinsamer HTTP-Hook bei mehreren Gateways

- Status: Angenommen
- Datum: 2026-10-28
- Ergänzt: ADR 0024

## Kontext

Alle Gateway-Instanzen registrierten denselben flüchtigen SDK-Hook. Eine neue
Instanz übernahm ihn und erreichte nur ihre eigenen Datenflusskinder. Nach ihrem
Entfernen wurde der ursprüngliche Hook nicht automatisch wiederhergestellt.
Der dokumentierte zulässige Mehr-Gateway-Betrieb darf bestehende URLs und
Kanäle nicht ungültig machen.

## Entscheidung

- Die niedrigste nicht zum Löschen vorgesehene Gateway-ID registriert den
  unveränderten Hook. `Create`, `ApplyChanges` und Kernelstart gleichen die
  flüchtigen Registrierungen aller vorhandenen Gateways ab.
  Öffentliche Peer-Aufrufe erfolgen ausschließlich bei Status `IS_ACTIVE`;
  während eines Reloads vorhandene Kernelobjekte besitzen noch nicht zwingend
  eine verfügbare PHP-Modulschnittstelle. Dieser Fall bleibt ohne Fremdaufruf.
- Gateways abonnieren gegenseitig `IM_DELETE`. Nach dem tatsächlichen Entfernen
  einer Instanz übernimmt der verbleibende Eigentümer automatisch. Es entstehen
  keine zusätzlichen Ereignisobjekte, Polling-Timer oder Benutzer-Properties.
  Die Nachrichtenverarbeitung ist asynchron; ein kurzer HTTP-404 während der
  Übergabe bleibt ein vorübergehender Verbindungsfehler und kein Dauerzustand.
- Der HTTP-Handler ermittelt den tatsächlichen Datenflussparent des Zielgeräts.
  `ECGW_GetTransportState` lässt nur dessen eigene Kinder zu und verwendet dort
  den bisherigen `SendDataToChildren`-/`ReceiveData`-Vertrag. Andere Parentmodule,
  ungültige IDs, Kanäle, Ausgaben und Zeitfenster bleiben ausgeschlossen.
- Die Kanalautorisierung und IPSView-Aktivierung prüft weiterhin das Zielgerät.
  Token und vollständige Zustände bleiben in der Diagnose geschützt.
- `ECGW_RefreshTransport` erneuert ausschließlich flüchtige Registrierungen und
  Löschabonnements. Beide neuen öffentlichen Funktionen sind Transportbausteine;
  der normale Benutzerbetrieb benötigt keine manuellen Aufrufe.
- HTTP-/WebSocket-Pfade, gespeicherte Kanäle, Modellverträge, Konfigurationen,
  Designs und getrennte Archivcaches bleiben unverändert.

Die Logik bleibt im Gateway. Der vorhandene Helper bietet Ressourcen und
Interaktion, keine Koordination mehrerer Eigentümer eines Bibliothek-Hooks.
Die individuellen Hooks anderer Libraries sind kein belegter zweiter Anwender.

## Nachweise

`php tests/gateway_gauges.php --gateway-transport` reproduzierte zunächst die
unzulässige Übernahme durch ein zweites Gateway. Der korrigierte Lauf prüft
unterschiedliche ID-Reihenfolge, beide Datenflussparent-Gruppen, Kanal-/Offset-
Ablehnung, native Navigation, wiederholte Eigentümerlöschung und Kernelstart.
Die Funktion ist zusätzlich in die gemeinsame Gateway-Integration eingebunden.

Die Symcon-9.1-Live-Prüfung bestätigt HTTP-Zustände aller 29 Diagrammfamilien
über zwei Gateways und automatische Wiederherstellung nach Eigentümerlöschung.
Direkt im Löschaufruf war einmal HTTP 404 sichtbar; im nächsten unabhängigen
Abruf antwortete derselbe unveränderte Zustandspfad automatisch wieder mit 200.
