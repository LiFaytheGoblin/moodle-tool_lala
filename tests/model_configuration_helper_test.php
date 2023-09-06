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

global $CFG;
require_once($CFG->dirroot . '/admin/tool/lala/classes/model_configuration_helper.php');
require_once(__DIR__ . '/fixtures/test_model.php');
require_once(__DIR__ . '/fixtures/test_config.php');

/**
 * Model configuration helper test
 *
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_configuration_helper_test extends advanced_testcase {
    /**
     * Check that init_and_get_all_model_config_objs() creates model configurations and returns them.
     *
     * @covers ::tool_lala_model_model_configuration_helper_init_and_get_all_model_config_objs
     */
    public function test_model_configurations_init_and_get_all_model_config_objs(): void {
        $this->resetAfterTest(true);

        $nmodels = test_model::count_models();
        $configs = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels, count($configs)); // A config is created for each existing model.
        $this->assertEquals([], $configs[0]->versions);
        $this->assertTrue($configs[0]->modelanalysabletype != null);

        $tobedeletedmodelid = test_model::create();
        $nmodels = test_model::count_models();
        $configs2 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels, count($configs2)); // A config has been added for the new model.

        $nmodelsbeforedeletion = test_model::count_models();
        test_model::delete($tobedeletedmodelid);
        $configs3 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodelsbeforedeletion, count($configs3)); // Config belonging to deleted model should still be there.
    }

    /**
     * Check that init_and_get_all_model_config_objs() creates new model configs if former configs have been updated.
     *
     * @covers ::tool_lala_model_model_configuration_helper_init_and_get_all_model_config_objs
     */
    public function test_model_configurations_model_update_init_and_get_all_model_config_objs(): void {
        $this->resetAfterTest(true);

        $modelid = test_model::create();
        $nmodels = test_model::count_models();
        $configs = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels, count($configs)); // A config is created for each existing model.
        $this->assertEquals([], $configs[0]->versions);
        $this->assertTrue($configs[0]->modelanalysabletype != null);

        sleep(1);

        // Update a model.
        test_model::update($modelid, 'predictionsprocessor');
        $configs4 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 1, count($configs4)); // New config belonging to updated model should be created.

        sleep(1);

        // Update a model again.
        test_model::update($modelid, 'timesplitting');
        $configs5 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 2, count($configs5)); // New config belonging to updated model should be created.

        sleep(1);

        // Update a model again.
        test_model::update($modelid, 'indicators');
        $configs6 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 3, count($configs6)); // New config belonging to updated model should be created.

        sleep(1);

        // Reset model = update model to previous state.
        test_model::reset($modelid);
        $configs7 = model_configuration_helper::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 3, count($configs7)); // No new config belonging to updated model should be created.
    }

    /**
     * Check that create_and_get_for_model() references and creates correct configs.
     *
     * @covers ::tool_lala_model_configuration_helper_get_or_create_and_get_for_model
     */
    public function test_model_configuration_create_and_get_for_model() {
        $this->resetAfterTest(true);

        $modelid = test_model::create();

        // No config exists yet for the model, so create one.
        $maxidbeforenewconfigcreation = test_config::get_highest_id();
        $returnedconfigid = model_configuration_helper::create_and_get_for_model($modelid);
        $this->assertGreaterThan($maxidbeforenewconfigcreation,
                $returnedconfigid); // New config has been created, is referenced.
    }

    /**
     * Check that get_model_config_obj() throws an error if the provided model id does not exist neither in analytics_models nor
     * in tool_lala_model_configs.
     *
     * @covers ::tool_lala_model_configuration_get_or_create_and_get_for_model
     */
    public function test_model_configuration_create_and_get_for_model_error() {
        $this->expectException(Exception::class);
        model_configuration_helper::create_and_get_for_model(test_model::get_highest_id() + 1);
    }
}
