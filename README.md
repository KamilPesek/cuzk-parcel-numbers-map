# cuzk-parcel-numbers-map

This is a very basic PHP app that shows parcels on a map by their number.

## How to install

1. Get an Apikey from  https://registrace.cuzk.gov.cz -> API dat katastru nemovitostí
2. Set api key in `config/config.php` -> `API_KEY`
3. Provide input file. Input data are loaded from `config/parcel_numbers.json`. See `config/parcel_numbers.json.dist` for an example.
4. Output is automatically created and cached into `config/parcel_coordinates.json`
