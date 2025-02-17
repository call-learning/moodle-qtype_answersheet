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

    /**
     * Set the Table data.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    setData(args) {
        const request = {
            methodname: 'qtype_answersheet_set_data',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Create a new row.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    createRow(args) {
        const request = {
            methodname: 'qtype_answersheet_create_row',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Create a new module.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    createModule(args) {
        const request = {
            methodname: 'qtype_answersheet_create_module',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Delete a module.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    deleteModule(args) {
        const request = {
            methodname: 'qtype_answersheet_delete_module',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Delete a row.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    deleteRow(args) {
        const request = {
            methodname: 'qtype_answersheet_delete_row',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }

    /**
     * Update the sort order.
     * @param {Object} args The arguments.
     * @return {Promise} The promise.
     */
    updateSortOrder(args) {
        const request = {
            methodname: 'qtype_answersheet_update_sort_order',
            args: args
        };

        let promise = Ajax.call([request])[0]
            .fail(Notification.exception);

        return promise;
    }
}

const RepositoryInstance = new Repository();

export default RepositoryInstance;
