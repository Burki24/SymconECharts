# 0118 – Windrose für aktuelle Windmesswerte

## Entscheidung

Eine eigene Geräteinstanz `EChartsGaugeWind` mit dem Präfix `ECWI` bezieht
die aktuelle Windrichtung und optional die Windgeschwindigkeit über das
vorhandene Gateway. Die Richtung ist ein zyklischer Winkel: 0° liegt im
Norden, 90° im Osten. Der Zeiger weist in die Richtung, aus der der Wind
kommt. Messwerte außerhalb einer Umdrehung werden auf den Kreis abgebildet.
Die Geschwindigkeit ist ein separater nichtnegativer Messwert. Die Einheit
beschriftet ihren Originalwert. Eine fehlende Quelle bleibt von gemessener
Windstille unterscheidbar.

Die Windrose benötigt feste Himmelsrichtungen, einen geschlossenen
360°-Maßstab und eine unabhängige Animation der Windgeschwindigkeit. Sie
erhält deshalb einen eigenen Renderer und nutzt dieselbe lokal ausgelieferte
Gauge-Laufzeit wie die bestehenden Instrumente. Das Rotorblatt verwendet die
ECharts-Grafikkomponente mit einer wiederholten Drehung. Die Drehzahl dient
als qualitative Anzeige. Sie erzeugt keine Messwerte oder Symcon-Timer.

Kachel und IPSView nutzen dasselbe Datenmodell und denselben Renderer.
Beide Designer verwenden die vorhandenen Theme-, Farb- und Vorschaukonventionen.
Die Formularvorschauen zeigen die Windrose als Standbild. Bei
deaktivierter Animation oder reduzierter Bewegung bleibt das Blatt in Ruhe.
