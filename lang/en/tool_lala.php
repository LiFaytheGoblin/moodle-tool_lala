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
 * Plugin strings are defined here.
 *
 * @package     tool_lala
 * @category    string
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Let(\')s audit Learning Analytics';
$string['nomodelconfigurations'] = 'No model configurations found. Create some models using the Learning Analytics functionality, before you can train them and collect evidence for auditing them.';
$string['nomodelversions'] = 'You have not created any models for this configuration yet. Thus, no evidence for auditing is available.';
$string['learnaboutauditing'] = 'Learn about model auditing';
$string['automaticallycreateversion'] = '+ automatically create new version';
$string['configid'] = 'config-id';
$string['modelid'] = 'model-id';
$string['target'] = 'target';
$string['version'] = 'version';
$string['versions'] = 'versions';
$string['versionid'] = 'version-id';
$string['started'] = 'started';
$string['finished'] = 'finished';
$string['analysisinterval'] = 'analysis interval';
$string['predictionsprocessor'] = 'predictions processor (ML backend)';
$string['analysisinterval'] = 'analysis interval';
$string['contexts'] = 'contexts';
$string['indicators'] = 'indicators';
$string['name'] = 'name';
$string['serializedfilelocation'] = 'location of file containing serialized data';
$string['unfinished'] = 'not finished yet';
$string['allcontexts'] = 'all contexts';
$string['defaultcontexts'] = 'default contexts used for data gathering';
$string['analysable'] = 'analysable';
$string['model'] = 'model';
$string['error'] = 'error';
$string['traintestsplit'] = 'train-test-split';
$string['traintest'] = 'Train: {$a->trainsize}%, Test: {$a->testsize}%';
$string['eventmodelversioncreated'] = 'Model version created';
$string['lala:viewpagecontent'] = 'View the content of the plugin page';
$string['lala:downloadevidence'] = 'Download the evidence produced for model versions';
$string['lala:createmodelversion'] = 'Trigger the creation of a new model version';
$string['privacy:metadata'] = 'This plugin only creates database tables concerning model configuration, version and evidence meta data. Some data is gathered from the Moodle instance during the model version creation, and provided to admins and auditors as evidence. This data is anonymized BEFORE storing it permanently a file.';