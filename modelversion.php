<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * The model version router
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use \tool_laaudit\model_version;

require_admin();

$configid = optional_param('configid', 0, PARAM_INT);

// Routes
// POST /admin/tool/laaudit/modelversion.php?configid=<configid>

// Set some page parameters.
$priorurl = new moodle_url('/admin/tool/laaudit/index.php');
$pageurl = new moodle_url('/admin/tool/laaudit/modelversion.php', array("configid" => $configid)); # POST /admin/tool/laaudit/modelversion.php?configid=1
$context = context_system::instance();

$PAGE->set_url($pageurl);
$PAGE->set_context($context);

if (!empty($configid) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $versionid = model_version::create_scaffold_and_get_for_config($configid);

    // if route contains auto param, do it automatically
    $version = new model_version($versionid);

    // how to include user selection of data while also allowing for dynamic collection?
    //dynamic approach?
    // maybe more like:
    // $version->add('dataset');
    // $version->set_training_and_test_data_split_by_analysis_interval(); //this already creates the training and testing datasets
    // later it could also be $version->add('training_dataset', $data); and $version->add('test_dataset', $data)
    // catch the case where training/testing dataset is trying to be added but none has been added as a property yet
    // $version->add('training_dataset');
    // $version->add('testing_dataset');
    // $version->add(new dataset($data));

    // how do I get the evidence collection logic dynamically into the evidence classes?! They need data from the version...
    $version->add('dataset');
    $version->add('training_dataset'); // needs split info
    $version->add('test_dataset'); // needs split info, related to training dataset
    $version->add('features_dataset'); // create features dataset for training and testing data - unsure split or merge
    $version->add('model');
    $version->add('predictions_dataset');

    //other approach:

    $version->set_data();

    $version->set_training_and_test_data_split_by_analysis_interval();
    //$training_data = $version->get_training_data(); // store as evidence
    //$test_data = $version->get_test_data(); // store as evidence

    $version->calculate_features();

    // store as evidence:
    //$features_train = $version->get_features_training(); //with param or not? these kind of functions should probably go into a static method/ helper method?
    //$features_test = $version->get_features_test();

    $version->train(); //input are the indicators right? differentiate for download between features and training/test data sets of raw samples
    //$model = $version->get_model_data(); // weights and configuration, store as evidence

    $version->predict();
    //$prediction_data = $version->get_prediction_data(); // store as evidence

    redirect($priorurl);
}

