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
 * A reactive state class that stores the data for the competvet module.
 *
 * @module     qtype_answersheet/local/state
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A simple state class that stores the data for the competvet module.
 * Classes can subscribe to this class to get updates.
 */
class State {
    /**
     * Constructor.
     */
    constructor() {
        this.data = {};
        this.subscribers = [];
    }

    /**
     * Set a single value.
     * @param {String} key The key.
     * @param {String} value The value.
     * @return {Promise} The promise.
     */
    async setValue(key, value) {
        return new Promise((resolve) => {
            this.data[key] = value;
            this.notifySubscriber(key);
            this.debug();
            resolve();
        });
    }

    /**
     * Get a single value.
     * @param {String} key The key.
     * @return {String} The value.
     */
    getValue(key) {
        return this.data[key];
    }

    /**
     * Get the data.
     * @return {Object} The data.
     */
    getData() {
        return this.data;
    }

    /**
     * Subscribe to the state.
     * @param {String} key The key.
     * @param {Function} callback The callback.
     */
    subscribe(key, callback) {
        if (typeof key !== 'string') {
            throw new Error('The key must be a string');
        }
        if (typeof callback !== 'function') {
            throw new Error('The callback must be a function');
        }

        // Check if the key is already subscribed, with the same callback.
        const exists = this.subscribers.find(subscriber => subscriber.key === key && subscriber.callback === callback);
        if (exists) {
            window.console.log('The key is already subscribed');
            return;
        }
        this.subscribers.push({key, callback});
    }

    /**
     * Unsubscribe from the state.
     * @param {Function} callback The callback.
     */
    unsubscribe(callback) {
        this.subscribers = this.subscribers.filter(subscriber => subscriber.callback !== callback);
    }

    /**
     * Notify the subscribers, but only if the data key exists or has changed.
     */
    notifySubscribers() {
        this.subscribers.forEach(subscriber => {
            if (this.data[subscriber.key] !== undefined) {
                subscriber.callback(this.data);
            }
        });
    }

    /**
     * Notify a single subscriber.
     * @param {String} key The key.
     */
    notifySubscriber(key) {
        const subscriber = this.subscribers.find(subscriber => subscriber.key === key);
        if (subscriber) {
            subscriber.callback(this.data);
        } else {
            window.console.log(`The key ${key} is not subscribed`);
        }
    }

    /**
     * Debugging function.
     */
    debug() {
        const debugRegion = document.getElementById('debug');
        if (debugRegion) {
            debugRegion.innerHTML = JSON.stringify(this.data, null, 2);
        }
    }
}

const state = new State();
export default state;
