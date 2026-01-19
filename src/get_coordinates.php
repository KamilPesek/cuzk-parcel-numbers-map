<?php
declare(strict_types=1);

use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * Get parcels with data needed for Leaflet
 *
 * @return array
 */
function getParcelsForLeaflet(): array
{
    $parcelInfos = getParcelInfos();

    $parcelsForLeaflet = [];
    $parcelsNotFound = [];
    foreach ($parcelInfos as $cadastralArea => $parcelsInCadastralArea) {
        foreach ($parcelsInCadastralArea as $parcelNumber => $parcelInfo) {
            if ($parcelInfo) {
                $parcelsForLeaflet[] = [
                    'cadastralArea' => $cadastralArea,
                    'cadastralAreaName' => $parcelInfo['katastralniUzemi']['nazev'],
                    'parcelNumber' => $parcelNumber,
                    'coordinates' => [$parcelInfo['definicniBod']['lat'], $parcelInfo['definicniBod']['long']],
                    'landType' => $parcelInfo['druhPozemku']['nazev'],
                    'area' => $parcelInfo['vymera'],
                ];
            } else {
                $parcelsNotFound[] = $cadastralArea . ' - ' . $parcelNumber;
            }
        }
    }

    return [$parcelsForLeaflet, $parcelsNotFound];
}

/**
 * Get complete information about parcels
 *
 * @return array
 */
function getParcelInfos(): array
{
    try {
        if (!file_exists(INPUT_FILE_PATH)) {
            file_put_contents(INPUT_FILE_PATH, json_encode([], JSON_THROW_ON_ERROR));
        }

        if (!file_exists(OUTPUT_FILE_PATH)) {
            file_put_contents(OUTPUT_FILE_PATH, json_encode([], JSON_THROW_ON_ERROR));
        }

        $inputParcelNumbers = json_decode(file_get_contents(INPUT_FILE_PATH), true, 512, JSON_THROW_ON_ERROR);
        $existingParcelNumbers = json_decode(file_get_contents(OUTPUT_FILE_PATH), true, 512, JSON_THROW_ON_ERROR);

        foreach ($inputParcelNumbers as $cadastralArea => $parcelNumbers) {
            foreach ($parcelNumbers as $parcelNumber) {
                $fullCadastralNumber = $cadastralArea . '/' . $parcelNumber;

                if (!array_key_exists($parcelNumber, $existingParcelNumbers[$cadastralArea])) {
                    $parcelInfo = getParcelInfo((string) $cadastralArea, $parcelNumber);

                    if ($parcelInfo) {
                        $definingPointCoordinates = $parcelInfo['definicniBod'];
                        [$lat, $long] = convertCoordinates($definingPointCoordinates['x'], $definingPointCoordinates['y']);

                        $parcelInfo['definicniBod']['lat'] = $lat;
                        $parcelInfo['definicniBod']['long'] = $long;
                    }

                    // it has to be an empty array, not null, because null is not saved in JSON
                    $existingParcelNumbers[$cadastralArea][$parcelNumber] = $parcelInfo;
                    file_put_contents(OUTPUT_FILE_PATH, json_encode($existingParcelNumbers, JSON_THROW_ON_ERROR)); // "cache it" for next run

                    logMessage('Coordinates found for ' . $fullCadastralNumber);
                } else {
                    logMessage('Coordinates already exists for ' . $fullCadastralNumber);
                }
            }
        }
    } catch (JsonException $e) {
        logMessage($e->getMessage());
        exit;
    }

    return $existingParcelNumbers;
}

/**
 * Get info about the parcel
 *
 * @param string $cadastralArea e.g. 721981
 * @param string $parcelNumber  e.g. 161/1
 *
 * @return array|null
 */
function getParcelInfo(string $cadastralArea, string $parcelNumber): ?array
{
    $parcelNumberParts = explode('/', $parcelNumber);

    $queryParams = [
        'KodKatastralnihoUzemi' => $cadastralArea,
        'DruhCislovaniParcely' => '2',
        'KmenoveCisloParcely' => $parcelNumberParts[0],
    ];

    if (isset($parcelNumberParts[1]) && '0' !== $parcelNumberParts[1]) {
        $queryParams['PoddeleniCislaParcely'] = $parcelNumberParts[1];
    }

    $url = CUZK_API_BASE_URL . '?' . http_build_query($queryParams);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'ApiKey: ' . API_KEY,
    ]);

    $response = curl_exec($ch);

    if (false === $response) {
        logMessage('cURL error: ' . curl_error($ch));
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    try {
        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        logMessage('JsonException - ' . $e->getMessage());
        return null;
    }

    return $data['data'][0] ?? null;
}

/**
 * Log message on screen
 *
 * @param string $message
 *
 * @return void
 */
function logMessage(string $message)
{
    if (ENABLE_LOGGING) {
        echo (new DateTime())->format('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL;
    }
}

/**
 * Convert X, Y coordinates to latitude and longitude
 * S-JTSK (EPSG:5514) → WGS84 (EPSG:4326)
 *
 * @param float $coordinateX
 * @param float $coordinateY
 *
 * @return array{0: float, 1: float} [lat, lon]
 */
function convertCoordinates(float $coordinateX, float $coordinateY): array
{
    $proj4 = new Proj4php();

    $src = new Proj('EPSG:5514', $proj4);
    $dst = new Proj('EPSG:4326', $proj4);

    $point = new Point($coordinateX, $coordinateY);
    $proj4->transform($src, $dst, $point);

    return [
        $point->g,
        $point->x,
    ];
}
