<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ini_set('memory_limit', '512M'); // CUZK response is big

const INPUT_FILE_PATH = __DIR__ . '/../parcel_numbers.json';
const OUTPUT_FILE_PATH = __DIR__ . '/../parcel_coordinates.json';
const CUZK_API_ENDPOINT = 'https://services.cuzk.cz/wfs/inspire-cp-wfs.asp?service=WFS&version=2.0.0&request=GetFeature&typeNames=CadastralParcel&outputFormat=application/json';

function getParcelCoordinates(): array
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

                if (!isset($existingParcelNumbers[$cadastralArea][$parcelNumber])) {
                    $cqlFilter = "(parcelIdentifier='" . $fullCadastralNumber . "')";
                    $url = CUZK_API_ENDPOINT . '&CQL_FILTER=' . urlencode($cqlFilter);

                    $requestStart = microtime(true);
                    $json = file_get_contents($url);
                    $requestEnd = microtime(true) - $requestStart;

                    $response = json_decode($json, true, 512, JSON_THROW_ON_ERROR); // this takes approximately 20 s

                    if (empty($response['features'])) {
                        logMessage('"features" index for ' . $fullCadastralNumber . ' is empty');
                        continue;
                    }

                    $feature = $response['features'][0]; // First index is outer boundary, the rest are "holes" etc. So this is fine for us.
                    $existingParcelNumbers[$cadastralArea][$parcelNumber] = $feature['geometry']['coordinates'][0];
                    file_put_contents(OUTPUT_FILE_PATH, json_encode($existingParcelNumbers, JSON_THROW_ON_ERROR)); // "cache it" for next run

                    logMessage('Coordinates found for ' . $fullCadastralNumber . ' in ' . round($requestEnd, 2) . ' seconds');
                } else {
                    logMessage('Coordinates already exists for ' . $fullCadastralNumber);
                }
                break 2;
            }
        }
    } catch (JsonException $e) {
        logMessage($e->getMessage());
        exit;
    }

    return $existingParcelNumbers;
}

function logMessage(string $message)
{
    echo (new DateTime())->format('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL;
}

