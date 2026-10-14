# ADR 0046: Reinen Tree und Anlagenzustand trennen

- Status: Angenommen
- Datum: 2026-10-14
- Entscheider: Burki24
- Ersetzt die Modulzuordnung aus ADR 0045; ergänzt ADR 0044

## Kontext

Die einfache IP-Symcon-Objekthierarchie und die explizite Bewertung von
Anlagenzuständen haben unterschiedliche Konfigurationsaufgaben. Die
Statusregeln und ihre Massensuche machten das Tree-Formular umfangreich und
schränkten eine reine Hierarchie-Instanz unnötig ein. Die einzige bisherige
Tree-Instanz war eine Testinstanz und wurde vor der Trennung gelöscht. Die
etwa 70 dort eingetragenen Testregeln müssen daher nicht migriert werden.

## Entscheidung

`EChartsTree` bleibt das einfache, ausschließlich lesende Hierarchiemodul mit
der bestehenden Modul-GUID `{2B2C26A3-6F92-48D0-ADE1-5F03D7282EF8}`
und dem Präfix `ECTR`. Es besitzt weder `StatusRules` noch Zustandsfarben,
Statussuche, Statusvalidierung oder Statusdarstellung.

`EChartsTreeSystemHealth` erhält die neue Modul-GUID
`{DE4A700B-A38A-4441-A73A-C5F1D7DCCBE2}` und das Präfix `ECTH`. Es
übernimmt den fachlichen Vertrag aus ADR 0045: ausdrücklich gewählte
Bool- und Zahl-Variablen, Warn- und Kritisch-Schwellen, Suche und
Massenübernahme, Aggregation des schlechtesten Zustands sichtbarer Nachkommen
sowie getrennte Zustandsfarben für Kachel und IPSView. Die öffentliche
Funktionspräfixe der Statusformulare werden entsprechend `ECTH_`.

Beide Module lesen ihre Hierarchie mit der gemeinsamen Klasse
`libs/TreeHierarchy.php` und verwenden denselben Gateway-Datenfluss. Beide
behalten zunächst den Variablennamenfilter; seine spätere Bedienung und
Kopplung an die Statussuche wird gesondert entschieden. Familienbezogene
Properties und Renderer bleiben in ihren jeweiligen Modulen. Die
Visualisierungsmodelle beider Module verwenden weiterhin die Familie `tree`.

## Kompatibilität und Prüfung

Es gibt keine Instanzmigration und keine automatische Übertragung von
`StatusRules`. Neue Health-Instanzen werden frisch angelegt. Die bestehende
Tree-GUID wird nicht für Health umgedeutet; gespeicherte einfache
Tree-Konfigurationen behalten damit ihre Identität. Die vor der Trennung
unveröffentlichten Status-Properties werden aus dem einfachen Modul entfernt.

Struktur-, Formular-, PHP- und Renderer-Tests prüfen die zwei Identitäten und
die Trennung der Zuständigkeiten. Eine reale Kachel- und IPSView-Prüfung der
neuen Health-Modulidentität ist nach dem Modulupdate erforderlich; reine
Repository-Prüfungen ersetzen sie nicht.
