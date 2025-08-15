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
 * Gateway to the webservices.
 *
 * @module     qtype_answersheet/local/repository
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Competvet repository class.
 */
class Repository {

    /**
     * Get JSON data
     * @param {Object} args The data to get.
     * @return {Promise} The promise.
     */
    getColumns(args) {
        const request = {
            methodname: 'qtype_answersheet_get_columns',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Get the Table data.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    getData(args) {
        const request = {
            methodname: 'qtype_answersheet_get_data',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }
}

const RepositoryInstance = new Repository();

export default RepositoryInstance;
