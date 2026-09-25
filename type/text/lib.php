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

use mod_response\helper;

/**
 * Serves the response-text files.
 *
 * @package  responsetype_text
 * @category files
 * @param stdClass $course Standard course object
 * @param stdClass $cm Standard course module object
 * @param stdClass $context Standard context object
 * @param string $filearea The file area to serve from
 * @param array $args Any additional arguments the filesystem might have
 * @param bool $forcedownload True to force download
 * @param array $options Additional options affecting file serving
 * @return bool False if file not found, does not return if found
 * @author Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2026
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function responsetype_text_pluginfile(
    \stdClass $course,
    \stdClass $cm,
    \stdClass $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    return helper::serve_pluginfile(
        $cm,
        $context,
        $filearea,
        $args,
        $forcedownload,
        $options,
        'responsetype_text_user',
        'responsetype_text_user',
    );
}
