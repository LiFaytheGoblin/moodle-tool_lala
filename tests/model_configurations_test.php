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
require_once($CFG->dirroot . '/admin/tool/laaudit/classes/model_configurations.php');
require_once(__DIR__ . '/fixtures/test_model.php');

/**
 * Model configurations test
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class _model_configurations_test extends \advanced_testcase {
    /**
     * Check that init_and_get_all_model_config_objs() creates model configurations and returns them.
     *
     * @covers ::tool_laaudit_model_model_configurations_init_and_get_all_model_config_objs
     */
    public function test_model_configurations_init_and_get_all_model_config_objs() {
        $this->resetAfterTest(true);

        $nmodels = test_model::count_models();
        $configs = model_configurations::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels, sizeOf($configs)); // A config is created for each existing model.

        $tobedeletedmodelid = test_model::create();
        $configs2 = model_configurations::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 1, sizeOf($configs2)); // A config has been added for the new model.

        test_model::delete($tobedeletedmodelid);
        $configs3 = model_configurations::init_and_get_all_model_config_objs();
        $this->assertEquals($nmodels + 1, sizeOf($configs3)); // Config belonging to deleted model should still be there.
    }
}
