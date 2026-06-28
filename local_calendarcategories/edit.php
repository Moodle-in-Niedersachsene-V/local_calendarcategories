<?php
// This file is part of Moodle - https://moodle.org/
//
// @package    local_calendarcategories
// @copyright  2026 Moodle in Niedersachsen e. V.
// @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
$context = context_system::instance();
require_capability('local/calendarcategories:manage', $context);

$id     = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'edit', PARAM_ALPHAEXT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/calendarcategories/edit.php', ['id' => $id]));
$PAGE->set_title(get_string('pluginname', 'local_calendarcategories'));
$PAGE->set_pagelayout('admin');

// ---------------------------------------------------------------------------
// Inline form definition.
// ---------------------------------------------------------------------------
class local_calendarcategories_edit_form extends moodleform {
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('categoryname', 'local_calendarcategories'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description',
            get_string('categorydescription', 'local_calendarcategories'),
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('description', PARAM_CLEANHTML);

        $mform->addElement('text', 'color',
            get_string('color', 'local_calendarcategories'), ['size' => 7, 'maxlength' => 7]);
        $mform->setType('color', PARAM_RAW);  // validated server-side in manager.
        $mform->setDefault('color', '#3a87ad');
        $mform->addHelpButton('color', 'color', 'local_calendarcategories');

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_calendarcategories'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'] ?? '')) {
            $errors['color'] = get_string('invalidcolor', 'local_calendarcategories');
        }
        return $errors;
    }
}

// ---------------------------------------------------------------------------
// Process form.
// ---------------------------------------------------------------------------
global $DB;

$category = $id ? $DB->get_record('local_calcategories', ['id' => $id], '*', MUST_EXIST) : null;

$form = new local_calendarcategories_edit_form();

if ($category) {
    $form->set_data($category);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/calendarcategories/manage.php'));
} elseif ($data = $form->get_data()) {
    if ($data->id) {
        \local_calendarcategories\category_manager::update($data->id, (array)$data);
        $msg = get_string('categoryupdated', 'local_calendarcategories');
    } else {
        \local_calendarcategories\category_manager::create(
            $data->name,
            $data->color,
            context_system::instance()->id,
            $data->description ?? '',
            (int)($data->sortorder ?? 0)
        );
        $msg = get_string('categorycreated', 'local_calendarcategories');
    }
    redirect(
        new moodle_url('/local/calendarcategories/manage.php'),
        $msg,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// ---------------------------------------------------------------------------
// Output.
// ---------------------------------------------------------------------------
echo $OUTPUT->header();
$heading = $id
    ? get_string('editcategory', 'local_calendarcategories')
    : get_string('addcategory',  'local_calendarcategories');
echo $OUTPUT->heading($heading);
$form->display();
echo $OUTPUT->footer();
