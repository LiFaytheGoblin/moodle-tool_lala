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
 * The abstract evidence class that can be extended.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit\output;

abstract class evidence {
    /** @var int $id id assigned to the configuration by the db. */
    private $id;
    /** @var int $versionid id of the belonging model version. */
    private $versionid;
    /** @var string $name of the evidence. */
    private $name;
    /** @var string $timecollectionstarted of the evidence. */
    private $timecollectionstarted;
    /** @var string $timecollectionended of the evidence. */
    private $timecollectionended;
    /** @var string $serializedfilelocation path of the evidence. */
    private $serializedfilelocation;
    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the evidence
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $evidence = $DB->get_record('tool_laaudit_model_evidence', array('id' => $id), '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $evidence->id;
        $this->versionid = $evidence->versionid;
        $this->name = $evidence->name;
        $this->timecollectionstarted = $evidence->timecollectionstarted;
        $this->timecollectionended = $evidence->timecollectionended;
        $this->serializedfilelocation = $evidence->serializedfilelocation;
    }

    /**
     * Returns a stdClass with the evidence data.
     * @param int $versionid of the version
     * @return stdClass
     */
    public static function create_and_get_for_version($versionid) {
        global $DB;

        $obj = new stdClass();

        $obj->versionid = $versionid;

        $obj->name = ""; //classname
        $obj->timecollectionstarted = time();

        return $DB->insert_record('tool_laaudit_model_versions', $obj);
    }

    /**
     * Triggers collection of the evidence data.
     */
    public function trigger_evidence_collection_and_update() {
        global $DB;

        $data = $this->collect();
        $serializeddata = serialize($data);
        // todo: store data and retrieve location
        $this->serializedfilelocation = "";
        $this->timecollectionended = time();

        $DB->set_field('tool_laaudit_evidence', 'serializedfilelocation', $this->serializedfilelocation, array('id' => $this->id));
        $DB->set_field('tool_laaudit_evidence', 'timecollectionended', $this->timecollectionended, array('id' => $this->id));
    }

    /**
     * Returns the evidence data.
     * @return data data
     */
    abstract function collect();

    /**
     * Returns the serialized evidence data.
     * @param any $data produced by the evidence
     * @return file serialized data
     */
    abstract function serialize($data);
}