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

namespace mod_response\responsetype;
use stdClass;
use renderable;
use renderer_base;
use templatable;

/**
 * Defines the response information API that subplugins are expected to follow.
 *
 * Any subplugin that defines a type of response for the response activity
 * will need to load data for displaying it to users, as well as loading users'
 * responses. All such subplugins should define a configuration class that extends
 *  this one.
 *
 * @package   mod_response
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstractoutput implements renderable, templatable {
    /** @var object Stores state about the current activity. */
    protected $data;

    /**
     * Creates an instance of the renderer, and accepts the overall
     * object containing the response state.
     *
     * @param object $data The general data of the response itself
     * @param object $subplugin Instance of the subplugin object to render
     */
    public function __construct($data, $subplugin) {
        $this->data = $data;
        $this->data->subplugin = $subplugin;
    }

    /**
     * Provides the data for the template.
     *
     * This object will have already received all the data for the
     * current activity, this identifies which is the relevant template
     * for the current state of the activity, and renders it.
     *
     * @param renderer_base $output The output renderer object
     * @return object $data An object containing all the template data
     */
    abstract public function export_for_template(renderer_base $output);

    /**
     * Provides the default chart colours as Moodle 3.2 uses (in
     * case we're on 3.1), ready made into an JSON-encoded string
     * for exporting to templates. Will use $CFG->chart_colorset
     * if defined (as per 3.2)
     *
     * @return string JSON-encoded string of an array of colours.
     */
    public function stringify_chart_colorset() {
        global $CFG;
        if (!empty($CFG->chart_colorset) && is_array($CFG->chart_colorset)) {
            return json_encode($CFG->chart_colorset);
        } else {
            return '["#f3c300","#875692","#f38400","#a1caf1","#be0032","#c2b280","#7f180d","#008856","#e68fac","#0067a5"]';
        }
    }

    /**
     * Adds common user profile fields to rendered response data.
     *
     * @param stdClass $data Template data.
     * @return void
     */
    protected function add_profile_data(stdClass $data): void {
        global $OUTPUT, $USER;

        if (!empty($this->data->response->profile_picture)) {
            $data->profile_picture = $this->data->response->profile_picture;
            $data->profile_name = $this->data->response->first_name;
        } else {
            $data->profile_picture = $OUTPUT->user_picture($USER, ['size' => '50', 'class' => 'profilepicture']);
            $data->profile_name = '';
        }
    }

    /**
     * Adds common course-module display data.
     *
     * @param stdClass $data Template data.
     * @return void
     */
    protected function add_course_module_data(stdClass $data): void {
        $data->contextid = !empty($this->data->contextid) ? $this->data->contextid : false;
        $data->viewownpagedescription = !empty($this->data->viewownpagedescription);

        if (isset($this->data->cm)) {
            $data->cm = $this->data->cm;
            $data->cm->intro = $this->data->intro;
            $data->cm->introformat = $this->data->introformat;
            if ($data->fullpage && $data->viewownpagedescription) {
                $data->description = format_module_intro('response', $data->cm, $data->cm->id, false);
            } else {
                $data->description = '';
            }
        } else {
            $this->data->cm = get_coursemodule_from_instance('response', $this->data->id);
        }
    }

    /**
     * Adds common response metadata to rendered response data.
     *
     * @param stdClass $data Template data.
     * @return void
     */
    protected function add_response_metadata(stdClass $data): void {
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        $timemodified = $this->data->user_responses[$this->data->viewing_id]->timemodified;
        $data->user_response_time = userdate($timemodified, $dateformat, 99, false, false);
        $data->can_delete = !empty($this->data->can_delete);
        $data->delete_url = !empty($this->data->delete_url) ? $this->data->delete_url : '';
        $data->can_edit = !empty($this->data->can_edit);
        $data->edit_url = !empty($this->data->edit_url) ? $this->data->edit_url : '';
        $data->postcompletion = '';
        if (!empty($this->data->displaycompletionafter) && !empty($this->data->postcompletion)) {
            $data->postcompletion = $this->data->postcompletion->render();
        }
    }
}
