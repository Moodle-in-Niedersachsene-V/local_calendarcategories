<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$string['pluginname']    = 'Kalendergruppen';
$string['pluginname_help'] = 'Ermöglicht die Erstellung eigener Kalendergruppen wie Fachgruppen oder Schulbereiche.';

// Capabilities.
$string['calendarcategories:manage']         = 'Kalendergruppen verwalten';
$string['calendarcategories:managecategory'] = 'Kalendergruppen im Kursbereich verwalten';
$string['calendarcategories:addevent']       = 'Termine einer Kategorie zuordnen';
$string['calendarcategories:view']           = 'Kategorien-Termine anzeigen';

// UI strings.
$string['managecategories']     = 'Kalendergruppen verwalten';
$string['addcategory']          = 'Kalendergruppe hinzufügen';
$string['editcategory']         = 'Kalendergruppe bearbeiten';
$string['categoryname']         = 'Name der Kalendergruppe';
$string['categorydescription']  = 'Beschreibung';
$string['color']                = 'Farbe (Hex)';
$string['color_help']           = 'Hex-Farbcode, z. B. <code>#3a87ad</code>';
$string['categorydeleted']      = 'Kalendergruppe wurde gelöscht.';
$string['categorycreated']      = 'Kalendergruppe wurde erstellt.';
$string['categoryupdated']      = 'Kalendergruppe wurde aktualisiert.';
$string['nocategories']         = 'Es wurden noch keine Kalendergruppen angelegt.';
$string['confirmdelete']        = 'Möchten Sie diese Kalendergruppe wirklich löschen? Alle Mitgliedschaften und Terminverknüpfungen werden ebenfalls entfernt.';

// Errors.
$string['invalidcolor']  = 'Ungültiger Farbwert. Bitte im Format #RRGGBB angeben, z. B. #3a87ad.';
$string['invaliduser']   = 'Ungültige oder gelöschte Benutzer-ID.';
$string['invalidevent']  = 'Der angegebene Kalendertermin existiert nicht.';

// Views.
$string['viewlist']  = 'Liste';
$string['viewmonth'] = 'Monat';
$string['viewweek']  = 'Woche';
$string['calendarview']    = 'Kalenderansicht';
$string['mycategories']    = 'Meine Kalendergruppen';
$string['categoryhint']    = 'Termine werden nur für Kalendergruppen angezeigt, denen Sie angehören.';
$string['showcategories']  = 'Kategorien anzeigen';
$string['moreevents']      = 'weitere';
$string['noupcoming']      = 'Keine bevorstehenden Termine in Ihren Kategorien.';
$string['previousmonth']   = 'Vorheriger Monat';
$string['nextmonth']       = 'Nächster Monat';

// Events.
$string['addevent']          = 'Termin erstellen';
$string['editevent']         = 'Termin bearbeiten';
$string['eventtitle']        = 'Titel';
$string['eventstarttime']    = 'Datum & Uhrzeit';
$string['eventduration']     = 'Dauer';
$string['eventlocation']     = 'Ort (optional)';
$string['eventdescription']  = 'Beschreibung (optional)';
$string['eventcreated']      = 'Termin wurde erstellt.';
$string['eventupdated']      = 'Termin wurde aktualisiert.';
$string['eventdeleted']      = 'Termin wurde gelöscht.';

// Duration options.
$string['durnone']  = 'Kein Ende';
$string['dur30min'] = '30 Minuten';
$string['dur1h']    = '1 Stunde';
$string['dur90min'] = '1,5 Stunden';
$string['dur2h']    = '2 Stunden';
$string['dur1day']  = '1 Tag';

// Errors.
$string['erroremptytitle']  = 'Der Titel darf nicht leer sein.';
$string['errorinvaliddate'] = 'Ungültiges Datum oder ungültige Uhrzeit.';


$string['today'] = 'Heute';

$string['sortorder'] = 'Reihenfolge';


// Monatsnamen.
$string['month_jan'] = 'Januar';
$string['month_feb'] = 'Februar';
$string['month_mar'] = 'März';
$string['month_apr'] = 'April';
$string['month_may'] = 'Mai';
$string['month_jun'] = 'Juni';
$string['month_jul'] = 'Juli';
$string['month_aug'] = 'August';
$string['month_sep'] = 'September';
$string['month_oct'] = 'Oktober';
$string['month_nov'] = 'November';
$string['month_dec'] = 'Dezember';

// Wochentage (Kurzform).
$string['day_mon'] = 'Mo';
$string['day_tue'] = 'Di';
$string['day_wed'] = 'Mi';
$string['day_thu'] = 'Do';
$string['day_fri'] = 'Fr';
$string['day_sat'] = 'Sa';
$string['day_sun'] = 'So';

// Privacy.
$string['privacy:metadata:local_calcategory_members']             = 'Speichert die Zugehörigkeit eines Nutzers zu einer Kalender-Kategorie.';
$string['privacy:metadata:local_calcategory_members:userid']      = 'Die ID des zugeordneten Nutzers.';
$string['privacy:metadata:local_calcategory_members:categoryid']  = 'Die ID der Kalender-Kategorie.';
$string['privacy:metadata:local_calcategory_members:timecreated'] = 'Zeitstempel der Zuordnung.';
$string['privacy:metadata:local_calcategories']                   = 'Speichert, welcher Nutzer eine Kategorie zuletzt geändert hat.';
$string['privacy:metadata:local_calcategories:usermodified']      = 'ID des Nutzers, der die Kategorie zuletzt bearbeitet hat.';
