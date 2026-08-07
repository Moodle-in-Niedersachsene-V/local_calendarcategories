# local_calendarcategories

**Moodle in Niedersachsen e. V. | Version 1.0.0 | Build 2026062801**

Lokales Moodle-Plugin zur Erweiterung des Kalenders um eigene Kategorien wie Fachgruppen, Schulbereiche oder Gremien.

## Funktionsumfang

- Anlegen beliebig vieler Kalender-Kategorien (Name, Farbe, Beschreibung, Sichtbarkeit)
- Nutzerverwaltung je Kategorie (Mitglieder)
- Verknüpfung vorhandener Moodle-Kalendertermine mit einer Kategorie
- Automatische Bereinigung bei Terminen-Löschung oder Benutzerdeaktivierung
- Vollständige DSGVO-Compliance (Privacy API)

## Voraussetzungen

- Moodle ≥ 5.0
- PHP ≥ 8.1

## Installation

1. Ordner `local/calendarcategories/` in den Moodle-Pfad kopieren
2. `php admin/cli/upgrade.php` ausführen oder Upgrade im Admin-Interface starten

## Rechte (Capabilities)

| Capability | Standard |
|---|---|
| `local/calendarcategories:manage` | manager |
| `local/calendarcategories:managecategory` | manager (Kursbereich) |
| `local/calendarcategories:addevent` | manager, editingteacher |
| `local/calendarcategories:view` | alle authentifizierten Nutzer |

## Lizenz

GNU General Public License v3.0 oder höher –
<https://www.gnu.org/licenses/gpl-3.0.html>

## Offene Punkte / Roadmap

- Kalender-Widget zur Anzeige von Kategorie-Terminen direkt im Moodle-Kalender
- Kohorten als Mitglieder-Quelle (statt Einzelnutzer)
- REST-API-Endpunkt für externe Schnittstellen
- Ical-Export je Kategorie
