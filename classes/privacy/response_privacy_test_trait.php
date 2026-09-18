<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_response\privacy;

use context_module;
use core_privacy\local\request\approved_contextlist;
use mod_response\privacy\provider as parentprovider;

/**
 * Shared privacy tests for response type subplugins.
 *
 * @package   mod_response
 * @copyright 2026 Simon Thornett
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait response_privacy_test_trait {
    /**
     * Creates the fixture used by the shared tests.
     *
     * @return array
     */
    abstract protected function create_privacy_fixture(): array;

    /**
     * Returns the response type table and expected record count.
     *
     * @return array
     */
    abstract protected function get_response_type_details(): array;

    /**
     * Verifies the contexts returned for a user.
     *
     * @return void
     */
    protected function run_get_contexts_for_userid(): void {
        $this->resetAfterTest();
        [$text1, $text2, , , $u1] = $this->create_privacy_fixture();
        $expected = [
            context_module::instance($text1->cmid)->id,
            context_module::instance($text2->cmid)->id,
        ];
        $contextlist = parentprovider::get_contexts_for_userid($u1->id);
        $this->assertEquals(
            count($expected),
            count(array_intersect($expected, $contextlist->get_contextids())),
        );
    }

    /**
     * Verify that all user data for a single context is removed upon call.
     */
    protected function run_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();
        [$text1, $text2, $text1ctx] = $this->create_privacy_fixture();
        parentprovider::delete_data_for_all_users_in_context($text1ctx);

        $records = $DB->get_records('response');
        $this->assertEquals(2, count($records));
        foreach ($records as $record) {
            $this->assertContains($record->question, ['Question 1?', 'Question 2?']);
        }

        $recordsuser = $DB->get_records('response_user');
        $this->assertEquals(2, count($recordsuser));
        foreach ($recordsuser as $record) {
            $this->assertEquals($record->response, $text2->id);
        }

        [$table, $count] = $this->get_response_type_details();
        $recordsusertext = $DB->get_records($table);
        $this->assertEquals($count, count($recordsusertext));
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->response, $text2->id);
        }
    }

    /**
     * Verify that a single user's data is removed from multiple contexts.
     */
    protected function run_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();
        [$text1, $text2, $text1ctx, $text2ctx, $u1, $u2] = $this->create_privacy_fixture();
        $contextlist = new approved_contextlist($u1, 'mod_response', [$text1ctx->id, $text2ctx->id]);
        parentprovider::delete_data_for_user($contextlist);

        $recordsuser = $DB->get_records('response_user');
        $this->assertEquals(2, count($recordsuser));
        foreach ($recordsuser as $record) {
            $this->assertEquals($record->userid, $u2->id);
        }

        [$table, $count] = $this->get_response_type_details();
        $recordsusertext = $DB->get_records($table);
        $this->assertEquals($count, count($recordsusertext));
        foreach ($recordsusertext as $record) {
            $this->assertEquals($record->userid, $u2->id);
        }
    }
}
