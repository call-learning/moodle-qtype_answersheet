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
 * Maximise a document height
 *
 * Inspired from M.util.init_maximised_embed
 * @module     qtype_answersheet/maximise-document.js
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
const getHtmlElementSize = (elid, prop) => {
    const el = document.getElementById(elid);
    // Ensure element exists.
    if (el) {
        let val = el.getStyle(prop);
        if (val === 'auto') {
            val = el.getComputedStyle(prop);
        }
        val = parseInt(val);
        if (isNaN(val)) {
            return 0;
        }
        return val;
    } else {
        return 0;
    }
};

const resizeObjectHeight = (obj) => {
    obj.style.display = 'none';
    var headerheight = getHtmlElementSize('#page-header', 'height');
    var footerheight = getHtmlElementSize('#page-footer', 'height');
    var newheight = parseInt(Y.one('body').get('docHeight')) - footerheight - headerheight - 100;
    if (newheight < 400) {
        newheight = 400;
    }
    obj.style.height =  newheight + 'px';
    obj.style.display = '';
};


export default (documentid) => {
    const obj = document.getElementById(documentid);
    resizeObjectHeight(obj);
    // Fix layout if window resized too.
    window.addEventListener('resize', () => {
        resizeObjectHeight(obj);
    });
};