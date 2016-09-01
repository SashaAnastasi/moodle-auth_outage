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
 * The DB Context to manipulate Outages. Singleton class.
 *
 * @package    auth_outage
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_outage;

use Box\Spout\Common\Exception\InvalidArgumentException;

final class outagedb
{
    /**
     * @var outagedb Singleton reference created on first use.
     */
    private static $singleton = null;

    /**
     * Returns the singleton instance.
     *
     * @return outagedb The singleton object.
     */
    public static function get() {
        if (is_null(self::$singleton)) {
            self::$singleton = new outagedb();
        }
        return self::$singleton;
    }

    /**
     * Private clone method to prevent cloning singleton.
     *
     * @return void
     */
    private function __clone() {
    }

    /**
     * Private unserialize method to prevent unserializing singleton.
     *
     * @return void
     */
    private function __wakeup() {
    }

    /**
     * Private constructor (singleton), use outagedb::get() instead.
     */
    private function __construct() {
    }

    /**
     * Gets all outage entries.
     */
    public function getall() {
        global $DB;

        $outages = [];

        $rs = $DB->get_recordset('auth_outage', null, 'starttime,stoptime,title');
        foreach ($rs as $r) {
            $outages[] = new outage($r);
        }
        $rs->close();

        return $outages;
    }

    public function getbyid($id) {
        global $DB;

        if (!is_int($id)) {
            throw new InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new InvalidArgumentException('$id must be positive.');
        }

        $outage = $DB->get_record('auth_outage', ['id' => $id]);
        if ($outage === false) {
            return null;
        }

        return new outage($outage);
    }

    /**
     * Saves an outage to the database.
     *
     * @param outage $outage Outage to save.
     * @return int Outage ID.
     */
    public function save(outage $outage) {
        global $DB, $USER;

        // Do not change the original object.
        $outage = clone $outage;

        // If new outage, set its creator.
        if ($outage->id === null) {
            $outage->createdby = $USER->id;
        }

        // Update control fields.
        $outage->modifiedby = $USER->id;
        $outage->lastmodified = time();

        // If new, create it and return the id.
        if ($outage->id === null) {
            return $DB->insert_record('auth_outage', $outage, true);
        }

        // Clean up the class (remove creator field), then update it and return its id.
        unset($outage->createdby);
        $DB->update_record('auth_outage', $outage);
        return $outage->id;
    }

    /**
     * Deletes an outage from the database.
     *
     * @param $id outage Outage ID to delete
     * @throws InvalidArgumentException If ID is not valid.
     */
    public function delete($id) {
        global $DB;

        if (!is_int($id)) {
            throw new InvalidArgumentException('$id must be an int.');
        }
        if ($id <= 0) {
            throw new InvalidArgumentException('$id must be positive.');
        }

        $DB->delete_records('auth_outage', ['id' => $id]);
    }
}