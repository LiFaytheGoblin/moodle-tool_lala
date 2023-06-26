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

use Phpml\ModelManager;
use Phpml\Estimator;
/**
 * Model test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_test extends \advanced_testcase {
    private $evidence;
    private $predictor;
    protected function setUp(): void {
        $this->resetAfterTest(true);

        $this->modelid = test_model::create();
        $configid = test_config::create($this->modelid);
        $versionid = test_version::create($configid);

        $this->evidence = model::create_scaffold_and_get_for_version($versionid);

        $this->predictor = test_version::get_predictor($versionid);
    }
    /**
     * Data provider for {@see model_collect()}.
     *
     * @return array List of source data information
     */
    public function tool_laaudit_get_source_data_parameters_provider() {
        return [
                'Min datapoints' => [
                        'ndatapoints' => 3
                ],
                'Some datapoints' => [
                        'ndatapoints' => 10,
                ]
        ];
    }
    /**
     * Check that collect trains the model.
     *
     * @covers ::tool_laaudit_model_collect
     *
     * @dataProvider tool_laaudit_get_source_data_parameters_provider
     * @param int $ndatapoints amount of datapoints in training data
     */
    public function test_model_collect($ndatapoints) {
        $dataset = test_dataset_evidence::create($ndatapoints);

        $options=[
                'data' => $dataset,
                'predictor' => $this->predictor,
        ];
        $this->evidence->collect($options);

        $trained_model = $this->evidence->get_raw_data();
        // Check that the $data property is set to a TRAINED LogisticRegression model.
        $this->assertEquals('Phpml\Classification\Linear\LogisticRegression', get_class($trained_model));

        $size = 3;
        $testx = test_dataset_evidence::create_x($size);
        $predictedlabels = $trained_model->predict($testx);
        $this->assertEquals($size, sizeof($predictedlabels));

        // Test serialize().
        $this->evidence->serialize();

        // Store the serialized data.
        $content = $this->evidence->get_serialized_data();
        $path = '\testfiles\testmodel'.$this->modelid.'.ser';
        file_put_contents($path, $content);

        // Verify that serialized data is valid (it is if we can restore the classifier from it, and get predictions from it).
        $importer = new ModelManager();
        $imported = $importer->restoreFromFile($path);
        $this->assertTrue($imported != null);
        $this->assertTrue($imported instanceof Estimator);
        $size = 3;
        $testxforimported = test_dataset_evidence::create_x($size);
        $predictedlabelsforimported = $imported->predict($testxforimported);
        $this->assertEquals($size, sizeof($predictedlabelsforimported));
    }

    public function test_model_collect_error_again() {
        $dataset = test_dataset_evidence::create();

        $options=[
                'data' => $dataset,
                'predictor' => $this->predictor,
        ];
        $this->evidence->collect($options);

        $this->expectException(\Exception::class); // Expect exception if trying to collect again.
        $this->evidence->collect($options);
    }

    public function test_model_collect_deletedmodel() {
        test_model::delete($this->modelid);

        $dataset = test_dataset_evidence::create();

        $options=[
                'data' => $dataset,
                'predictor' => $this->predictor,
        ];

        $this->evidence->collect($options);

        $trained_model = $this->evidence->get_raw_data();
        // Check that the $data property is set to a LogisticRegression model.
        $this->assertEquals('Phpml\Classification\Linear\LogisticRegression', get_class($trained_model));
    }

    public function test_dataset_serialize_error_nodata() {
        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }

    public function test_model_serialize_error_again() {
        $dataset = test_dataset_evidence::create(3);

        $options=[
                'data' => $dataset,
                'predictor' => $this->predictor,
        ];
        $this->evidence->collect($options);

        $trained_model = $this->evidence->get_raw_data();

        $testx = test_dataset_evidence::create_x(3);
        $trained_model->predict($testx);

        // Test serialize()
        $this->evidence->serialize();

        // Expect error if trying to serialize again.
        $this->expectException(\Exception::class); // Expect exception if no data collected yet.
        $this->evidence->serialize();
    }
}
