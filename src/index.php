<?php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';
require 'get_coordinates.php';
?>

<html lang="cs">
    <head>
        <title>ČÚZK - show parcel numbers on the map</title>
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    </head>
    <body style="margin: 20px;">
        <div id="map" style="height: calc(100vh - 40px)"></div>

        <script>
            const parcels = <?php echo json_encode(getParcelsForLeaflet(), JSON_THROW_ON_ERROR); ?>;
            if (parcels.length) {
                const map = L.map('map').setView(parcels[0].coordinates, 17);

                L.tileLayer('https://ags.cuzk.gov.cz/arcgis1/rest/services/ORTOFOTO_WM/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 20,
                    minZoom: 6,
                    attribution: '© <a href="https://geoportal.cuzk.cz/" target="_blank">ČÚZK – Ortofoto</a>',
                    tileSize: 256
                }).addTo(map);  // Přidá se jako base

                L.tileLayer('https://services.cuzk.gov.cz/wmts/local-km-wmts-google/rest/WMTS/{style}/{tileMatrixSet}/{z}/{y}/{x}', {
                    style: 'default',
                    tileMatrixSet: 'KN',
                    format: 'image/png',
                    attribution: '© <a href="https://geoportal.cuzk.cz/" target="_blank">ČÚZK</a> – Katastrální mapa',
                    tileSize: 256,
                }).addTo(map);

                // L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                const bounds = L.latLngBounds();
                parcels.forEach(parcel => {
                    const [lat, lng] = parcel.coordinates;

                    const popupHtml = `
                        <strong>Parcelní číslo:</strong> ${parcel.parcelNumber}<br>
                        <strong>Katastrální území:</strong> ${parcel.cadastralAreaName} [${parcel.cadastralArea}]<br>
                        <strong>Druh pozemku:</strong> ${parcel.landType}
                    `;

                    L.marker([lat, lng])
                        .addTo(map)
                        .bindPopup(popupHtml);

                    bounds.extend(parcel.coordinates);
                });

                map.fitBounds(bounds, {padding: [20, 20]});
            }
        </script>
    </body>
</html>
