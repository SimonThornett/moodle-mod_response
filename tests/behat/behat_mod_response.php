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

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Testwork\Tester\Result\TestResult;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Behat steps for mod_response
 *
 * @package   mod_response
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_response extends behat_base {
    /** @var string|null Directory used to store console log dumps for the current run. */
    protected static ?string $consolelogdir = null;

    /**
     * Dump the browser's JavaScript console log alongside the core faildump when a step fails.
     *
     * The core faildump mechanism only captures a screenshot and the page HTML, which is often
     * insufficient to diagnose JavaScript errors (e.g. an AMD module load failure). This hook
     * writes any console log entries reported by the WebDriver session to a text file in
     * $CFG->behat_faildump_path so they are captured in CI artifacts too.
     *
     * @param AfterStepScope $scope scope passed by event fired after step.
     * @AfterStep
     */
    public function after_step_dump_console_log(AfterStepScope $scope): void {
        global $CFG;

        if (empty($CFG->behat_faildump_path)) {
            return;
        }

        if ($scope->getTestResult()->getResultCode() !== TestResult::FAILED) {
            return;
        }

        if (!$this->running_javascript()) {
            return;
        }

        $driver = $this->getSession()->getDriver();
        if (!method_exists($driver, 'getWebDriver')) {
            return;
        }

        try {
            /** @var RemoteWebDriver $webdriver */
            $webdriver = $driver->getWebDriver();
            $entries = $webdriver->manage()->getLog('browser');
        } catch (\Exception $e) {
            return;
        }

        if (empty($entries)) {
            return;
        }

        $lines = [];
        foreach ($entries as $entry) {
            $timestamp = isset($entry['timestamp']) ? date('Y-m-d H:i:s', (int) ($entry['timestamp'] / 1000)) : '';
            $lines[] = sprintf('[%s] %s: %s', $timestamp, $entry['level'] ?? '', $entry['message'] ?? '');
        }

        [$dir, $filename] = $this->get_console_log_filename($scope);
        file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, implode(PHP_EOL, $lines));
    }

    /**
     * Determine the full pathname to store a console log dump, mirroring the naming used by the
     * core faildump mechanism (feature title + step text) so files can be correlated.
     *
     * @param AfterStepScope $scope scope passed by event fired after step.
     * @return array [string $dir, string $filename]
     */
    protected function get_console_log_filename(AfterStepScope $scope): array {
        global $CFG;

        if (self::$consolelogdir === null) {
            self::$consolelogdir = date('Ymd_His') . '_console';
            $dir = $CFG->behat_faildump_path . DIRECTORY_SEPARATOR . self::$consolelogdir;
            if (!is_dir($dir) && !mkdir($dir, $CFG->directorypermissions, true)) {
                throw new \Exception(
                    'No directories can be created inside $CFG->behat_faildump_path, check the directory permissions.'
                );
            }
        }
        $dir = $CFG->behat_faildump_path . DIRECTORY_SEPARATOR . self::$consolelogdir;

        $filename = $scope->getFeature()->getTitle() . '_' . $scope->getStep()->getText();
        $filename = preg_replace('/([^a-zA-Z0-9\_]+)/', '-', $filename);
        $filename = substr($filename, 0, 245) . '_' . $scope->getStep()->getLine() . '.log';

        return [$dir, $filename];
    }

    #[\Override]
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch (strtolower($type)) {
            case 'view':
                return new moodle_url(
                    '/mod/response/view.php',
                    ['id' => $this->get_cm_by_response_name($identifier)->id]
                );
        }
    }

    /**
     * Get a response by name.
     *
     * @param string $name response name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_response_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('response', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * Get a response cmid from the response name.
     *
     * @param string $name quiz name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_response_name(string $name): stdClass {
        $response = $this->get_response_by_name($name);
        return get_coursemodule_from_instance('response', $response->id, $response->course);
    }
}
