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
 * TODO describe module table
 *
 * @module     qtype_answersheet/local/components/table
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import State from 'qtype_answersheet/local/state';
import Templates from 'core/templates';

const app = document.querySelector('[data-region="app"]');

/**
 * Define the user navigation.
 * @param {String} type The type.
 */
const stateTemplate = (type) => {
    const region = app.querySelector(`[data-region="${type}"]`);
    const template = `qtype_answersheet/table/${type}`;
    const tableColumns = async(context) => {
        if (context[type] === undefined) {
            return;
        }
        context[type] = State.getValue(type);
        context.editor = true;
        region.innerHTML = await Templates.render(template, context);
    };
    State.subscribe(type, tableColumns);
};

stateTemplate('modules');