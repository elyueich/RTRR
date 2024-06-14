<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Rainfall</title>
    <?php
    // Menghitung waktu hingga refresh berikutnya
    $now = time();
    $nextRefresh = ceil($now / 600) * 600;
    $timeToNextRefresh = $nextRefresh - $now;
    ?>

    <!-- Leaflet CSS Library -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css">

    <!-- Legend CSS -->
    <link rel="stylesheet" href="assets/plugins/Leaflet.Legend-master/src/leaflet.legend.css">

    <!-- Plugin Geolocation -->
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@v0.76.0/dist/L.Control.Locate.min.css" /> -->

    <style>
        html,
        body,
        #map {
            height: 100%;
            width: 100%;
            margin: 0px;
            z-index: 0;
        }

        #loading-circle {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 80px;
            height: 80px;
            z-index: 1;
        }

        .circle {
            fill: none;
            stroke: #4caf50;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-dasharray: 240;
            stroke-dashoffset: 0;
            animation: dash 2s ease-in-out infinite, rotate 2s linear infinite;
            transform-origin: center;
        }

        @keyframes dash {
            0% {
                stroke-dashoffset: 240;
            }

            50% {
                stroke-dashoffset: 20;
            }

            100% {
                stroke-dashoffset: 240;
            }
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }


        @keyframes dash {
            0% {
                stroke-dashoffset: 240;
            }

            50% {
                stroke-dashoffset: 20;
            }

            100% {
                stroke-dashoffset: 240;
            }
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Leaflet JavaScript Library -->
    <script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"></script>

    <!-- Spatial Data Library -->
    <script src="assets/plugins/leaflet-ajax/leaflet.ajax.js"></script>
    <script src="assets/plugins/leaflet-ajax/leaflet.ajax.min.js"></script>

    <!-- Legend JavaScript -->
    <script src="assets/plugins/Leaflet.Legend-master/src/leaflet.legend.js"></script>

    <!-- geolocation -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@v0.76.0/dist/L.Control.Locate.min.js" charset="utf-8"></script> -->
    <script src="assets/plugins/leaflet-geolet-main/geolet.js"></script><!-- include geolet.js after leaflet.js -->
</head>

<body>
    <div id="loading-circle">
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <circle class="circle" cx="50" cy="50" r="40"></circle>
        </svg>
    </div>
    <div id="map"></div>

    <script language="javascript">
        // Mengatur waktu hingga refresh berikutnya dalam milidetik
        var timeToNextRefresh = <?php echo $timeToNextRefresh * 1000; ?>;

        // Menjadwalkan refresh halaman
        setTimeout(function() {
            location.reload();
        }, timeToNextRefresh);

        function showLoadingBar() {
            document.getElementById('loading-circle').style.display = 'block';
        }

        function hideLoadingBar() {
            document.getElementById('loading-circle').style.display = 'none';
        }

        var map = L.map('map').setView([-3.945103275747339, 119.98554997606333], 8);

        var osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
        });

        var baseMaps = {
            "OpenStreetMap": osm,
            "Esri World Imagery": imagery
        };

        L.control.layers(baseMaps, {}, {
            collapsed: true
        }).addTo(map);

        function style(feature) {
            var rr = feature.properties.rr;
            var fillColor;

            if (rr > 20) {
                fillColor = '#00450C';
            } else if (rr > 10) {
                fillColor = '#369134';
            } else if (rr > 5) {
                fillColor = '#E0FD66';
            } else if (rr > 0) {
                fillColor = '#EFA800';
            } else if (rr == 0) {
                fillColor = '#E4080A';
            } else if (rr = null) {
                fillColor = '#CECECE';
            } else if (rr == "Belum ada data hari ini") {
                fillColor = '#59086d';
            } else {
                fillColor = '#072268';
            }

            return {
                fillColor: fillColor,
                weight: 2,
                opacity: 1,
                color: 'white',
                dashArray: '3',
                fillOpacity: 0.7
            };
        }

        var admin = {
            "color": "#000000",
            "weight": 2,
            "fillOpacity": 0.7,
            "opacity": 0.45
        };

        function fetchData() {
            showLoadingBar();

            var stat = new L.GeoJSON.AJAX(["polygonrr.php"], {
                onEachFeature: function(feature, layer) {
                    var popupText = "Kecamatan: " + feature.properties.WADMKC;
                    if (feature.properties.WADMKK) {
                        popupText += "<br/>Kabupaten: " + feature.properties.WADMKK +
                            "<br>ID Stasiun: " + feature.properties.id_station; +
                        "<br>Tipe Stasiun: " + feature.properties.tipe_stat +
                            "<br>Nama Stasiun: " + feature.properties.name_stat +
                            "<br>tanggal: " + feature.properties.date; +
                        "<br>rr: " + feature.properties.rr

                    }
                    layer.bindPopup(popupText);
                },
                style: style
            });

            stat.on('data:loaded', function() {
                hideLoadingBar();
            });

            stat.addTo(map);
        }

        fetchData();

        var admin = new L.GeoJSON.AJAX(["data/spasial/kec_sulsel.geojson"], {
            onEachFeature: function(feature, layer) {
                var popupText = "Kecamatan: " + feature.properties.WADMKC;
                if (feature.properties.WADMKK) {
                    popupText += "<br/>Kabupaten: " + feature.properties.WADMKK;
                }
                layer.bindPopup(popupText);
            },
            style: admin
        }).addTo(map);

        const legend = L.control.Legend({
            position: "topright",
            collapsed: true,
            symbolWidth: 24,
            opacity: 1,
            column: 2,
            legends: [{
                    label: "0",
                    type: "polygon",
                    sides: 4,
                    color: "#E4080A",
                    fillColor: "#E4080A",
                    weight: 2
                }, {
                    label: "ringan",
                    type: "polygon",
                    sides: 4,
                    color: "#EFA800",
                    fillColor: "#EFA800",
                    weight: 2
                },
                {
                    label: "sedang",
                    type: "polygon",
                    sides: 4,
                    color: "#E0FD66",
                    fillColor: "#E0FD66",
                    weight: 2
                },
                {
                    label: "lebat",
                    type: "polygon",
                    sides: 4,
                    color: "#369134",
                    fillColor: "#369134",
                    weight: 2
                },
                {
                    label: "sangat lebat",
                    type: "polygon",
                    sides: 4,
                    color: "#00450C",
                    fillColor: "#00450C",
                    weight: 2
                },
                {
                    label: "null",
                    type: "polygon",
                    sides: 4,
                    color: "#CECECE",
                    fillColor: "#CECECE",
                    weight: 2
                },
                {
                    label: "Belum ada data hari ini",
                    type: "polygon",
                    sides: 4,
                    color: "#59086d",
                    fillColor: "#59086d",
                    weight: 2
                },
                {
                    label: "Belum ada data dalam 1 jam terakhir",
                    type: "polygon",
                    sides: 4,
                    color: "#072268",
                    fillColor: "#072268",
                    weight: 2
                },
                {
                    label: "tidak ada stasiun",
                    type: "polygon",
                    sides: 4,
                    color: "#000000",
                    fillColor: "#000000",
                    weight: 2
                }
            ]
        }).addTo(map);

        /*buat fitur geolocation dengan memanfaatkan plugin geolocation */
        // L.control.locate({
        //     position: "topleft"
        // }).addTo(map);
        L.geolet({
            position: 'topleft'
        }).addTo(map);
    </script>
</body>

</html>