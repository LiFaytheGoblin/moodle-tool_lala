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
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/model_configuration.php');
require_once(__DIR__ . '/fixtures/test_config.php');
require_once(__DIR__ . '/fixtures/test_model.php');

/**
 * Model configuration test.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_configuration_test extends \advanced_testcase {
    /**
     * Check that __construct() creates a model configuration.
     *
     * @covers ::tool_laaudit_model_configuration___construct
     */
    public function test_model_configuration_construct() {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $configid = test_config::create($modelid);

        // Create a model configuration from a config with an existing model
        $config = new model_configuration($configid);
        $this->assertEquals($config->get_id(), $configid);
        $this->assertEquals($config->get_modelid(), $modelid);
        $this->assertEquals($config->get_name(), test_model::NAME);
        $this->assertEquals($config->get_target(), test_model::TARGET);

        // Delete model and construct a model configuration from a config with a now deleted model
        test_model::delete($modelid);
        $config2 = new model_configuration($configid);
        $this->assertEquals($config2->get_id(), $configid);
        $this->assertEquals($config2->get_modelid(), $modelid);
    }

    /**
     * Check that __construct() throws an error if the provided config id does not exist in tool_laaudit_model_configs.
     *
     * @covers ::tool_laaudit_model_configuration___construct
     */
    public function test_model_configuration_construct_error() {
        $this->expectException(\dml_missing_record_exception::class);
        new model_configuration(test_config::get_highest_id() + 1);
    }

    /**
     * Check that create_and_get_for_model() references and creates correct configs.
     *
     * @covers ::tool_laaudit_model_configuration_get_or_create_and_get_for_model
     */
    public function test_model_configuration_create_and_get_for_model() {
        $this->resetAfterTest(true);

        $modelid = test_model::create();

        // No config exists yet for the model, so create one
        $maxidbeforenewconfigcreation = test_config::get_highest_id();
        $returnedconfigid = model_configuration::create_and_get_for_model($modelid);
        $this->assertGreaterThan($maxidbeforenewconfigcreation, $returnedconfigid); // A new config has been created and is referenced.
    }

    /**
     * Check that get_model_config_obj() throws an error if the provided model id does not exist neither in analytics_models nor in tool_laaudit_model_configs.
     *
     * @covers ::tool_laaudit_model_configuration_get_or_create_and_get_for_model
     */
    public function test_model_configuration_create_and_get_for_model_error() {
        $this->expectException(\Exception::class);
        model_configuration::create_and_get_for_model(test_model::get_highest_id() + 1);
    }
}
