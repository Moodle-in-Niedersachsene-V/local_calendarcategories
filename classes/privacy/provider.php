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

namespace local_calendarcategories\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider – GDPR compliance for local_calendarcategories.
 *
 * Personal data stored:
 *  - local_calcategory_members: userid → which categories a user belongs to.
 *  - local_calcategories.usermodified: tracks who last changed a category.
 *
 * @package    local_calendarcategories
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'local_calcategory_members',
            [
                'userid'      => 'privacy:metadata:local_calcategory_members:userid',
                'categoryid'  => 'privacy:metadata:local_calcategory_members:categoryid',
                'timecreated' => 'privacy:metadata:local_calcategory_members:timecreated',
            ],
            'privacy:metadata:local_calcategory_members'
        );

        $collection->add_database_table(
            'local_calcategories',
            [
                'usermodified' => 'privacy:metadata:local_calcategories:usermodified',
            ],
            'privacy:metadata:local_calcategories'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();

        // Contexts of categories the user is a member of.
        $sql = 'SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {local_calcategories} cat ON cat.contextid = c.id
                  JOIN {local_calcategory_members} m ON m.categoryid = cat.id
                 WHERE m.userid = :userid';
        $contextlist->add_from_sql($sql, ['userid' => $userid]);

        // Contexts of categories the user last modified.
        $sql2 = 'SELECT DISTINCT c.id
                   FROM {context} c
                   JOIN {local_calcategories} cat ON cat.contextid = c.id
                  WHERE cat.usermodified = :userid';
        $contextlist->add_from_sql($sql2, ['userid' => $userid]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        global $DB;
        $contextid = $userlist->get_context()->id;

        $sql = 'SELECT m.userid
                  FROM {local_calcategory_members} m
                  JOIN {local_calcategories} cat ON cat.id = m.categoryid
                 WHERE cat.contextid = :contextid';
        $userlist->add_from_sql('userid', $sql, ['contextid' => $contextid]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $sql = 'SELECT cat.name, m.timecreated
                      FROM {local_calcategory_members} m
                      JOIN {local_calcategories} cat ON cat.id = m.categoryid
                     WHERE m.userid = :userid AND cat.contextid = :contextid';
            $rows = $DB->get_records_sql($sql, ['userid' => $userid, 'contextid' => $context->id]);
            if ($rows) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_calendarcategories')],
                    (object)['memberships' => array_values($rows)]
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        $categoryids = $DB->get_fieldset_select(
            'local_calcategories',
            'id',
            'contextid = :contextid',
            ['contextid' => $context->id]
        );
        if ($categoryids) {
            [$in, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_calcategory_members', "categoryid $in", $params);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $categoryids = $DB->get_fieldset_select(
                'local_calcategories',
                'id',
                'contextid = :contextid',
                ['contextid' => $context->id]
            );
            if ($categoryids) {
                [$in, $params] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED);
                $params['userid'] = $userid;
                $DB->delete_records_select(
                    'local_calcategory_members',
                    "categoryid $in AND userid = :userid",
                    $params
                );
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $contextid = $userlist->get_context()->id;
        $userids   = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        $categoryids = $DB->get_fieldset_select(
            'local_calcategories',
            'id',
            'contextid = :contextid',
            ['contextid' => $contextid]
        );
        if ($categoryids) {
            [$incat, $catparams] = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
            [$inusr, $usrparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
            $DB->delete_records_select(
                'local_calcategory_members',
                "categoryid $incat AND userid $inusr",
                array_merge($catparams, $usrparams)
            );
        }
    }
}
