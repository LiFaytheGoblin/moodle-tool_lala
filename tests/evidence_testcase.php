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

namespace tool_lala;

use advanced_testcase;
use Exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_course_with_students.php');
require_once(__DIR__ . '/fixtures/test_analyser.php');
require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');

/**
 * Dataset test.
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class evidence_testcase extends advanced_testcase {
    /** @var mixed $evidence the evidence that is created during set up of an implementing class. */
    protected mixed $evidence;
    /** @var int $modelid the id of the belonging Moodle model. */
    protected int $modelid;
    /** @var int $versionid the id of the belonging model version. */
    protected int $versionid;

    /**
     * Set up resources before each test.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $this->versionid = test_version::create($configid);
    }
    /**
     * Check that collect throws an error if trying to call it twice for the same evidence.
     *
     * @covers ::tool_lala_evidence_collect
     */
    protected function test_evidence_collect_error_again() : void {
        $options = $this->get_options();
        $this->evidence->collect($options);

        $this->expectException(Exception::class); // Expect exception if trying to collect again.
        $this->evidence->collect($options);
    }
    /**
     * Get the options object needed for collecting this evidence.
     *
     * @return array
     */
    abstract public function get_options(): array;
}
