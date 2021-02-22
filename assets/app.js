/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// loads the jquery package from node_modules
import $ from 'jquery';
require("bootstrap");
// start the Stimulus application
import './bootstrap';
import rome from '@bevacqua/rome';

window.addEventListener('DOMContentLoaded', (event) => {    
    if (document.getElementById('dayview-date') !== null) {
        rome(document.getElementById('dayview-date'), { 
            'time': false,
            'weekStart': 1,
            'inputFormat': 'DD-MM-YYYY',
        }).on('hide', function () {
            // document.getElementById('dayview-date').closest('form').submit();
        });
    }

    const list = document.querySelectorAll('.date-pricker');
    for (let item of list) {
        rome(item, {'time': false });
    }
});





