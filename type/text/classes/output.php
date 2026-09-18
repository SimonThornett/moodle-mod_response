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

namespace mod_response\type\text;
use mod_response\responsetype\abstractoutput;
use stdClass;
use renderable;
use renderer_base;
use templatable;
use mod_response\helper;
use moodle_url;
use pix_icon;
use context_module;

/**
 * Creates a renderer for creation of an activity (i.e. user completion of response).
 *
 * @package   responsetype_text
 * @copyright 2017 Peter Spicer <peter.spicer@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class output extends abstractoutput implements renderable, templatable {
    /** @var object Contains all the data for a response so a user can complete it. */
    protected $data = null;

    /**
     * Provides the data for the template.
     *
     * Essentially hands everything to the subplugin because the subplugin
     * knows what it needs for its own templates.
     *
     * @param renderer_base $output The output renderer object
     * @return object $data An object containing all the template data
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $OUTPUT;

        $data = new stdClass();

        // Whatever we're exporting, we want the title and question. (And support multilang by default).
        $data->heading = format_string($this->data->name);
        $data->question = format_string($this->data->question);
        $data->fullpage = !empty($this->data->fullpage);
        $this->add_course_module_data($data);

        $data->icon = $OUTPUT->render(new pix_icon('icon', '', 'responsetype_text'));
        if (empty($this->data->viewing_id)) {
            $this->data->viewing_id = $USER->id;
            $this->data->viewing_own = true;
        }
        $data->viewing_id = $this->data->viewing_id;
        $data->viewing_own = $this->data->viewing_own;

        $data->can_see_all = !empty($this->data->can_see_all);
        $data->viewall_url = !empty($this->data->viewall_url) ? $this->data->viewall_url : '';

        // And we need to inform the renderer which response type and template to load.
        $data->responsetype = $this->data->responsetype;
        $data->response_id = $this->data->activity->response;

        if (!empty($this->data->form)) {
            // Showing the form, so render the form and select that template.
            $data->form = $this->data->form->render();
            $data->template = 'responselayout';
        } else {
            // Showing what the user selected.
            $data->template = 'showsubmission';
            $data->fullpage = !empty($this->data->fullpage);
            $data->summary_url = new moodle_url('/mod/response/index.php', ['id' => $this->data->course]);

            $this->add_profile_data($data);

            $responsetext = $this->data->user_responses[$this->data->viewing_id]->response_text;
            $responsetext = file_rewrite_pluginfile_urls(
                $responsetext,
                'pluginfile.php',
                context_module::instance($this->data->cm->id)->id,
                'responsetype_text',
                'response_text',
                $this->data->user_responses[$this->data->viewing_id]->response_user_id
            );
            $data->user_response = format_text($responsetext);

            $this->add_response_metadata($data);
        }
        return $data;
    }
}
