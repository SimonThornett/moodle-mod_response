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

namespace mod_response\responsetype;

use pix_icon;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Shared renderer for displaying responses for an activity.
 *
 * @package   mod_response
 * @copyright 2026 Simon Thornett
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractviewallresponses extends abstractoutput implements renderable, templatable {
    /**
     * Returns the component name used for the icon.
     *
     * @return string
     */
    abstract protected function get_component(): string;

    /**
     * Adds response-type-specific fields and formatting.
     *
     * @param stdClass $response Response to prepare.
     * @return bool Whether to retain the response.
     */
    abstract protected function prepare_response(stdClass $response): bool;

    /**
     * Provides the data for the template.
     *
     * @param renderer_base $output The output renderer object.
     * @return object
     */
    public function export_for_template(renderer_base $output): object {
        global $OUTPUT;

        $data = new stdClass();
        $data->heading = format_string($this->data->name);
        $data->question = format_string($this->data->question);
        $data->user_responses = !empty($this->data->user_responses) ? $this->data->user_responses : [];
        $data->group_selector = !empty($this->data->group_selector) ? $this->data->group_selector : '';
        $data->icon = $OUTPUT->render(new pix_icon('icon', '', $this->get_component()));
        $data->responsetype = $this->data->responsetype;

        foreach ($data->user_responses as $id => $response) {
            if (!$this->prepare_response($response)) {
                unset($data->user_responses[$id]);
            }
        }

        $data->user_responses = array_values($data->user_responses);
        $this->prepare_dates($data->user_responses);
        $this->finalize_data($data);
        $data->context_link = $this->get_context_link();

        return $data;
    }

    /**
     * Adds formatted completion dates to responses.
     *
     * @param array $responses Responses to format.
     * @return void
     */
    protected function prepare_dates(array $responses): void {
        $dateformat = get_string('strftimedatefullshort', 'langconfig');
        $datetimeformat = get_string('strftimedatetimeshort', 'langconfig');
        foreach ($responses as $response) {
            $response->timecompleted_date = userdate($response->timecompleted, $dateformat, 99, false, false);
            $response->timecompleted_datetime = userdate($response->timecompleted, $datetimeformat, 99, false, false);
        }
    }

    /**
     * Builds the context link for the current activity.
     *
     * @return string
     */
    abstract protected function get_context_link(): string;

    /**
     * Adds response-type-specific aggregate data.
     *
     * @param stdClass $data Template data.
     * @return void
     */
    protected function finalize_data(stdClass $data): void {
    }
}
