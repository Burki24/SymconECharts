# 0117 – Zeitraumauswahl je geöffneter Archivansicht

## Entscheidung

Reguläre Archivdiagramme mit mehreren sinnvollen Zeitfenstern können ein
einzelnes Auswahlfeld unter der Zeichenfläche einblenden. Beide Designer
steuern es mit `EnablePeriodSelection` bzw. `IPSViewEnablePeriodSelection`.
Der Standard ist aus. Das konfigurierte Fenster bleibt der Startwert und
lässt sich über den Standardeintrag erneut laden. Der Zoom verkleinert den
Ausschnitt des bereits geladenen Fensters.

Der Browser hält die Auswahl im geöffneten Dokument. Fensterwechsel schreiben
weder Instanz-Properties noch gemeinsam genutzte Variablen. Der bestehende
authentifizierte GET-Zustandsabruf akzeptiert den optionalen Parameter
`period`. Gateway und Kind prüfen Zugriffskanal und Ausgabe weiterhin.
Das Gerät prüft zusätzlich, ob seine Ausgabe und das aktuelle Datenmodell das
gewählte Preset erlauben. Die bestehenden Archivplaner bestimmen Zeitraum,
Auflösung und Ladebudget. Navigation innerhalb eines Fensters kann weiterhin
`offset` verwenden. Alternative Abfragen ändern den regulären Instanzstatus
und die gespeicherte Fehlerdiagnose nicht.

Ein späterer Auswahlwechsel hat Vorrang vor einer älteren Antwort. Fehler
beim Transport werden beim Auswahlfeld angezeigt. Eine alternative Ansicht
lädt bei geöffnetem, sichtbarem Dokument periodisch ihr eigenes Zeitfenster.
Reguläre Push-Nachrichten gehören weiterhin zum konfigurierten Fenster.
Bestehende Aufrufer und Ausgaben ohne aktivierte Auswahl verwenden den
bisherigen Transportvertrag.
