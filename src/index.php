<?php
@require 'get_coordinates.php';
$existingParcelNumbers = getParcelCoordinates();

var_Dump($existingParcelNumbers);
exit;
?>

<html lang="cs">
    <head>
        <title>Title of the document</title>
    </head>
    <body style="margin: 20px;">
        <div id="map" style="height: calc(100vh - 40px)"></div>
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>

        <script>
            var map = L.map('map').setView([49.8381, 13.4957], 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            var polygonCoords = [
                [49.8381, 13.4957],
                [49.8382, 13.4958],
                [49.8383, 13.4956],
                // … all points from outer ring (index 0)
            ];

            L.polygon(polygonCoords, {color: 'blue'}).addTo(map)
                .bindPopup("Parcel 506/15");
        </script>
    </body>
</html>
