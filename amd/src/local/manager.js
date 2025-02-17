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
import {debounce} from 'core/utils';
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
    temprowid = 2;

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
        this.tempfield = document.querySelector('input[name="newquestion"]');
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

        let dragging = null;

        form.addEventListener('dragstart', (e) => {
            if (e.target.tagName === 'TR') {
                dragging = e.target;
                e.target.effectAllowed = 'move';
            }
        });
        form.addEventListener('dragover', (e) => {
            e.preventDefault();
            const target = e.target.closest('tr');
            if (target && target !== dragging && target.parentNode.dataset.region === 'rows') {
                const rect = target.getBoundingClientRect();
                if (e.clientY - rect.top > rect.height / 2) {
                    target.parentNode.insertBefore(dragging, target.nextSibling);
                } else {
                    target.parentNode.insertBefore(dragging, target);
                }
            }
        });
        form.addEventListener("drop", (e) => {
            e.preventDefault(); // Voorkom standaard drop-actie
        });
        form.addEventListener('dragend', (e) => {
            const rowId = dragging.dataset.index;
            const prevRowId = dragging.previousElementSibling ? dragging.previousElementSibling.dataset.index : 0;
            const moduleId = dragging.closest('[data-region="module"]').dataset.id;
            Repository.updateSortOrder(
                {
                    type: 'row',
                    questionid: this.questionid,
                    moduleid: moduleId,
                    id: rowId,
                    previd: prevRowId
                }
            );
            dragging = null;
            e.preventDefault(); // Voorkom standaard drop-actie
        });

        // Listen for the saveconfirm custom event. When run save the table data.
        document.addEventListener('saveconfirm', () => {
            this.setTableData();
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
            if (response.modules.length > 0) {
                const modules = this.parseModules(response.modules);
                State.setValue('modules', modules);
            } else {
                const moduleid = await this.createModule(' ', 0);
                await this.createRow(moduleid, 0, 0);
                this.getTableData();
            }
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Parse the modules, add the correct column properties to each cell.
     * @param {Array} modules The modules.
     * @return {Array} The parsed rows.
     */
    parseModules(modules) {
        modules.forEach(mod => {
            const type = this.TYPES[mod.type];
            mod[type] = true;
            mod.rows.map(row => {
                row.cells = row.cells.map(cell => {
                    const column = mod.columns.find(column => column.column == cell.column);
                    // Clone the column properties to the cell but keep the cell properties.
                    cell = Object.assign({}, cell, column);
                    if (cell.type === 'select') {
                        // Clone the options array to avoid shared references
                        cell.options = cell.options.map(option => {
                            const clonedOption = Object.assign({}, option);
                            if (clonedOption.name == cell.value) {
                                clonedOption.selected = true;
                            }
                            return clonedOption;
                        });
                    }
                    cell.edit = true;
                    return cell;
                });
                return row;
            });
        });
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
                    });
                    return cleanedCell;
                });
                return cleanedRow;
            });
            const cleanedModule = {};
            cleanedModule.id = module.moduleid;
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
        const set = debounce(async() => {
            const saveConfirmButton = document.querySelector('[data-action="saveconfirm"]');
            saveConfirmButton.classList.add('saving');
            const modules = State.getValue('modules');
            const cleanedModules = this.cleanModules(modules);
            if (this.questionid == 0) {
                this.tempfield.value = JSON.stringify(cleanedModules);
            }
            const response = await Repository.setData({questionid: this.questionid, modules: cleanedModules});
            if (!response) {
                Notification.exception('No response from the server');
            }
            setTimeout(() => {
                saveConfirmButton.classList.remove('saving');
            }, 200);
        }, 600);
        set();
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
        if (btn.dataset.action === 'saveconfirm') {
            this.setTableData();
        }
        if (btn.dataset.action === 'moduleremoveoption') {
            this.updateModuleOption(btn, false);
        }
        if (btn.dataset.action === 'moduleaddoption') {
            this.updateModuleOption(btn, true);
        }
    }

    /**
     * Inject a new row after this row.
     * @param {object} btn The button that was clicked.
     */
    async addRow(btn) {
        const modules = State.getValue('modules');

        let rowid = btn.dataset.id;
        const moduleid = btn.closest('[data-region="module"]').dataset.id;
        const module = modules.find(m => m.moduleid == moduleid);
        const rows = module.rows;
        // When called from the link under the table, the rowid is not set.
        if (rowid == -1) {
            rowid = rows[rows.length - 1].id;
        }

        const row = await this.createRow(moduleid, rowid);
        if (!row) {
            return;
        }
        // Inject the row after the clicked row.
        rows.splice(rows.indexOf(rows.find(r => r.id == rowid)) + 1, 0, row);
        State.setValue('modules', modules);
        this.resetRowSortorder();
    }

    /**
     * Create a new row.
     *
     * @param {Number} moduleid The moduleid.
     * @param {Number} prevrowid The previous rowid.
     * @return {Promise} The promise.
     */
    async createRow(moduleid, prevrowid) {
        let rowid = this.temprowid++;
        if (this.questionid != 0) {
            rowid = await Repository.createRow({questionid: this.questionid, moduleid: moduleid, prevrowid: prevrowid});
        }
        return new Promise((resolve) => {
            const row = {};
            row.id = rowid;
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
        const rowid = btn.closest('[data-row]').dataset.index;
        const moduleid = btn.closest('[data-region="module"]').dataset.id;
        const module = modules.find(m => m.moduleid == moduleid);
        if (this.questionid == 0) {
            const index = module.rows.findIndex(r => r.id == rowid);
            module.rows.splice(index, 1);
            this.resetRowSortorder();
            State.setValue('modules', modules);
            return new Promise((resolve) => {
                resolve(rowid);
            });
        }
        const response = await Repository.deleteRow({questionid: this.questionid, rowid: rowid});
        return new Promise((resolve) => {
            if (response) {
                const rows = module.rows;
                const index = Array.from(btn.closest('[data-region="rows"]').children).indexOf(btn.closest('[data-row]'));
                rows.splice(index, 1);
                this.resetRowSortorder();
                State.setValue('modules', modules);
            }
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
        const index = row.dataset.index;
        const modules = State.getValue('modules');
        modules.forEach(module => {
            // Find the correct cell in the row.
            const rowIndex = module.rows.findIndex(r => r.id == index);
            if (rowIndex === -1) {
                return;
            }
            const cellIndex = module.rows[rowIndex].cells.findIndex(c => c.columnid == columnid);
            module.rows[rowIndex].cells[cellIndex].value = value;
            if (module.rows[rowIndex].cells[cellIndex].type === 'select') {
                module.rows[rowIndex].cells[cellIndex].options.forEach(option => {
                    option.selected = option.name === value;
                });
            }
        });
        this.setTableData();
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
            if (moduleObject.moduleid == moduleid) {
                moduleObject.modulename = name;
                moduleObject.type = parseInt(type);
                moduleObject.numoptions = parseInt(numoptions);
            }
        });
        this.updateRangeIndicator(moduleElement);
        this.setTableData();
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
        const stringname = 'indicator:' + this.TYPES[type];
        // Get the x-th letter of the alphabet.
        const stringtemplate = {
            'options': numoptions,
            'lastletter': String.fromCharCode(65 + parseInt(numoptions) - 1),
        };
        indicator.textContent = await getString(stringname, 'qtype_answersheet', stringtemplate);
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
        const module = modules.find(m => m.moduleid == moduleid);
        const response = await Repository.deleteModule({questionid: this.questionid, moduleid: moduleid});
        return new Promise((resolve) => {
            if (response) {
                const index = modules.indexOf(module);
                modules.splice(index, 1);
                State.setValue('modules', modules);
            }
            resolve(moduleid);
        });
    }

    /**
     * Create a new module.
     * @param {String} name The name.
     * @param {Number} index The index.
     * @param {Number} type The type.
     * @param {Number} numoptions The number of options.
     * @return {Promise} The promise.
     */
    async createModule(name, index, type = 1, numoptions = 4) {
        const id = await Repository.createModule(
            {
                name: name,
                questionid: this.questionid,
                sortorder: index,
                type: type,
                numoptions: numoptions
            });
        return new Promise((resolve) => {
            resolve(id);
        });
    }

    /**
     * Add a new module.
     * @return {void}
     */
    async addModule() {
        const modules = State.getValue('modules');
        const index = modules.length;
        const numoptions = 4;
        let moduleid = modules.length + 1;
        if (this.questionid != 0) {
            moduleid = await this.createModule(' ', index, 1, numoptions);
        }
        const row = await this.createRow(moduleid, 0);

        const module = {
            moduleid: moduleid,
            modulesortorder: index + 1,
            modulename: ' ',
            type: 1,
            numoptions: numoptions,
            indicator: 'A - ' + String.fromCharCode(65 + numoptions - 1),
            rows: [row],
        };
        module[this.TYPES['1']] = true;
        modules.push(module);
        State.setValue('modules', modules);
    }

    /**
     * Get the row from the state.
     * @param {int} rowid The rowid.
     */
    getRow(rowid) {
        const modules = State.getValue('modules');
        // Combine all rows in one array.
        const rows = modules.reduce((acc, module) => {
            return acc.concat(module.rows);
        }, []);
        const row = rows.find(r => r.id == rowid);
        return row;
    }

    /**
     * Reset the row sortorder values.
     * @return {void}
     */
    resetRowSortorder() {
        const modules = State.getValue('modules');
        modules.forEach(module => {
            module.rows.forEach((row, index) => {
                row.sortorder = index;
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