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

use core_analytics\local\analysis\result_array;
use core_analytics\analysis;
use core_php_time_limit;
use DomainException;
use Exception;

/**
 * Class for the complete dataset evidence item.
 */
class dataset_helper {
    /**
     * Shuffle a data set while preserving the key and the header.
     *
     * @param array $dataset
     * @return array shuffled data
     */
    public static function get_shuffled(array $dataset): array {
        if (count($dataset) == 0) {
            throw new DomainException('Data array to be shuffled can not be empty.');
        }

        $analysisintervalkey = self::get_analysisintervalkey($dataset);

        $arr = $dataset[$analysisintervalkey];

        if (count($arr) == 1) {
            throw new DomainException('Data array to be shuffled needs to be at least of size 2.
            The first item is kept as item one, being treated as the header.');
        }

        $header = self::get_first_row($dataset);
        $remainingdata = self::get_rows($dataset);

        $shuffleddata = self::shuffle_array_preserving_keys($remainingdata);

        $datawithheader = [];
        $datawithheader[$analysisintervalkey] = $header + $shuffleddata;

        return $datawithheader;
    }

    /**
     * Shuffle the provided array while keeping the key-value connection.
     *
     * @param array $arr
     * @return array shuffled array
     */
    public static function shuffle_array_preserving_keys(array $arr) : array {
        $keys = array_keys($arr);
        if (count($keys) < 2) {
            return $arr;
        }
        shuffle($keys);
        $shuffleddata = [];
        foreach ($keys as $key) {
            // Assign to each key in the random order the value from the original array.
            $shuffleddata[$key] = $arr[$key];
        }
        return $shuffleddata;
    }

    /**
     * Extract rows from dataset.
     *
     * @param array $dataset
     * @return array rows
     */
    public static function get_rows(array $dataset) : array {
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        return array_slice($dataset[$analysisintervalkey], 1, null, true);
    }

    /**
     * Extract x and y values from rows.
     *
     * @param array $rows
     * @return array ['x' => array, 'y' => array]
     */
    public static function get_separate_x_y_from_rows(array $rows) : array {
        $testx = [];
        $testy = [];
        foreach ($rows as $row) {
            $len = count($row);
            $testx[] = array_slice($row, 0, $len - 1, true);
            $testy[] = $row[$len - 1];
        }
        return [
                'x' => $testx,
                'y' => $testy
        ];
    }

    /**
     * Build a dataset in the correct structure from analysisintervalkey, header, sampleids, x values and y values (will be joined for rows)
     * @param string $analysisintervalkey
     * @param array $header
     * @param array $sampleids
     * @param int[]|string[] $xs
     * @param int[]|string[] $ys
     * @return array
     */
    public static function build(string $analysisintervalkey, array $header, array $sampleids, array $xs, array $ys) : array {
        $mergeddata = [];
        $mergeddata['0'] = $header;
        foreach ($sampleids as $key => $sampleid) {
            $mergeddata[$sampleid] = [$xs[$key], $ys[$key]];
        }

        $res = [];
        $res[$analysisintervalkey] = $mergeddata;
        return $res;
    }

    /**
     * Replace all rows (except the header) in a dataset with a new bunch of rows.
     *
     * @param array $dataset
     * @param array $newrows
     * @return array
     */
    public static function replace_rows_in_dataset(array $dataset, array $newrows) : array {
        $res = [];
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        $header = self::get_first_row($dataset);
        $res[$analysisintervalkey] = $header + $newrows;
        return $res;
    }

    /**
     * Get the first row of the dataset, usually this will be the header.
     *
     * @param array $dataset
     * @return array
     */
    public static function get_first_row(array $dataset) : array {
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        return array_slice($dataset[$analysisintervalkey], 0, 1, true);
    }

    /**
     * Get the analysis interval key, that is the name of the analysisinterval type.
     *
     * @param array $dataset
     * @return string
     */
    public static function get_analysisintervalkey(array $dataset) : string {
        return array_keys($dataset)[0];
    }

    /**
     * Get the ids that have been used in a dataset (not the sampleids, and excluding the id used for the header),
     * in correct order.
     *
     * @param array $dataset
     * @return array [id => id]
     */
    public static function get_ids_used_in_dataset(array $dataset) : array {
        $ids = [];
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        $sampleids = array_keys($dataset[$analysisintervalkey]);
        unset($sampleids['0']); // Remove the header.
        foreach ($sampleids as $sampleid) {
            $id = self::get_id_part($sampleid);
            $ids[$id] = $id; // Preserve the order, avoid duplicates.
        }

        return array_keys($ids);
    }

    /**
     * Get the id part of a sample id in the form <id>-<intervalpart>.
     *
     * @param int|string $sampleid
     * @return string
     */
    public static function get_id_part(int|string $sampleid): string {
        return explode('-', $sampleid)[0];
    }

    /**
     * Get the analysis interval part of a sample id in the form <id>-<intervalpart>, if an intervalpart exists.
     *
     * @param int|string $sampleid
     * @return string|null
     */
    public static function get_analysisinterval_part(int|string $sampleid): ?string {
        $sampleidparts = explode('-', $sampleid);
        if (array_key_exists(1, $sampleidparts)) {
            return $sampleidparts[1];
        } else {
            return null;
        }
    }

    /** Create idmap from a dataset of a specific type.
     *
     * @param array $dataset
     * @return idmap
     * @throws Exception
     * @throws Exception
     */
    public static function create_idmap_from_dataset(array $dataset): idmap {
        $originalids = dataset_helper::get_ids_used_in_dataset($dataset);
        return idmap::create_from_ids($originalids);
    }
}
