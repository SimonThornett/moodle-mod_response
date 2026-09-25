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

namespace mod_response\type\poll;

use context_module;
use mod_response\helper;
use mod_response\responsetype\abstractviewall;
use stdClass;

/**
 * Creates a renderer for showing all poll responses.
 *
 * @package   responsetype_poll
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewall extends abstractviewall {
    /**
     * Returns all poll responses.
     *
     * @return array
     */
    protected function get_responses(): array {
        return !empty($this->data->all_responses) ? $this->data->all_responses : [];
    }

    /**
     * Formats a poll response.
     *
     * @param stdClass $response Response to prepare.
     * @return bool
     */
    protected function prepare_response(stdClass $response): bool {
        if (!isset($this->data->activity->poll_choices[$response->choice])) {
            return false;
        }

        $response->choicetext = $this->data->activity->poll_choices[$response->choice]->choice;
        $response->reflection_text = file_rewrite_pluginfile_urls(
            $response->reflection_text,
            'pluginfile.php',
            context_module::instance($this->data->cm->id)->id,
            'responsetype_poll',
            'response_poll',
            $response->id,
        );
        $response->reflection_text = format_text($response->reflection_text);
        return true;
    }

    /**
     * Returns the response component.
     *
     * @return string
     */
    protected function get_component(): string {
        return 'responsetype_poll';
    }

    /**
     * Adds poll aggregate data.
     *
     * @param stdClass $data Template data.
     * @return void
     */
    protected function finalize_data(stdClass $data): void {
        $data->aggregate = [];
        foreach ($this->data->activity->poll_choices as $choice) {
            $data->aggregate[$choice->responsenum] = [
                'choice' => $choice->choice,
                'count' => 0,
            ];
        }
        foreach ($data->all_responses as $response) {
            $data->aggregate[$response->choice]['count']++;
        }
        $data->aggregate = json_encode($data->aggregate);
        $data->colours = $this->stringify_chart_colorset();
    }

    /**
     * Returns the context link.
     *
     * @return string
     */
    protected function get_context_link(): string {
        return helper::get_context_url(
            $this->data->cm,
            $this->data->course,
            $this->data->responsedisplay,
        )->out(false);
    }
}
