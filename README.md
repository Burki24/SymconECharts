# SymconECharts

Eigenständiges Symcon-Projekt für konfigurierbare Visualisierungen auf Basis von
Apache ECharts.

**Status: Projektstart.** Dieser Stand enthält die Lizenz und die
Projektdokumentation. Es gibt noch kein installierbares Symcon-Modul,
keine eingebundene ECharts-Bibliothek und keinen veröffentlichten Funktionsumfang.

## Ziel

Symcon-Variablen und Archivdaten sollen ohne eigene JavaScript-Programmierung
als Messinstrumente und Diagramme dargestellt werden können. Das Projekt entsteht
unabhängig von JSLive; bestehende JSLive-Installationen werden nicht verändert.

Als erster Umsetzungsschritt sind ein radiales Messinstrument und ein
Liniendiagramm mit mehreren Datenreihen vorgesehen. Weitere Diagrammtypen sind
spätere Ausbaustufen.

## Entwicklungsgrundsätze

- Entwicklung und Erprobung erfolgen auf `dev`. Geprüfte Stände gelangen per
  Pull Request von `dev` nach `main`; der Entwicklungsbranch bleibt erhalten.
- Symcon-Datenanbindung, ECharts-Konfiguration und Darstellung werden getrennt.
  ECharts soll ohne projektspezifische Änderungen am Bibliothekskern verwendet werden.
- Bibliotheksversionen werden festgeschrieben, mit dem Modul ausgeliefert und
  vor Aktualisierungen getestet. Im Betrieb wird nicht automatisch `latest` geladen.
- Vorhandene zentrale Helper werden wiederverwendet. Allgemeine Erweiterungen
  gehören in `Symcon_ModuleHelper`, nicht in abweichende lokale Helper-Kopien.

## Lizenz

Die eigenen Beiträge zu SymconECharts stehen unter der
**PolyForm Noncommercial License 1.0.0**.

SPDX-Identifier: `PolyForm-Noncommercial-1.0.0`

Required Notice: Copyright 2026 Burkhard Kneiseler. SymconECharts.

Der vollständige Lizenztext steht in [LICENSE](LICENSE). Er ist maßgeblich für
die erlaubten Nutzungen, Änderungen und Weitergaben. Für Nutzungen außerhalb der
dort eingeräumten Rechte ist eine gesonderte Genehmigung des Rechteinhabers erforderlich.

Fremdkomponenten behalten ihre jeweiligen Lizenzen. Insbesondere wird Apache
ECharts nicht unter PolyForm neu lizenziert. Details zur vorgesehenen Trennung
stehen in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).

## Projekt

Ursprüngliche Entwicklung: Burkhard Kneiseler  
Repository: [Burki24/SymconECharts](https://github.com/Burki24/SymconECharts)

SymconECharts ist kein offizielles Projekt der Apache Software Foundation.
