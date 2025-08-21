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
 * TODO describe module manager
 *
 * @module     qtype_answersheet/local/manager
 * @copyright  2024 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import State from 'qtype_answersheet/local/state';
import Repository from 'qtype_answersheet/local/repository';
import Notification from 'core/notification';
import {get_string as getString} from 'core/str';
import './components/table';

/**
 * Manager class.
 * @class
 */
class Manager {

    /**
     * Row number.
     */
    rowNumber = 0;

    /**
     * The questionid.
     * @type {Number}
     */
    questionid;

    /**
     * The temp row id.
     * @type {Number}
     */
    temprowid = 1000;

    /**
     * The element.
     * @type {HTMLElement}
     */
    element;

    /**
     * The table name.
     */
    table = 'qtype_answersheet';

    /**
     * The table columns.
     * @type {Array}
     */
    columns = [];

    /**
     * Types definition
     */
    TYPES = {
        '1': 'radiochecked',
        '2': 'letterbyletter',
        '3': 'freetext'
    };

    /**
     * Constructor.
     * @param {HTMLElement} element The element.
     * @param {String} questionid The questionid.
     * @return {void}
     */
    constructor(element, questionid) {
        this.element = element;
        this.questionid = parseInt(questionid);
        this.addEventListeners();
        this.getDatagrid();
        this.tempfield = document.querySelector('input[name="jsonquestions"]');
    }

    /**
     * Add event listeners.
     * @return {void}
     */
    addEventListeners() {
        const form = document.querySelector('[data-region="app"]');
        form.addEventListener('click', (e) => {
            let btn = e.target.closest('[data-action]');
            if (btn) {
                e.preventDefault();
                this.actions(btn);
            }
        });
        // Listen to all changes in the table.
        form.addEventListener('change', (e) => {
            const input = e.target.closest('[data-input]');
            if (input) {
                this.change(input);
            }
            const modulename = e.target.closest('[data-region="modulename"]');
            if (modulename) {
                this.changeModule(modulename);
            }
            const moduletype = e.target.closest('[data-region="moduletype"]');
            if (moduletype) {
                this.changeModule(moduletype);
            }
            this.setTableData();
        });
        // Listen to the arrow down and up keys to navigate to the next or previous row.
        form.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                this.navigate(e);
                e.preventDefault();
            }
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
        });
    }

    async getDatagrid() {
        await this.getTableData();
        await this.getTableConfig();
    }

    /**
     * Get the table configuration.
     * @return {Promise} The promise.
     */
    async getTableConfig() {
        const response = await Repository.getColumns({table: this.table});
        await State.setValue('columns', response.columns);
    }

    /**
     * Get the table data.
     * @return {void}
     */
    async getTableData() {
        try {
            const response = await Repository.getData({questionid: this.questionid});
            // Validate the response, the response.date should be a string that can be parsed to a JSON object.
            const modules = await this.parseModules(response.modules);
            State.setValue('modules', modules);
            this.setTableData();
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Parse the modules, add the correct column properties to each cell.
     * @param {Array} modules The modules.
     * @return {Array} The parsed rows.
     */
    async parseModules(modules) {
        for (const mod of modules) {
            const type = this.TYPES[mod.type];
            mod[type] = true;
            mod.indicator = await this.getIndicator(mod.numoptions, mod.type);
            mod.rows.forEach((row) => {
                let selectedValue = '';
                row.cells.forEach(cell => {
                    if (cell.column === 'answer') {
                        selectedValue = cell.value;
                    }
                });
                row.cells = row.cells.map(cell => {
                    const column = mod.columns.find(column => column.column === cell.column);
                    // Clone the column properties to the cell but keep the cell properties.
                    cell = Object.assign({}, cell, column);
                    if (cell.type === 'select') {
                        // Clone the options array to avoid shared references
                        cell.options = cell.options.map(option => {
                            const clonedOption = Object.assign({}, option);
                            if (clonedOption.name === selectedValue) {
                                clonedOption.selected = true;
                            }
                            return clonedOption;
                        });
                    }
                    cell.edit = true;
                    return cell;
                });
            });
        }
        // To calculate the answerid index, so we can make up an id related to the row index.
        // Used in behat tests to find the correct answer input.
        modules = await this.recomputeIds(modules);
        return modules;
    }
    /**
     * Get the row object that can be accepted by the webservice.
     * @return {Array} The keys.
     */
    getRowObject() {
        return {
            'rows': {
                'id': 'id',
                'sortorder': 'sortorder',
                'cells': {
                    'type': 'type',
                    'column': 'column',
                    'value': 'value',
                },
            },
        };
    }

    /**
     * Check the cell value. It can not exceed the cell length.
     * @param {object} cell The cell.
     * @return {void}
     */
    checkCellValue(cell) {
        if (cell.value === null) {
            return;
        }
        if (cell.type === 'text' && cell.value.length > cell.length) {
            cell.value = cell.value.substring(0, cell.length);
        }
    }


    /**
     * Clean the Modules array.
     * @param {Array} modules The modules.
     * @return {Array} The cleaned modules.
     */
    cleanModules(modules) {
        const cleanedModules = [];
        modules.forEach(module => {
            const rows = module.rows;
            const rowObject = this.getRowObject();
            const cleanedRows = rows.map(row => {
                const cleanedRow = {};
                Object.keys(rowObject.rows).forEach(key => {
                    cleanedRow[key] = row[key];
                });
                // Clean the cells.
                cleanedRow.cells = row.cells.map(cell => {
                    const cleanedCell = {};
                    this.checkCellValue(cell);
                    Object.keys(rowObject.rows.cells).forEach(key => {
                        cleanedCell[key] = cell[key];
                        if (cell.column === 'options' && key === 'value') {
                            cleanedCell[key] = cell['options']?.map(option => option.name);
                        }
                    });
                    return cleanedCell;
                });
                return cleanedRow;
            });
            const cleanedModule = {};
            cleanedModule.sortorder = module.modulesortorder;
            cleanedModule.name = module.modulename;
            cleanedModule.type = module.type;
            cleanedModule.numoptions = module.numoptions;
            cleanedModule.rows = cleanedRows;
            cleanedModules.push(cleanedModule);
        });
        return cleanedModules;
    }

    /**
     * Set the table data.
     * @return {void}
     */
    async setTableData() {
        const modules = State.getValue('modules');
        const cleanedModules = this.cleanModules(modules);
        this.tempfield.value = JSON.stringify(cleanedModules);
    }

    /**
     * Actions.
     * @param {object} btn The button that was clicked.
     */
    actions(btn) {
        if (btn.dataset.action === 'addrow') {
            this.addRow(btn);
        }
        if (btn.dataset.action === 'deleterow') {
            this.deleteRow(btn);
        }
        if (btn.dataset.action === 'addmodule') {
            this.addModule(btn);
        }
        if (btn.dataset.action === 'deletemodule') {
            this.deleteModule(btn);
        }
        if (btn.dataset.action === 'moduleremoveoption') {
            this.updateModuleOption(btn, false);
        }
        if (btn.dataset.action === 'moduleaddoption') {
            this.updateModuleOption(btn, true);
        }
        this.setTableData();
    }

    /**
     * Inject a new row after this row.
     * @param {object} btn The button that was clicked.
     */
    async addRow(btn) {
        const modules = State.getValue('modules');

        const moduleid = btn.closest('[data-region="module"]').dataset.id;
        const module = modules.find(m => m.id == moduleid);
        const rows = module.rows;

        const row = await this.createRow(rows.length + 1);
        if (!row) {
            return;
        }
        // Inject the row after the clicked row.
        rows.push(row);
        // Compute the id for each cells.
        State.setValue('modules', modules);
        this.resetRowSortorder();
    }

    /**
     * Recompute the ids for the modules and cells.
     * @param {Array} modules The modules.
     * @return {Array} Modules with computed ids (id_answer_0_0, id_answer_0_1, etc.).
     */
    async recomputeIds(modules) {
        // To calculate the answerid index, so we can make up an id related to the row index.
        // Used in behat tests to find the correct answer input.
        for (const [moduleIndex, mod] of modules.entries()) {
            mod.modid = `id_module_${moduleIndex}`; // Used mostly for behat tests.
            mod.rows.forEach((row, rowIndex) => {
                row.rowid = `id_row_${moduleIndex}_${rowIndex}`;
                row.cells = row.cells.map(cell => {
                    cell.cellid = `id_${cell.column}_${moduleIndex}_${rowIndex}`;
                    return cell;
                });
            });
        }
        return modules;
    }
    /**
     * Create a new row.
     *
     * @param {int} sortorder The sortorder.
     * @return {Promise} The promise.
     */
    async createRow(sortorder) {
        let rowid = this.createTempId(this.temprowid++);
        return new Promise((resolve) => {
            const row = {};
            row.id = rowid;
            row.numoptions = 4;
            row.sortorder = sortorder;
            row.type = 1;
            row.radiochecked = true;
            const columns = State.getValue('columns');
            if (columns === undefined) {
                resolve();
                return;
            }
            // The copy the columns to the row and call them cells.
            row.cells = columns.map(column => structuredClone(column));
            // Set the correct types for the cells.
            row.cells.forEach(cell => {
                cell.edit = true;
                cell.value = '';
                cell[cell.type] = true;
            });
            resolve(row);
        });
    }

    /**
     * Delete a row.
     * @param {Object} btn The button that was clicked.
     * @return {Promise} The promise.
     */
    async deleteRow(btn) {
        const modules = State.getValue('modules');
        const rowid = btn.closest('[data-row]').dataset.id;
        const moduleid = btn.closest('[data-region="module"]').dataset.id;
        const module = modules.find(m => m.id == moduleid);
        if (module.rows.length > 1) {
            const index = module.rows.findIndex(r => r.id == rowid);
            module.rows.splice(index, 1);
            this.resetRowSortorder();
            State.setValue('modules', modules);
        }
        return new Promise((resolve) => {
            resolve(rowid);
        });
    }

    /**
     * Change.
     * @param {object} input The input that was changed.
     */
    change(input) {
        const row = input.closest('[data-row]');
        const cell = input.closest('[data-cell]');
        const value = input.value;
        const columnid = cell.dataset.columnid;
        const rowid = row.dataset.id;
        const modules = State.getValue('modules');
        modules.forEach(module => {
            // Find the correct cell in the row.
            const rowIndex = module.rows.findIndex(r => r.id == rowid);
            if (rowIndex === -1) {
                return;
            }
            const cellIndexOption = module.rows[rowIndex].cells.findIndex(c => c.columnid == columnid);
            module.rows[rowIndex].cells[cellIndexOption].value = value;
            if (module.rows[rowIndex].cells[cellIndexOption].type === 'select') {
                module.rows[rowIndex].cells[cellIndexOption].options.forEach(option => {
                    option.selected = option.name === value;
                });
            }
            const cellIndexAnswer = module.rows[rowIndex].cells.findIndex(c => c.column == 'answer');
            module.rows[rowIndex].cells[cellIndexAnswer].value = value;
        });
    }

    /**
     * Change the module name.
     * @param {object} element The element that was changed.
     * @return {void}
     */
    changeModule(element) {
        const moduleElement = element.closest('[data-region="module"]');
        const moduleid = moduleElement.dataset.id;
        const name = moduleElement.querySelector('[data-region="modulename"]').value;
        const type = moduleElement.querySelector('[data-region="moduletype"]').value;
        const numoptions = moduleElement.querySelector('[data-region="numoptions"]').value;
        Object.values(this.TYPES).forEach(type => {
            moduleElement.classList.remove(type);
        });
        moduleElement.classList.add(this.TYPES[type]);
        const modules = State.getValue('modules');
        modules.forEach(moduleObject => {
            if (moduleObject.id == moduleid) {
                moduleObject.modulename = name;
                moduleObject.type = parseInt(type);
                moduleObject.class = this.TYPES[type];
                moduleObject.numoptions = parseInt(numoptions);
                Object.values(this.TYPES).forEach(type => {
                    moduleObject[type] = false;
                });
                moduleObject[this.TYPES[type]] = true;
            }
        });
        this.updateRangeIndicator(moduleElement);
    }

    /**
     * Update the module option. Update the value of the numoptions field.
     * @param {object} btn The button that was clicked.
     * @param {Boolean} add Add or remove an option.
     * @return {void}
     */
    updateModuleOption(btn, add) {
        const module = btn.closest('[data-region="module"]');
        const numoptions = module.querySelector('[data-region="numoptions"]');
        const value = parseInt(numoptions.value);
        if (add) {
            numoptions.value = value + 1;
        } else {
            numoptions.value = value - 1;
        }
        this.changeModule(numoptions);
    }

    /**
     * Update the range indicator.
     * @param {HTMLElement} moduleElement The module.
     */
    async updateRangeIndicator(moduleElement) {
        const type = moduleElement.querySelector('[data-region="moduletype"]').value;
        const numoptions = moduleElement.querySelector('[data-region="numoptions"]').value;
        const indicator = moduleElement.querySelector('[data-region="indicator"]');
        indicator.textContent = await this.getIndicator(numoptions, type);
    }

    /**
     * Get the indicator string
     * @param {int} numoptions The number of options.
     * @param {int} type The type.
     * @return {string} The indicator string.
     */
    async getIndicator(numoptions, type) {
        const stringname = 'indicator:' + this.TYPES[type];
        const stringtemplate = {
            'options': numoptions,
            'lastletter': String.fromCharCode(65 + parseInt(numoptions) - 1),
        };
        return await getString(stringname, 'qtype_answersheet', stringtemplate);
    }

    /**
     * Delete a module.
     * @param {object} btn The button that was clicked.
     * @return {Promise} The promise.
     * @return {void}
     */
    async deleteModule(btn) {
        const modules = State.getValue('modules');
        const moduleid = btn.closest('[data-region="module"]').dataset.id;
        const module = modules.find(m => m.id == moduleid);
        return new Promise((resolve) => {
            const index = modules.indexOf(module);
            modules.splice(index, 1);
            State.setValue('modules', modules);
            resolve(moduleid);
        });
    }

    /**
     * Add a new module.
     * @return {int} The moduleid.
     */
    async addModule() {
        const modules = State.getValue('modules');
        const numoptions = 4;

        const module = {
            id: this.createTempId(modules.length + 1),
            modulesortorder: modules.length + 1,
            modulename: ' ',
            type: 1,
            numoptions: numoptions,
            indicator: this.getIndicator(numoptions, 1),
            rows: [await this.createRow(1)],
        };
        module[this.TYPES['1']] = true;
        modules.push(module);
        await State.setValue('modules', modules);
    }

    /**
     * Create a temporary ID for a row.
     * This is used to identify rows that are not yet saved.
     * @param {number} numericId
     * @return {string}
     */
    createTempId(numericId) {
        return `tmp-${numericId}`;
    }

    /**
     * Reset the row sortorder values.
     * @return {void}
     */
    resetRowSortorder() {
        const modules = State.getValue('modules');
        modules.forEach(module => {
            module.rows.forEach((row, index) => {
                row.sortorder = index + 1;
            });
        });
        State.setValue('modules', modules);
    }

    /**
     * Navigate to the next or previous row and left or right column.
     * @param {Event} e The event.
     * @return {void}
     */
    navigate(e) {
        const currentIndex = e.target.closest('[data-row]').dataset.index;
        const currentColumn = e.target.closest('[data-cell]').dataset.columnid;
        const allRows = document.querySelectorAll('[data-row]');
        for (let i = 0; i < allRows.length; i++) {
            if (allRows[i].dataset.index == currentIndex) {
                if (e.key === 'ArrowDown' && i < allRows.length - 1) {
                    const nextInput = allRows[i + 1].querySelector(`[data-columnid="${currentColumn}"] input`);
                    if (nextInput) {
                        nextInput.focus();
                    }
                }
                if (e.key === 'ArrowUp' && i > 0) {
                    const previousInput = allRows[i - 1].querySelector(`[data-columnid="${currentColumn}"] input`);
                    if (previousInput) {
                        previousInput.focus();
                    }
                }
            }
        }
        // This part is not working yet, it might not be accessible.
        if (e.key === 'ArrowRight') {
            const nextColumn = e.target.closest('[data-cell]').nextElementSibling;
            if (nextColumn) {
                nextColumn.focus();
            }
        }
        if (e.key === 'ArrowLeft') {
            const previousColumn = e.target.closest('[data-cell]').previousElementSibling;
            if (previousColumn) {
                previousColumn.focus();
            }
        }
    }

}

/*
 * Initialise
 * @param {HTMLElement} element The element.
 * @param {String} questionid The questionid.
 */
const init = (element, questionid) => {
    new Manager(element, questionid);
};

export default {
    init: init,
};