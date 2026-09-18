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

namespace mod_response\type\poll;
use mod_response\responsetype\abstractoutput;
use stdClass;
use renderable;
use renderer_base;
use templatable;
use mod_response\helper;
use moodle_url;

/**
 * Creates a renderer for creation of an activity (i.e. user completion of response).
 *
 * @package   responsetype_poll
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
        $data->response_id = $this->data->activity->response;
        $this->add_course_module_data($data);

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

            $userchoice = $this->data->user_responses[$this->data->viewing_id]->choice;
            $data->user_choice = format_string($this->data->activity->poll_choices[$userchoice]->choice);

            $rewritelinks = file_rewrite_pluginfile_urls(
                $this->data->user_responses[$this->data->viewing_id]->reflection_text,
                'pluginfile.php',
                \context_module::instance($this->data->cm->id)->id,
                'responsetype_poll',
                'response_poll',
                $this->data->user_responses[$this->data->viewing_id]->id
            );
            $data->user_response = format_text($rewritelinks);

            $this->add_response_metadata($data);

            // There may be some stuff to display aggregate-wise.
            $aggregate = false;
            if (!empty($this->data->aggregate)) {
                $aggregate = new stdClass();
                $aggregate->all = new stdClass();
                $aggregate->all->title = !empty($this->data->title) ? $this->data->title : '';
                $aggregate->all->labels = [];
                $aggregate->all->data = [];
                foreach ($this->data->activity->poll_choices as $choicenum => $choice) {
                    $aggregate->all->labels[] = $choice->choice;
                    $amount = 0;
                    if (!empty($this->data->aggregate->all[$choicenum])) {
                        $amount = $this->data->aggregate->all[$choicenum];
                    }
                    $aggregate->all->data[] = $amount;
                }
            }
            $data->aggregate = json_encode($aggregate);
            $data->colours = $this->stringify_chart_colorset();
        }
        return $data;
    }
}
