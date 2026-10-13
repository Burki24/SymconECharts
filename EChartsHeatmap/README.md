# EChartsHeatmap

`EChartsHeatmap` zeigt eine numerische Archivvariable als 7 × 24-Heatmap:
Wochentage von Montag bis Sonntag und lokale Stunden von 0 bis 23 Uhr. Eine
gemeinsame `EChartsGateway`-Instanz genügt auch für diese Diagrammfamilie.

## Voraussetzungen und Daten

Benötigt werden IP-Symcon 9.0/9.1, eine numerische Integer- oder
Float-Variable mit aktiviertem Archiv und das verbundene EChartsGateway.
Die Variable wird über ihre ID zugeordnet; ihr Name ist kein technischer
Schlüssel.

Wählbar sind die letzten 7 oder 30 Tage. Das Gateway liefert ausschließlich
abgeschlossene stündliche Durchschnittswerte. Werte derselben lokalen
Wochentag-Stunde-Kombination werden erneut arithmetisch gemittelt. Eine
fehlende Stunde bleibt leer, nicht null. Beim Wechsel von Sommer- auf
Winterzeit gehen beide tatsächlich vorhandenen 02-Uhr-Intervalle in dieselbe
Zelle ein. Beim umgekehrten Wechsel wird keine 02-Uhr-Zelle erfunden.
Tooltip und optional sichtbare Zellenwerte verwenden Einheit und
Nachkommastellen aus der Variablendarstellung oder den manuellen Angaben.

## Design und Ausgabe

Der Kacheldesigner enthält Theme, Farben der Werteskala, sichtbare Farbskala,
optionale Zellenwerte, Textgrößen und -farben sowie Animation. Die Farbskala
zeigt ihren kleinsten und größten Archivwert mit der gewählten Einheit an.
Die Formularvorschau verwendet ausdrücklich Beispieldaten und liest beim
Bearbeiten kein Archiv. Das Diagramm selbst zeigt leere Felder ohne
Interpolation.

Die native Symcon-Kachel und ein optionales IPSView-WebContent-Widget verwenden
denselben Archivwert. Das IPSView-Design kann das Kacheldesign erben oder
unabhängig eingestellt werden. Bei einem vorübergehenden Ladefehler bleibt
eine zuletzt gültige Grafik sichtbar und die Archivabfrage wird beim nächsten
Aktualisierungsintervall wiederholt.

## Funktionen

```php
$json = ECHM_GetHeatmapData($InstanceID);
$html = ECHM_GetIPSViewHTML($InstanceID);
```

## Lizenz

Lizenz der eigenen Beiträge:
[PolyForm Noncommercial License 1.0.0](../LICENSE).
