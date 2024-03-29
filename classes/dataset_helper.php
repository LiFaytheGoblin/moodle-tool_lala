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
use InvalidArgumentException;
use LengthException;
use stored_file;

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

        if (!isset($dataset[$analysisintervalkey]) || count($dataset[$analysisintervalkey]) < 2) {
            throw new DomainException('Data array to be shuffled needs to be at least of size 2.
            The first item is kept as item one, being treated as the header.');
        }

        $data = self::get_rows($dataset); // Without header.
        $shuffleddata = self::shuffle_array_preserving_keys($data);

        return [$analysisintervalkey => self::get_first_row($dataset) + $shuffleddata];
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
     * Extract rows from dataset, without the header.
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
            $xs = array_slice($row, 0, $len - 1, true);
            $y = $row[$len - 1];

            $testx[] = $xs;
            $testy[] = $y;
        }

        return [
                'x' => $testx,
                'y' => $testy
        ];
    }

    /**
     * Build a dataset in the correct structure from analysisintervalkey, header, sampleids, x values and y values
     * (will be joined for rows)
     *
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

        return [$analysisintervalkey => $mergeddata];
    }

    /**
     * Parses a CSV file into a valid dataset.
     *
     * @param false|resource $filehandle
     * @param string $analysisintervalkey
     * @return array
     */
    public static function build_from_csv(mixed $filehandle, string $analysisintervalkey): array {
        if (!$filehandle) {
            throw new InvalidArgumentException('Filehandle is not available. Need filehandle to parse uploaded CSV into dataset.');
        }

        $mergeddata = [];
        while ($row = fgetcsv($filehandle)) {
            $sampleid = $row[0];
            $values = array_slice($row, 1);
            if ($sampleid == 'sampleid') { // We have the header!
                $mergeddata['0'] = $values;
                continue;
            }
            $mergeddata[$sampleid] = $values;
        }
        fclose($filehandle);

        return [$analysisintervalkey => $mergeddata];
    }

    /**
     * Replace all rows (except the header) in a dataset with a new bunch of rows.
     *
     * @param array $dataset
     * @param array $newrows
     * @return array
     */
    public static function replace_rows_in_dataset(array $dataset, array $newrows) : array {
        $analysisintervalkey = self::get_analysisintervalkey($dataset);
        $header = self::get_first_row($dataset);
        return [$analysisintervalkey => $header + $newrows];
    }

    /**
     * Marges two datasets of equal structure.
     *
     * @param array $dataseta
     * @param array $datasetb
     * @return array[]
     */
    public static function merge(array $dataseta, array $datasetb) : array {
        if (count($dataseta) < 1) {
            return $datasetb;
        }
        if (count($datasetb) < 1) {
            return $dataseta;
        }

        $analysisintervalkeya = self::get_analysisintervalkey($dataseta);
        $analysisintervalkeyb = self::get_analysisintervalkey($datasetb);
        if ($analysisintervalkeya == $analysisintervalkeyb) {
            $headera = self::get_first_row($dataseta);
            $headerb = self::get_first_row($datasetb);
            if ($headera == $headerb) {
                $rowsa = self::get_rows($dataseta);
                $rowsb = self::get_rows($datasetb);
                return [$analysisintervalkeya => $headera + $rowsa + $rowsb];
            }
        }
        throw new \InvalidArgumentException('Datasets are not equal in structure.');
    }

    /**
     * Get dataset of those elements in a that are not in b - decided by sampleid.
     *
     * @param array $dataseta
     * @param array $datasetb
     * @return array
     */
    public static function diff(array $dataseta, array $datasetb) : array {
        if (count($dataseta) < 1) {
            return $datasetb;
        }
        if (count($datasetb) < 1) {
            return $dataseta;
        }

        $sampleidsb = self::get_sampleids_used_in_dataset($datasetb);
        $rowsa = self::get_rows($dataseta);

        $datasetrowsdiff = [];
        foreach ($rowsa as $sampleid => $row) {
            if (!in_array($sampleid, $sampleidsb)) {
                $datasetrowsdiff[$sampleid] = $row;
            }
        }
        return self::replace_rows_in_dataset($dataseta, $datasetrowsdiff);
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
     * @return int[]
     */
    public static function get_ids_used_in_dataset(array $dataset) : array {
        $ids = [];

        $analysisintervalkey = self::get_analysisintervalkey($dataset);

        foreach ($dataset[$analysisintervalkey] as $sampleid => $value) {
            if ($sampleid == '0') {
                continue; // Skip the header.
            }

            $id = intval(self::get_id_part($sampleid));
            $ids[$id] = $id; // Avoid duplicates while preserving the order.
        }

        return array_values($ids);
    }

    /**
     * Get the sampleids that have been used in a dataset (excluding the id used for the header),
     * in correct order.
     *
     * @param array $dataset
     * @return string[]
     */
    public static function get_sampleids_used_in_dataset(array $dataset) : array {
        $sampleids = [];

        $analysisintervalkey = self::get_analysisintervalkey($dataset);

        foreach ($dataset[$analysisintervalkey] as $sampleid => $value) {
            if ($sampleid == '0') {
                continue; // Skip the header.
            }
            $sampleids[$sampleid] = $sampleid; // Avoid duplicates while preserving the order.
        }

        return array_values($sampleids);
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

    /**
     * Create idmap from a dataset of a specific type.
     *
     * @param array $dataset
     * @return idmap
     * @throws Exception
     * @throws Exception
     */
    public static function create_idmap_from_dataset(array $dataset): idmap {
        return idmap::create_from_ids(self::get_ids_used_in_dataset($dataset));
    }

    /**
     * Validate a dataset array.
     *
     * @param array $dataset
     */
    public static function validate(array $dataset) : void {
        // Check if csv contains at least two rows.
        $header = self::get_first_row($dataset);
        if (count($header) < 1) {
            throw new LengthException('No header found in the data.');
        }

        $rows = self::get_rows($dataset);
        if (count($rows) < 1) {
            throw new LengthException('No rows besides a header found in the data.');
        }

        // Check if header contains an indicator name and a target name, but not "sampleid".
        $headerstring = implode(',', reset($header));
        var_dump($headerstring);
        if (str_contains($headerstring, 'sampleid')) {
            throw new InvalidArgumentException('Header must not contain a sampleid column.
             The sampleid is the array index and does not need its own row.');
        }
        if (!str_contains($headerstring, 'indicator')) {
            throw new InvalidArgumentException('Header needs to contain an indicator column but does not: '.$headerstring);
        }
        if (!str_contains($headerstring, 'target')) {
            throw new InvalidArgumentException('Header needs to contain a target column but does not: '.$headerstring);
        }
    }

    /**
     * Transform a dataset (a two dimensional array) into a string which can be
     * stored as a CSV. Elements at id '0' are treated as the header.
     *
     * @param array $dataset
     * @return string
     */
    public static function serialize(array $dataset) : string {
        $str = '';
        $columns = null;

        foreach ($dataset as $record) {
            $ids = array_keys($record);
            foreach ($ids as $id) {
                if ($id == '0') {
                    $columns = implode(',', $record[$id]);
                    continue;
                }
                $str = $str . $id . ',' . implode(',', $record[$id]) . "\n";
            }
        }

        $comma = (isset($columns)) ? ',' : null;
        $heading = 'sampleid' . $comma . $columns . "\n";
        return $heading.$str;
    }
}
