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
 * A unit test suite for the collector class
 *
 * @package   local_pluginsfetcher
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_pluginsfetcher;


/**
 * A unit test suite for the collector class
 */
final class collector_test extends \advanced_testcase {

    /**
     * Basic unit test for the get_plugin_stats method
     *
     * @covers \local_pluginsfetcher\collector::get_plugin_stats
     *
     * @return void
     */
    public function test_get_plugin_stats(): void {
        // Determine the expected amount of plugins.
        $plugman = \core_plugin_manager::instance();
        $plugins = $plugman->get_plugins();

        $expectedpluginscount = 0;
        foreach ($plugins as $pluginsoftype) {
            $expectedpluginscount += count($pluginsoftype);
        }

        // Call the collector.
        $pluginstats = collector::get_plugin_stats();

        // Compare the results.
        $this->assertArrayHasKey('stats', $pluginstats);
        $this->assertIsNumeric($pluginstats['stats']['total'], 'Total plugin count is not numeric.');
        $this->assertIsNumeric($pluginstats['stats']['standard'], 'Standard plugin count is not numeric.');
        $this->assertIsNumeric($pluginstats['stats']['contrib'], 'Contrib plugin count is not numeric.');
        $this->assertSame($expectedpluginscount, $pluginstats['stats']['total'], 'Total plugin count does not match.');

        $this->assertArrayHasKey('plugins', $pluginstats);
        $this->assertCount($expectedpluginscount, $pluginstats['plugins'], 'Not every plugin seems to have its own record.');

        $testplugin = array_shift($pluginstats['plugins']);
        $expectedkeys = ['type', 'name', 'displayname', 'version', 'release', 'requires', 'supported', 'isstandard', 'status'];
        foreach ($expectedkeys as $key) {
            $this->assertArrayHasKey($key, $testplugin, "Key '$key' is missing in the plugin info.");
        }
    }

    /**
     * Tests filtering for 3rd-party plugins
     *
     * @covers \local_pluginsfetcher\collector::get_plugin_stats
     *
     * @return void
     */
    public function test_get_plugin_stats_contrib_only(): void {
        // Call the collector with contribonly set to true.
        $pluginstats = collector::get_plugin_stats(null, true);

        // Check that all plugins are 3rd-party.
        foreach ($pluginstats['plugins'] as $plugin) {
            $this->assertFalse($plugin['isstandard'], 'Found a standard plugin in contrib-only results.');
        }
    }

    /**
     * Tests filtering plugins by type
     *
     * @covers \local_pluginsfetcher\collector::get_plugin_stats
     * @dataProvider get_plugin_stats_by_type_data_provider
     *
     * @param string $plugintype The type of plugin to filter by (e.g., 'mod', 'block', etc.)
     * @return void
     */
    public function test_get_plugin_stats_by_type(string $plugintype): void {
        // Call the collector with a specific type.
        $pluginstats = collector::get_plugin_stats($plugintype);

        // Check that all plugins are of the specified type.
        foreach ($pluginstats['plugins'] as $plugin) {
            $this->assertSame($plugintype, $plugin['type'], 'Found a plugin of a different type in mod results.');
        }
    }

    /**
     * Data provider for test_get_plugin_stats_by_type
     *
     * @return array[] List of plugin types to test
     */
    public static function get_plugin_stats_by_type_data_provider(): array {
        return [
            'mod' => ['mod'],
            'block' => ['block'],
            'local' => ['local'],
            'auth' => ['auth'],
            'nonexistingtype' => ['nonexistingtype'],
        ];
    }

    /**
     * Basic unit test for the get_software_stats method
     *
     * @covers \local_pluginsfetcher\collector::get_software_stats
     *
     * @return void
     */
    public function test_get_software_stats(): void {
        global $CFG;

        // Call the collector.
        $softwarestats = collector::get_software_stats();

        // Compare the results.
        $this->assertSame($CFG->version, $softwarestats['moodle']['version'], 'Moodle version does not match.');
        $this->assertSame($CFG->release, $softwarestats['moodle']['release'], 'Moodle release does not match.');
        $this->assertSame($CFG->branch, $softwarestats['moodle']['branch'], 'Moodle branch does not match.');

        $this->assertSame(phpversion(), $softwarestats['php']['version'], 'PHP version does not match.');
        $this->assertSame($CFG->dbtype, $softwarestats['db']['type'], 'Database type does not match.');
        $this->assertSame(PHP_OS, $softwarestats['os']['name'], 'OS name does not match.');
    }

}
