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

namespace tool_laaudit;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_evidence.php');
require_once(__DIR__ . '/evidence_testcase.php');

use context_system;

/**
 * Model evidence test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class evidence_test extends evidence_testcase {
    /**
     * Set up resources before each test.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $this->versionid = test_version::create($configid);
    }
    /**
     * Check that create_scaffold_and_get_for_version() creates an evidence scaffold.
     *
     * @covers ::tool_laaudit_evidence_create_scaffold_and_get_for_version
     */
    public function test_evidence_create_scaffold_and_get_for_version() : void {
        // Create a new piece of evidence for the version.
        $evidence = test_evidence::create_scaffold_and_get_for_version($this->versionid);
        $this->assertEquals($evidence->get_versionid(), $this->versionid);

        // Delete model and construct evidence from a version of a now deleted model
        test_model::delete($this->modelid);
        $evidence2 = test_evidence::create_scaffold_and_get_for_version($this->versionid);
        $this->assertEquals($evidence2->get_versionid(), $this->versionid);
    }

    /**
     * Check that create_scaffold_and_get_for_version() throws an error if the provided version id does not exist in tool_laaudit_evidence.
     *
     * @covers ::tool_laaudit_evidence_create_scaffold_and_get_for_version
     */
    public function test_evidence_create_scaffold_and_get_for_version_error() : void {
        $this->expectException(\Exception::class);
        test_evidence::create_scaffold_and_get_for_version(test_version::get_highest_id() + 1);
    }

    /**
     * Check that store() creates a file which has as content the filestring
     *
     * @covers ::tool_laaudit_evidence_store
     */
    public function test_evidence_store() : void {
        // Create a new piece of evidence for the version and store it.
        $evidenceid = test_evidence::create($this->versionid);
        $evidence = new test_evidence($evidenceid);

        $evidence->collect($this->get_options());
        $evidence->serialize();

        $evidence->store();

        // Read the file content.
        $fs = get_file_storage();
        $file = $fs->get_file(context_system::instance()->id, 'tool_laaudit', 'tool_laaudit', $evidenceid,
                '/evidence/', 'modelversion' . $this->versionid . '-evidence' . $evidence->get_name() . $evidenceid . '.' .
                $evidence::FILETYPE);
        $contents = $file->get_content();

        $this->assertEquals(test_evidence::DATASTRING, $contents);
    }

    /**
     * Check that store() throws an error if no serialized filestring exists yet.
     *
     * @covers ::tool_laaudit_evidence_store
     */
    public function test_evidence_store_error() : void {
        // Create a new piece of evidence for the version.
        $evidence = test_evidence::create_scaffold_and_get_for_version($this->versionid);

        // Expect that an exception is thrown when trying to store without first collecting evidence.
        $this->expectException(\Exception::class);
        $evidence->store();
    }

    /**
     * Check that finish() sets the timecollectionfinished property in the database and field
     *
     * @covers ::tool_laaudit_evidence_finish
     */
    public function test_evidence_finish() : void {
        // Create a new piece of evidence for the version and store it.
        $evidenceid = test_evidence::create($this->versionid);
        $evidence = new test_evidence($evidenceid);

        $evidence->finish();

        $this->assertTrue($evidence->get_timecollectionfinished() !== null);

        global $DB;
        $finished = $DB->get_fieldset_select('tool_laaudit_evidence', 'timecollectionfinished', 'id='.$evidenceid);
        $this->assertTrue($finished !== null);
    }

    /**
     * Check that abort() deletes the evidence from the database
     *
     * @covers ::tool_laaudit_evidence_abort
     */
    public function test_evidence_abort() : void {
        // Create a new piece of evidence for the version and store it.
        $evidenceid = test_evidence::create($this->versionid);
        $evidence = new test_evidence($evidenceid);

        // get db entry
        global $DB;
        $resultid = $DB->get_fieldset_select('tool_laaudit_evidence', 'id', 'id='.$evidenceid)[0];
        $this->assertEquals($evidenceid, $resultid);

        $evidence->abort();

        $resultids = $DB->get_fieldset_select('tool_laaudit_evidence', 'id', 'id='.$evidenceid);
        $this->assertEquals([], $resultids);
    }

    function get_options(): array {
        return [];
    }
}
