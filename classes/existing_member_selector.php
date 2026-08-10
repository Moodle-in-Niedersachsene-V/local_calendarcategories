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

namespace local_calendarcategories;

/**
 * User selector listing the current members of a calendar group.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class existing_member_selector extends \user_selector_base {
    /** @var int The calendar category to list members of. */
    protected $categoryid;

    /**
     * Constructor.
     *
     * @param string $name    Form element name.
     * @param array  $options Must contain 'categoryid'.
     */
    public function __construct($name, $options) {
        $this->categoryid = (int)$options['categoryid'];
        parent::__construct($name, $options);
    }

    /**
     * Find current members matching the search term.
     *
     * @param string $search Search term entered by the admin.
     * @return array Array keyed by group label, containing user records.
     */
    public function find_users($search): array {
        global $DB;

        [$wherecondition, $params] = $this->search_sql($search, 'u');
        $params['categoryid'] = $this->categoryid;

        $fields = 'SELECT ' . $this->required_fields_sql('u');

        $sql = ' FROM {user} u
                 JOIN {local_calendarcategories_members} m ON m.userid = u.id
                WHERE m.categoryid = :categoryid
                  AND ' . $wherecondition;

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';

        $members = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($members)) {
            return [];
        }

        $groupname = $search
            ? get_string('currentmembersmatching', 'local_calendarcategories', $search)
            : get_string('currentmembers', 'local_calendarcategories');

        return [$groupname => $members];
    }

    /**
     * Preserve selector state across postbacks.
     *
     * @return array Options array including the calendar category id.
     */
    protected function get_options(): array {
        $options = parent::get_options();
        $options['categoryid'] = $this->categoryid;
        $options['file'] = 'local/calendarcategories/classes/existing_member_selector.php';
        return $options;
    }
}
