<?php

// Daftar ID station yang akan digunakan
$idStations = [
    "150126", "STA3047", "14032792", "STA0018", "AAWS0330", "STA2124",
    "STA2126", "STA0017", "14103431", "150122", "STA2179", "STA5024", "STA2076", "150352",
    "160034", "14032791", "STA0123", "AAWS0331", "150354", "STA2125", "14063063", "160031",
    "AAWS0332", "150356", "150125", "STA0124", "14032790", "AAWS0340", "STA3236", "STA2127",
    "AAWS0333", "150355", "STA0019", "150124", "150127", "150353", "STA5074", "STA3239", "14063064"
];

// Ambil data geospasial titik dari API
$titik_data = 'https://apiaws.bmkg.go.id/getdata?filter=rr&token=4g2289qCYfpX831xa9CHo78n7Ev0g8&tgl_mulai=%s&tgl_selesai=%s&id_station=%s';
$apiUrlTemplate2 = "https://apiaws.bmkg.go.id/getdata?filter=rr&token=4g2289qCYfpX831xa9CHo78n7Ev0g8&tgl_mulai=%s&tgl_selesai=%s&id_station=%s";

// Fungsi untuk mendapatkan data dari API
function getDataFromApi($url)
{
    // Inisialisasi cURL
    $ch = curl_init();

    // Set opsi cURL
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Eksekusi cURL dan dapatkan respon
    $response = curl_exec($ch);

    // Tutup cURL
    curl_close($ch);

    // Kembalikan respon
    return $response;
}

// Ambil data geospasial polygon dari sumber yang sesuai
$polygon_data = json_decode(file_get_contents('data/spasial/area_stasiun_kec.geojson'), true);

function substractOneHour($time)
{
    $dateTime = new DateTime($time);
    $dateTime->sub(new DateInterval('PT1H'));
    return $dateTime->format('Y-m-d H:i');
}

function subtractOneDay($date)
{
    $dateDay = new DateTime($date);
    $dateDay->sub(new DateInterval('P1D'));
    return $dateDay->format('Y-m-d H:i');
}

// Mendapatkan tanggal saat ini
$currentDate = new DateTime('now', new DateTimeZone("UTC"));
$startDate = $currentDate->format('Y-m-d H:i');
$startDate = substractOneHour($startDate);

// Mendapatkan waktu saat ini dalam UTC
$current_time = new DateTime("now", new DateTimeZone("UTC"));
$endDate = $current_time->format('Y-m-d H:i');

//Waktu akses
$currentTime = new DateTime("now", new DateTimeZone("UTC"));

function fetchDataWithFallback($titik_data, $startDate, $endDate, $idStations)
{
    $allData = [];
    foreach ($idStations as $idStation) {
        $apiUrl = sprintf($titik_data, $startDate, $endDate, $idStation);
        $response = getDataFromApi($apiUrl);
        $dataArray = json_decode($response, true);

        if (!empty($dataArray)) {
            $allData[$idStation] = $dataArray;
        }
    }
    return $allData;
}

// Dapatkan data dari API dengan fallback
$dataArray = fetchDataWithFallback($titik_data, $startDate, $endDate, $idStations);

if (!empty($dataArray)) {
    foreach ($dataArray as $idStation => $data) {
        if (!empty($data)) {
            $lastData = end($data);
            $tgl_lastdata = $lastData['tanggal'];
            $tgl_lastdata = substr($tgl_lastdata, 0, 16);
            $tgl_mulai = substractOneHour($tgl_lastdata);
        }
    }
}

function fetchDataOneHourBefore($apiUrlTemplate2, $tgl_mulai, $tgl_lastdata, $idStations)
{
    $allData2 = [];
    foreach ($idStations as $idStation) {
        $apiUrl2 = sprintf($apiUrlTemplate2, $tgl_mulai, $tgl_lastdata, $idStation);
        $response2 = getDataFromApi($apiUrl2);
        $dataArray2 = json_decode($response2, true);

        if (!empty($dataArray2)) {
            $allData2[$idStation] = $dataArray2;
        }
    }
    return $allData2;
}

// Dapatkan data dari API dengan fallback
$dataArray2 = fetchDataOneHourBefore($apiUrlTemplate2, $tgl_mulai, $tgl_lastdata, $idStations);

// Buat associative array untuk memetakan id_station dengan nilai RR terakhir
$id_station_to_last_idstat = array();
$id_station_to_last_typestat = array();
$id_station_to_last_namestat = array();
$id_station_to_last_date = array();
$id_station_to_last_rr = array();

if (!empty($dataArray) && !empty($dataArray2)) {
    foreach ($dataArray as $idStation => $data) {
        $data2 = $dataArray2[$idStation];
        if (!empty($data) && !empty($data2)) {
            $endData = end($data); // Ambil data terakhir
            $previousData = current($data2); // Ambil data 1 jam sebelumnya
            $tgl = $endData['tanggal'];
            $tgl = substr($tgl, 11, 5);
            if ($currentTime->format('H:i') >= '00:00' && $currentTime->format('H:i') < '01:10') {
                if ($tgl >= '00:00' && $tgl < '01:10') {
                    $id_station = $endData['id_station'];
                    $rr_value = $endData['rr'];
                    $id_station_to_last_rr[$id_station] = $rr_value;
                    $date_value = $endData['tanggal'];
                    $id_station_to_last_date[$id_station] = $date_value;
                } else {
                    $id_station = $endData['id_station'];
                    $rr_value = "Belum ada data hari ini";
                    $id_station_to_last_rr[$id_station] = $rr_value;
                    $date_value = "Belum ada data hari ini";
                    $id_station_to_last_date[$id_station] = $date_value;
                }
            } else {
                if ($tgl >= '00:00' && $tgl < '01:10') {
                    $id_station = $endData['id_station'];
                    $rr_value = $endData['rr'];
                    $id_station_to_last_rr[$id_station] = $rr_value;
                    $date_value = $endData['tanggal'];
                    $id_station_to_last_date[$id_station] = $date_value;
                } else {
                    $id_station = $endData['id_station'];
                    $endRR = $endData['rr'];
                    $previousRR = $previousData['rr'];
                    $rr_value = $endRR - $previousRR;
                    $id_station_to_last_rr[$id_station] = $rr_value;
                    $date_value = $endData['tanggal'];
                    $id_station_to_last_date[$id_station] = $date_value;
                }
            }
        }

    }
}

// Memasukkan nilai atribut RR terakhir dari associative array ke dalam data polygon
foreach ($polygon_data['features'] as &$feature) {
    $id_station = $feature['properties']['id_station'];
    if (
        isset($id_station_to_last_rr[$id_station])
        && isset($id_station_to_last_date[$id_station])
    ) {
        // Jika id_station ditemukan dalam associative array
        $feature['properties']['date'] = $id_station_to_last_date[$id_station];
        $feature['properties']['rr'] = $id_station_to_last_rr[$id_station];
    } else {
        // Jika id_station tidak ditemukan dalam associative array        
        $feature['properties']['rr'] = "Belum ada data dalam 1 jam terkahir";
        $feature['properties']['date'] = "Belum ada data dalam 1 jam terakhir";
    }
}

// Format ulang data polygon dengan nilai atribut RR ke dalam format GeoJSON
$result = array(
    'type' => 'FeatureCollection',
    'features' => $polygon_data['features']
);

// Konversi ke JSON
$result_json = json_encode($result);

// Output data GeoJSON
echo $result_json;
