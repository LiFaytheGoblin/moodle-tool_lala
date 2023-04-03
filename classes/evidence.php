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

namespace tool_laaudit;

use stdClass;

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
    private $timecollectionfinished;
    /** @var string $serializedfilelocation path of the evidence. */
    private $serializedfilelocation;
    /** @var string $data raw data of the evidence. */
    private $data;
    /**
     * Constructor. Deserialize DB object.
     *
     * @param int $id of the evidence
     * @return void
     */
    public function __construct($id) {
        global $DB;

        $evidence = $DB->get_record('tool_laaudit_evidence', array('id' => $id), '*', MUST_EXIST);

        // Fill properties from DB.
        $this->id = $evidence->id;
        $this->versionid = $evidence->versionid;
        $this->name = $evidence->name;
        $this->timecollectionstarted = $evidence->timecollectionstarted;
        $this->timecollectionfinished = $evidence->timecollectionfinished;
        $this->serializedfilelocation = $evidence->serializedfilelocation;
    }

    /**
     * Returns a stdClass with the evidence data.
     * @param int $versionid of the version
     * @return stdClass of the created evidence
     */
    public static function create_and_get_for_version($versionid, $data = null) {
        global $DB;

        $obj = new stdClass();

        $obj->versionid = $versionid;
        $obj->name = get_called_class();
        $obj->timecollectionstarted = time();

        $id = $DB->insert_record('tool_laaudit_evidence', $obj);

        $evidence = new static($id);
        $evidence->collect($data);
        $evidence->serialize();

        $DB->set_field('tool_laaudit_evidence', 'serializedfilelocation', $evidence->get_serializedfilelocation(), array('id' => $id));

        // note down end
        $DB->set_field('tool_laaudit_evidence', 'timecollectionfinished', time(), array('id' => $id));

        return $evidence;
    }

    /**
     * Serializes the raw data and stores it in a file. Sets the serializedfilelocation.
     * @return file serialized data
     */
    abstract function serialize();

    abstract function collect($data = null); //either auto collection, or setting the data directly. where to get the necessary info from tho? maybe pass model_version?

    public function get_serializedfilelocation() {
        return $this->serializedfilelocation;
    }

    public function get_raw_data() {
        return $this->data;
    }

    public function get_id() {
        return $this->id;
    }
}