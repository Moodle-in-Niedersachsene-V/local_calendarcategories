<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Add or edit a calendar group event.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
$context = context_system::instance();

if (
    !is_siteadmin()
    && !\local_calendarcategories\util::has_capability_anywhere('local/calendarcategories:addevent')
) {
    throw new required_capability_exception($context, 'local/calendarcategories:addevent', 'nopermissions', '');
}

$eventid  = optional_param('eventid', 0, PARAM_INT);
$prefdate = optional_param('date', '', PARAM_TEXT);  // YYYY-MM-DD.

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/calendarcategories/addevent.php', ['eventid' => $eventid]));
$PAGE->set_pagelayout('standard');
$PAGE->requires->css('/local/calendarcategories/styles/calendarcategories.css');

// Load categories the user can post into.
$mycats = \local_calendarcategories\category_manager::get_visible_categories();
if (empty($mycats)) {
    redirect(
        new moodle_url('/local/calendarcategories/view.php'),
        get_string('nocategories', 'local_calendarcategories'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$catoptions = [];
foreach ($mycats as $cat) {
    $catoptions[$cat->id] = format_string($cat->name);
}

// Existing event (edit mode).
$existing = null;
if ($eventid) {
    global $DB;
    $link = $DB->get_record('local_calendarcategories_events', ['eventid' => $eventid]);
    if ($link) {
        $existing = $DB->get_record('event', ['id' => $eventid], '*', MUST_EXIST);
    }
}

$PAGE->set_title($existing
    ? get_string('editevent', 'local_calendarcategories')
    : get_string('addevent', 'local_calendarcategories'));
$PAGE->set_heading($PAGE->title);

// Moodle form.
/**
 * Form for creating or editing a calendar group event.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_calendarcategories_event_form extends moodleform {
    /**
     * Define the form fields.
     */
    public function definition(): void {
        $mform   = $this->_form;
        $catopts = $this->_customdata['catoptions'];
        $isedit  = !empty($this->_customdata['eventid']);

        // Title.
        $mform->addElement('text', 'name', get_string('eventtitle', 'local_calendarcategories'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Category.
        $mform->addElement(
            'select',
            'categoryid',
            get_string('categoryname', 'local_calendarcategories'),
            $catopts
        );
        $mform->addRule('categoryid', null, 'required', null, 'client');

        // Date.
        $mform->addElement(
            'date_time_selector',
            'timestart',
            get_string('eventstarttime', 'local_calendarcategories'),
            ['optional' => false]
        );
        $mform->addRule('timestart', null, 'required', null, 'client');

        // Duration.
        $duropts = [
            0        => get_string('durnone', 'local_calendarcategories'),
            1800     => get_string('dur30min', 'local_calendarcategories'),
            3600     => get_string('dur1h', 'local_calendarcategories'),
            5400     => get_string('dur90min', 'local_calendarcategories'),
            7200     => get_string('dur2h', 'local_calendarcategories'),
            86400    => get_string('dur1day', 'local_calendarcategories'),
        ];
        $mform->addElement(
            'select',
            'timeduration',
            get_string('eventduration', 'local_calendarcategories'),
            $duropts
        );

        // Location.
        $mform->addElement('text', 'location', get_string('eventlocation', 'local_calendarcategories'));
        $mform->setType('location', PARAM_TEXT);

        // Description.
        $mform->addElement(
            'textarea',
            'description',
            get_string('eventdescription', 'local_calendarcategories'),
            ['rows' => 4, 'cols' => 40, 'class' => 'w-100']
        );
        $mform->setType('description', PARAM_CLEANHTML);

        // Hidden fields.
        $mform->addElement('hidden', 'eventid');
        $mform->setType('eventid', PARAM_INT);

        $this->add_action_buttons(true, $isedit
            ? get_string('savechanges')
            : get_string('addevent', 'local_calendarcategories'));
    }
}

$formdata = [
    'catoptions' => $catoptions,
    'eventid'    => $eventid,
];

$form = new local_calendarcategories_event_form(null, $formdata);

// Pre-fill.
if ($existing) {
    global $DB;
    $link = $DB->get_record('local_calendarcategories_events', ['eventid' => $eventid]);
    $form->set_data([
        'eventid'      => $eventid,
        'name'         => $existing->name,
        'categoryid'   => $link->categoryid,
        'timestart'    => $existing->timestart,
        'timeduration' => $existing->timeduration,
        'location'     => $existing->location ?? '',
        'description'  => $existing->description ?? '',
    ]);
} else if ($prefdate) {
    $form->set_data(['timestart' => strtotime($prefdate . ' 09:00:00')]);
}

// Handle submission.
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/calendarcategories/view.php'));
} else if ($data = $form->get_data()) {
    if ($data->eventid) {
        \local_calendarcategories\event_manager::update_event($data->eventid, (array)$data);
        $msg = get_string('eventupdated', 'local_calendarcategories');
    } else {
        \local_calendarcategories\event_manager::create_event(
            (int)$data->categoryid,
            $data->name,
            (int)$data->timestart,
            (int)($data->timeduration ?? 0),
            $data->description ?? '',
            $data->location ?? ''
        );
        $msg = get_string('eventcreated', 'local_calendarcategories');
    }
    redirect(
        new moodle_url('/local/calendarcategories/view.php'),
        $msg,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Output.
echo $OUTPUT->header();

// Category color preview strip (JS-driven).
echo html_writer::tag('div', '', [
    'id'    => 'lcc-cat-preview',
    'style' => 'height:4px;border-radius:2px;margin-bottom:1rem;background:#dee2e6;transition:background .2s',
]);

$form->display();

// JS: update color preview when category changes.
$catcolors = [];
foreach ($mycats as $cat) {
    $catcolors[(int)$cat->id] = $cat->color;
}
$catcolorsjson = json_encode($catcolors);
echo html_writer::tag('script', "
(function(){
  var colors = {$catcolorsjson};
  var sel = document.querySelector('[name=categoryid]');
  var bar = document.getElementById('lcc-cat-preview');
  if (!sel || !bar) return;
  function update(){ bar.style.background = colors[sel.value] || '#dee2e6'; }
  sel.addEventListener('change', update);
  update();
})();
");

echo $OUTPUT->footer();
