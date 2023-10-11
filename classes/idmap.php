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
 * @package     tool_lala
 * @copyright   2023 Linda Fernsel <fernsel@htw-berlin.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lala;

use Countable;
use Exception;

/**
 * Class for the complete dataset evidence item.
 */
class idmap implements Countable {
    /** @var int[] $originalids used for anonymization */
    private array $originalids;
    /** @var int[] $pseudonyms - ids used instead of originalids */
    private array $pseudonyms;

    /** Construct the idmap instance.
     *
     * @param int[] $originalids
     * @param int[] $pseudonyms
     * @throws Exception
     */
    public function __construct(array $originalids, array $pseudonyms) {
        if (count($originalids) === 0) {
            throw new Exception('Can not create empty idmap. No ids provided.');
        }
        if (count(array_unique($originalids)) !== count($originalids)) {
            throw new Exception('Duplicate ids found. Ids must be unique.');
        }
        if (count($originalids) !== count($pseudonyms)) {
            throw new Exception('Must provide as many pseudonyms as originalids.');
        }

        $this->originalids = $originalids;
        $this->pseudonyms = $pseudonyms;
    }

    /** Create an idmap based on an array of ids
     *
     * @param int[] $ids
     * @return idmap
     * @throws Exception
     * @throws Exception
     */
    public static function create_from_ids(array $ids) : self {
        $pseudonyms = self::create_pseudonyms($ids);

        return new static($ids, $pseudonyms);
    }

    /** Create pseudonyms for an array of ids.
     *
     * @param int[] $orignalids
     * @return int[]
     */
    public static function create_pseudonyms(array $orignalids): array {
        shuffle($orignalids);
        $possiblefactors = range(3, 10);
        $offset = 100;
        shuffle($possiblefactors);
        $actualfactor = end($possiblefactors);
        $possiblepseudonyms = range($offset, $offset * $actualfactor * count($orignalids));
        shuffle($possiblepseudonyms);
        $actualpseudonyms = array_slice($possiblepseudonyms, 0, count($orignalids));
        shuffle($actualpseudonyms);
        return $actualpseudonyms;
    }

    /** Extract the id from the sampleid (id-analysisintervalpart), get the pseudonym for it and re-append the analysisintervalpart.
     *
     * @param string $originalsampleid
     * @return string the pseudonomized sampleid
     * @throws Exception
     * @throws Exception
     */
    public function get_pseudonym_sampleid(string $originalsampleid) : string {
        $originalid = intval(dataset_helper::get_id_part($originalsampleid));
        if (!$this->has_original_id($originalid)) {
            throw new Exception('Idmap is incomplete. No pseudonym found for id.');
        }

        $pseudonym = $this->get_pseudonym($originalid);
        $analysisintervalpart = dataset_helper::get_analysisinterval_part($originalsampleid);

        if (isset($analysisintervalpart)) {
            $pseudonym = $pseudonym.'-'.$analysisintervalpart; // Add analysisintervalpart if it exists.
        }

        return $pseudonym;
    }

    /** Verify whether the provided id can be found in the ids of the idmap.
     *
     * @param int $originalid
     * @return bool whether the original id exists
     */
    public function has_original_id(int $originalid): bool {
        return in_array($originalid, $this->originalids);
    }

    /** Return the pseudonym for an id.
     *
     * @param int $originalid
     * @return int the pseudonym
     */
    public function get_pseudonym(int $originalid) : int {
        $index = array_search($originalid, $this->originalids);
        return $this->pseudonyms[$index];
    }

    /** Getter for pseudonyms
     *
     * @return int[] pseudonyms
     */
    public function get_pseudonyms() : array {
        return $this->pseudonyms;
    }

    /** Return the original id for a pseudonym.
     *
     * @param int $pseudonym
     * @return int
     */
    public function get_originalid(int $pseudonym) : int {
        $index = array_search($pseudonym, $this->pseudonyms);
        return $this->originalids[$index];
    }

    /** Verify if the id-pseudonym mappings of another idmap are part of this idmap.
     * Useful for testing.
     *
     * @param idmap $other
     * @return bool whether $other is contained in this idmap
     */
    public function contains(idmap $other) : bool {
        $otheroriginalids = $other->get_originalids();
        foreach ($otheroriginalids as $otheroriginalid) {
            if ($this->has_original_id($otheroriginalid)) {
                return false;
            }
            if ($this->get_pseudonym($otheroriginalid) != $other->get_pseudonym($otheroriginalid)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Getter for original ids
     *
     * @return int[] originalids
     */
    public function get_originalids() : array {
        return $this->originalids;
    }

    /**
     * String representation of an idmap
     *
     * @return string
     */
    public function __toString() : string {
        return json_encode(array_combine($this->originalids, $this->pseudonyms));
    }

    /**
     * Return amount of originalid - pseudonym pairs in the idmap.
     * Useful for testing
     *
     * @return int
     */
    public function count(): int {
        return count($this->pseudonyms);
    }
}
