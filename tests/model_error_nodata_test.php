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

global $CFG;
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/model.php');
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_version.php');
require_once(__DIR__ . '/fixtures/test_dataset_evidence.php');

/**
 * Model test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_error_nodata_test extends \advanced_testcase {
    private $evidence;
    private $predictor;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);
        $versionid = test_version::create($configid);

        $this->evidence = model::create_scaffold_and_get_for_version($versionid);

        $this->predictor = test_version::get_predictor();
    }
    /**
     * Data provider for {@see test_model_collect_error_nodata()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'No dataset' => [
                        'dataset' => []
                ],
                'Just header' => [
                        'dataset' => test_dataset_evidence::create(0)
                ],
                'Too small dataset' => [
                        'dataset' => test_dataset_evidence::create(1)
                ]
        ];
    }
    /**
     * Check that collect trains the model.
     *
     * @covers ::tool_laaudit_model_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param int $dataset training dataset
     */
    public function test_model_collect_error_nodata($dataset) {
        $options=[
                'data' => $dataset,
                'predictor' => $this->predictor,
        ];
        $this->expectException(\Exception::class); // Expect exception if trying to collect but no(t enough) data exists.
        $this->evidence->collect($options);
    }

    public function test_model_serialize_error_nodata() {
        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }
}
