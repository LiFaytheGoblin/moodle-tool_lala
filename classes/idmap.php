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
 * The dataset class.
 *
 * @package     tool_laaudit
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_laaudit;

use Exception;

/**
 * Class for the complete dataset evidence item.
 */
class idmap {
    /** @var int[]|string[] $originalids used for anonymization */
    private array $originalids;
    /** @var int[]|string[] $pseudonyms - ids used instead of originalids */
    private array $pseudonyms;
    /** @var string $entitytype type of ids, eg. 'user', 'user_enrolment' */
    private string $entitytype;

    public function __construct(array $originalids, array $pseudonyms, string $entitytype) {
        if (count($originalids) !== count($pseudonyms)) throw new Exception('Must provide as many pseudonyms as originalids.');
        $this->originalids = $originalids;
        $this->pseudonyms = $pseudonyms;
        $this->entitytype = $entitytype;
    }

    public static function create_from_dataset($dataset, $entitytype) {
        $orignalids = dataset_helper::get_ids_used_in_dataset($dataset);

        shuffle($orignalids);

        $offset = 2; // Moodle has two standard users, 1 and 2.
        $pseudonyms = range(1 + $offset, sizeof($orignalids) + $offset);

        shuffle($pseudonyms);

        return new static($orignalids, $pseudonyms, $entitytype);
    }

    public function count(): int {
        return count($this->pseudonyms);
    }

    public function has_original_id(mixed $originalid): bool {
        return in_array($originalid, $this->originalids);
    }

    public function get_pseudonym_sampleid(mixed $originalsampleid) : mixed {
        $originalid = dataset_helper::get_id_part($originalsampleid);
        if (!$this->has_original_id($originalid)) throw new Exception('Idmap is incomplete. No pseudonym found for id.');

        $pseudonym = $this->get_pseudonym($originalid);
        $analysisintervalpart = dataset_helper::get_analysisinterval_part($originalsampleid);

        if (isset($analysisintervalpart)) {
            $pseudonym = $pseudonym.'-'.$analysisintervalpart; // Add analysisintervalpart if it exists.
        }

        return $pseudonym;
    }

    public function get_pseudonym(mixed $originalid) : mixed {
        $index = array_search ($originalid, $this->originalids);
        return $this->pseudonyms[$index];
    }

    public function get_pseudonyms() : mixed {
        return $this->pseudonyms;
    }

    public function get_originalid($pseudonym) : mixed {
        $index = array_search ($pseudonym, $this->pseudonyms);
        return $this->originalids[$index];
    }

    public function get_originalids() : mixed {
        return $this->originalids;
    }

    public function contains(idmap $other) : bool {
        foreach ($this->originalids as $myoriginalid) {
            if (!$other->has_original_id($myoriginalid)) return false;
            if ($this->get_pseudonym($myoriginalid) != $other->get_pseudonym($myoriginalid)) return false;
        }
        return true;
    }

    public function get_entitytype() : string {
        return $this->entitytype;
    }
}
