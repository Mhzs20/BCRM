import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix for default marker icon
import icon from 'leaflet/dist/images/marker-icon.png';
import iconShadow from 'leaflet/dist/images/marker-shadow.png';

let DefaultIcon = L.icon({
    iconUrl: icon,
    shadowUrl: iconShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

L.Marker.prototype.options.icon = DefaultIcon;


document.addEventListener('DOMContentLoaded', function () {
    const lat = parseFloat(document.querySelector('meta[name="lat"]').getAttribute('content'));
    const lang = parseFloat(document.querySelector('meta[name="lang"]').getAttribute('content'));

    const map = L.map('map').setView([lat, lang], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    L.marker([lat, lang]).addTo(map)
        .bindPopup('موقعیت سالن')
        .openPopup();

    document.getElementById('navigate-btn').addEventListener('click', function() {
        const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lang}`;
        window.open(url, '_blank');
    });

    const appointmentDateStr = document.querySelector('meta[name="appointment-date"]').getAttribute('content').split('T')[0];
    const appointmentTimeStr = document.getElementById('appointment-time-display').textContent.trim();
    const appointmentDateTime = new Date(`${appointmentDateStr}T${appointmentTimeStr}`).getTime();

    const x = setInterval(function() {
        const now = new Date().getTime();
        const distance = appointmentDateTime - now;

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        const countdownElement = document.getElementById("countdown");
        if (countdownElement) {
            if (distance > 0) {
                countdownElement.innerHTML = `
                    <div class="card p-3">
                        <h5>زمان باقی‌مانده تا نوبت</h5>
                        <p style="font-size: 1.2rem; font-weight: bold;">${days} روز و ${hours} ساعت و ${minutes} دقیقه</p>
                    </div>
                `;
            } else {
                clearInterval(x);
                countdownElement.innerHTML = `
                    <div class="card p-3">
                        <h5 class="text-success">زمان نوبت فرا رسیده است.</h5>
                    </div>
                `;
            }
        }
    }, 1000);
});
