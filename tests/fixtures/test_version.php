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

/**
 * Test model.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use \core_analytics\manager;
use \core_analytics\predictor;
use Phpml\Classification\Linear\LogisticRegression;

defined('MOODLE_INTERNAL') || die();
class test_version {
    const RELATIVETESTSETSIZE = 0.2;

    /**
     * Stores a version of a config in the db and returns a versionid
     *
     * @param int $configid
     * @return int
     */
    public static function create(int $configid) : int {
        global $DB;
        $valididversionobject = [
                'timecreationstarted' => time(),
                'analysisinterval' => test_model::ANALYSISINTERVAL,
                'predictionsprocessor' => test_model::PREDICTIONSPROCESSOR,
                'configid' => $configid,
                'indicators' => test_model::INDICATORS,
                'relativetestsetsize' => self::RELATIVETESTSETSIZE,
        ];
        $versionid = $DB->insert_record('tool_laaudit_model_versions', $valididversionobject);
        return $versionid;
    }

    /**
     * Get the highest config id that is currently in use.
     *
     * @return int configid
     */
    public static function get_highest_id(): int {
        global $DB;
        $existingversionids = $DB->get_fieldset_select('tool_laaudit_model_versions', 'id', '1=1');
        return (sizeof($existingversionids) > 0) ? max($existingversionids) : 1;
    }

    /**
     * Return whether an error has been registered for the version.
     *
     * @param int $versionid
     * @return bool
     */
    public static function haserror(int $versionid) : bool {
        global $DB;
        $error = $DB->get_fieldset_select('tool_laaudit_model_versions', 'error', 'id='.$versionid)[0];
        return isset($error);
    }

    /**
     * Get a predictor for this version.
     *
     * @return predictor predictor
     */
    public static function get_predictor() : predictor {
        $predictor = manager::get_predictions_processor(test_model::PREDICTIONSPROCESSOR);
        if(!$predictor) throw new \Exception('Predictor not found for predictionsprocessor with name '.test_model::PREDICTIONSPROCESSOR);
        return $predictor;
    }

    /**
     * Trains a classifier for a model version and returns it.
     *
     * @param int $versionid
     * @return LogisticRegression classifier
     */
    public static function get_classifier(int $versionid) : LogisticRegression {
        $dataset = test_dataset_evidence::create(3);
        $evidence = model::create_scaffold_and_get_for_version($versionid);
        $predictor = test_version::get_predictor();
        $options=[
                'data' => $dataset,
                'predictor' => $predictor,
        ];
        $evidence->collect($options);
        return $evidence->get_raw_data();
    }
}
