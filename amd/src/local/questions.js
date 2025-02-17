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
 * TODO describe module questions
 *
 * @module     qtype_answersheet/local/questions
 * @copyright  2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Questions class
 */
class Questions {

    /**
     * The questionid.
     * @type {Number}
     */
    questionid;

    /**
     * The element.
     * @type {HTMLElement}
     */
    element;

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
        this.setLetterInputValues();
    }

    /**
     * Add event listeners.
     * @return {void}
     */
    addEventListeners() {
        this.element.addEventListener('input', (e) => {
            const input = e.target.closest('[data-region="letterinput"]');
            if (input) {
                this.letterInputAction(input);
            }
            // On backspace, focus the previous input
            if (e.inputType === 'deleteContentBackward') {
                const index = parseInt(input.dataset.index);
                const previousInput =
                    input.closest('[data-region="letterbyletterquestion"]').querySelector(`[data-index="${index - 1}"]`);
                if (previousInput) {
                    previousInput.focus();
                }
            }
        });
    }

    /**
     * Letter input action.
     * @param {HTMLElement} input The input.
     * @return {void}
     */
    letterInputAction(input) {
        const value = input.value;
        const index = parseInt(input.dataset.index);
        const nextInput =
            input.closest('[data-region="letterbyletterquestion"]').querySelector(`[data-index="${parseInt(index) + 1}"]`);
        if (value.length > 1) {
            input.value = value.charAt(0);
            if (nextInput) {
                nextInput.focus();
                nextInput.value = value.charAt(1);
            }
        }
        this.setHiddenInputValue(input);
    }

    /**
     * Set hidden input value.
     * @param {HTMLElement} input The input.
     * @return {void}
     */
    setHiddenInputValue(input) {
        const row = input.closest('[data-region="letterbyletterquestion"]');
        const hiddenInput = row.querySelector('input[data-region="hiddeninput"]');
        // Construct the hidden input value from all letter by letter inputs in the row
        const inputs = row.querySelectorAll('[data-region="letterinput"]');
        let value = '';
        for (let i = 0; i < inputs.length; i++) {
            value += inputs[i].value;
        }
        hiddenInput.value = value;
    }

    /**
     * Set the letter input values.
     * @return {void}
     */
    setLetterInputValues() {
        const rows = this.element.querySelectorAll('[data-region="letterbyletterquestion"]');
        for (let i = 0; i < rows.length; i++) {
            const hiddenInput = rows[i].querySelector('input[data-region="hiddeninput"]');
            const value = hiddenInput.value;
            const inputs = rows[i].querySelectorAll('[data-region="letterinput"]');
            for (let j = 0; j < inputs.length; j++) {
                inputs[j].value = value.charAt(j);
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
    new Questions(element, questionid);
};

export default {
    init: init,
};