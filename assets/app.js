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
// import './bootstrap';
import rome from '@bevacqua/rome';
import bsCustomFileInput from 'bs-custom-file-input'

window.addEventListener('DOMContentLoaded', () => {
    const elm = document.getElementById('dayview-date');

    bsCustomFileInput.init()

    if (elm !== null) {
        const startValue = elm.value;
        rome(elm, {
            'time': false,
            'weekStart': 1,
            'inputFormat': 'DD-MM-YYYY',
        }).on('hide', function () {
            if (elm.value !== startValue) {
                elm.closest('form').submit();
            }
        });
    }

    const list = document.querySelectorAll('.date-pricker');
    for (let item of list) {
        rome(item, {
            'time': false,
            'inputFormat': 'DD-MM-YYYY',
        });
    }

    function showFields() {
        $('.periode-selector').hide();
        $('.periode-'+ $("#periode").children("option:selected").val()).show();
    };

    showFields();
    $('#periode').on('change', function() {
        showFields();
    });

    $('[name^="columns"]').on('change', function (event) {
        var show = $(event.target).is(':checked');
        var col = $(event.target).val();
        if (show) {
            $('.main-table .data-' + col).show();
        } else {
            $('.main-table .data-' + col).hide();
        }
    });
});
