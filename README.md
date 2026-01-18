# cuzk-parcel-numbers-map

This is a very basic PHP app that shows parcels on a map by their number.

## How to install

1. Get an ApiKey from  https://registrace.cuzk.gov.cz -> API dat katastru nemovitostí
2. Create `config/config.php` and set `API_KEY` in it. See `config/config.php.dist` for an example.
3. Provide input file. Input data are loaded from `config/parcel_numbers.json`. See `config/parcel_numbers.json.dist` for an example.
4. Output is automatically created and cached into `config/parcel_coordinates.json`
