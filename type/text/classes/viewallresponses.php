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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_response\type\text;

use context_module;
use mod_response\helper;
use mod_response\responsetype\abstractviewallresponses;
use stdClass;

/**
 * Creates a renderer for showing text responses to an activity.
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewallresponses extends abstractviewallresponses {
    /**
     * Formats a text response.
     *
     * @param stdClass $response Response to prepare.
     * @return bool
     */
    protected function prepare_response(stdClass $response): bool {
        $response->response_text = file_rewrite_pluginfile_urls(
            $response->response_text,
            'pluginfile.php',
            context_module::instance($this->data->coursemodule)->id,
            'responsetype_text',
            'response_text',
            $response->response_user_id,
        );
        $response->response_text = format_text($response->response_text);
        return true;
    }

    /**
     * Returns the response component.
     *
     * @return string
     */
    protected function get_component(): string {
        return 'responsetype_text';
    }

    /**
     * Returns the context link.
     *
     * @return string
     */
    protected function get_context_link(): string {
        $cm = get_coursemodule_from_id('response', $this->data->coursemodule);
        $course = get_course($cm->course);
        return helper::get_context_url(
            $cm,
            $course,
            $this->data->responsedisplay,
        )->out(false);
    }
}
