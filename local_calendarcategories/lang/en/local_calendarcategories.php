<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

$string['pluginname']    = 'Calendar Groups';
$string['pluginname_help'] = 'Allows creation of custom calendar groups such as subject groups or school areas.';

// Capabilities.
$string['calendarcategories:manage']         = 'Manage calendar groups';
$string['calendarcategories:managecategory'] = 'Manage calendar groups in course category context';
$string['calendarcategories:addevent']       = 'Link events to a calendar category';
$string['calendarcategories:view']           = 'View category events';

// UI.
$string['managecategories']    = 'Manage Calendar Groups';
$string['addcategory']         = 'Add calendar group';
$string['editcategory']        = 'Edit calendar group';
$string['categoryname']        = 'Group name';
$string['categorydescription'] = 'Description';
$string['color']               = 'Color (hex)';
$string['color_help']          = 'Hex color code, e.g. <code>#3a87ad</code>';
$string['categorydeleted']     = 'Calendar group deleted.';
$string['categorycreated']     = 'Calendar group created.';
$string['categoryupdated']     = 'Calendar group updated.';
$string['nocategories']        = 'No calendar groups have been created yet.';
$string['confirmdelete']       = 'Really delete this calendar group? All memberships and event links will also be removed.';

// Errors.
$string['invalidcolor'] = 'Invalid color value. Please use hex format #RRGGBB, e.g. #3a87ad.';
$string['invaliduser']  = 'Invalid or deleted user ID.';
$string['invalidevent'] = 'The specified calendar event does not exist.';

// Views.
$string['viewlist']  = 'List';
$string['viewmonth'] = 'Month';
$string['viewweek']  = 'Week';
$string['calendarview']    = 'Calendar view';
$string['mycategories']    = 'My calendar groups';
$string['categoryhint']    = 'Events are only shown for calendar groups you belong to.';
$string['showcategories']  = 'Show categories';
$string['moreevents']      = 'more';
$string['noupcoming']      = 'No upcoming events in your categories.';
$string['previousmonth']   = 'Previous month';
$string['nextmonth']       = 'Next month';

// Events.
$string['addevent']          = 'Create event';
$string['editevent']         = 'Edit event';
$string['eventtitle']        = 'Title';
$string['eventstarttime']    = 'Date & time';
$string['eventduration']     = 'Duration';
$string['eventlocation']     = 'Location (optional)';
$string['eventdescription']  = 'Description (optional)';
$string['eventcreated']      = 'Event created.';
$string['eventupdated']      = 'Event updated.';
$string['eventdeleted']      = 'Event deleted.';

// Duration options.
$string['durnone']  = 'No end time';
$string['dur30min'] = '30 minutes';
$string['dur1h']    = '1 hour';
$string['dur90min'] = '1.5 hours';
$string['dur2h']    = '2 hours';
$string['dur1day']  = '1 day';

// Errors.
$string['erroremptytitle']  = 'The title must not be empty.';
$string['errorinvaliddate'] = 'Invalid date or time.';


$string['today'] = 'Today';

$string['sortorder'] = 'Sort order';


// Monatsnamen.
$string['month_jan'] = 'January';
$string['month_feb'] = 'February';
$string['month_mar'] = 'March';
$string['month_apr'] = 'April';
$string['month_may'] = 'May';
$string['month_jun'] = 'June';
$string['month_jul'] = 'July';
$string['month_aug'] = 'August';
$string['month_sep'] = 'September';
$string['month_oct'] = 'October';
$string['month_nov'] = 'November';
$string['month_dec'] = 'December';

// Wochentage (Kurzform).
$string['day_mon'] = 'Mon';
$string['day_tue'] = 'Tue';
$string['day_wed'] = 'Wed';
$string['day_thu'] = 'Thu';
$string['day_fri'] = 'Fri';
$string['day_sat'] = 'Sat';
$string['day_sun'] = 'Sun';

// Privacy.
$string['privacy:metadata:local_calcategory_members']             = 'Stores membership of a user in a calendar category.';
$string['privacy:metadata:local_calcategory_members:userid']      = 'ID of the assigned user.';
$string['privacy:metadata:local_calcategory_members:categoryid']  = 'ID of the calendar category.';
$string['privacy:metadata:local_calcategory_members:timecreated'] = 'Timestamp when the membership was created.';
$string['privacy:metadata:local_calcategories']                   = 'Stores who last modified a category.';
$string['privacy:metadata:local_calcategories:usermodified']      = 'ID of the user who last modified the category.';
