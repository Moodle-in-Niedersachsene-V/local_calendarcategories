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
// Inline form definition.
/**
 * Form for creating or editing a calendar group.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_calendarcategories_edit_form extends moodleform {
    /**
     * Define the form fields.
     */
    public function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'name', get_string('categoryname', 'local_calendarcategories'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement(
            'textarea',
            'description',
            get_string('categorydescription', 'local_calendarcategories'),
            ['rows' => 3, 'cols' => 50]
        );
        $mform->setType('description', PARAM_CLEANHTML);

        $mform->addElement(
            'text',
            'color',
            get_string('color', 'local_calendarcategories'),
            ['size' => 7, 'maxlength' => 7]
        );
        $mform->setType('color', PARAM_RAW);  // Validated server-side in manager.
        $mform->setDefault('color', '#3a87ad');
        $mform->addHelpButton('color', 'color', 'local_calendarcategories');

        $mform->addElement('text', 'sortorder', get_string('sortorder', 'local_calendarcategories'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data  Submitted form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'] ?? '')) {
            $errors['color'] = get_string('invalidcolor', 'local_calendarcategories');
        }
        return $errors;
    }
}
// Process form.
global $DB;

$category = $id ? $DB->get_record('local_calendarcategories_cats', ['id' => $id], '*', MUST_EXIST) : null;

$form = new local_calendarcategories_edit_form();

if ($category) {
    $form->set_data($category);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/calendarcategories/manage.php'));
} else if ($data = $form->get_data()) {
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
// Output.
echo $OUTPUT->header();
$heading = $id
    ? get_string('editcategory', 'local_calendarcategories')
    : get_string('addcategory', 'local_calendarcategories');
echo $OUTPUT->heading($heading);
$form->display();
echo $OUTPUT->footer();
