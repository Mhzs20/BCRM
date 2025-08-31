import './bootstrap';

import Alpine from 'alpinejs';
import 'remixicon/fonts/remixicon.css';
import 'leaflet/dist/leaflet.css';
import 'persian-datepicker/dist/css/persian-datepicker.min.css';

import L from 'leaflet';
import 'persian-date/dist/persian-date.min.js';
import 'persian-datepicker/dist/js/persian-datepicker.min.js';

window.Alpine = Alpine;
window.L = L;

document.addEventListener('alpine:init', () => {
    // Alpine.js is ready
});

Alpine.start();

import Chart from 'chart.js/auto';

window.Chart = Chart;
