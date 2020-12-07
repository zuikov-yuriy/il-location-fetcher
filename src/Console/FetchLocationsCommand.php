<?php

namespace TheP6\ILLocationFetcher\Console;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use TheP6\ILLocationFetcher\CKANFetcher\CKANFetcher;
use TheP6\ILLocationFetcher\CKANFetcher\RecordTransformer;

class FetchLocationsCommand extends Command
{
    protected string $cityResourceId;
    protected string $streetsResourceId;
    protected string $ckanLocationsServer;

    protected array $transformCityRecordMap;
    protected array $transformStreetRecordMap;
    protected string $streetCityCodeField;

    protected int $cityChunkSize;
    protected int $streetChunkSize;

    protected string $citiesEntity;
    protected string $streetsEntity;

    private ?CKANFetcher $ckanFetcher = null;
    private ?RecordTransformer $recordTransformer = null;

    private $cachedCity = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'il_locations:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch cities and their streets according to data.gov.il';

    public function __construct(
        CKANFetcher $ckanFetcher,
        RecordTransformer $recordTransformer
    ) {
        $this->ckanLocationsServer = Config::get('il_location_fetch.ckan_server', '');
        $this->cityResourceId = Config::get('il_location_fetch.city_resource_id', '');
        $this->streetsResourceId = Config::get('il_location_fetch.street_resource_id', '');

        $this->transformCityRecordMap = Config::get('il_location_fetch.city_transform_record_map', []);
        $this->transformStreetRecordMap = Config::get('il_location_fetch.street_transform_record_map', []);
        $this->streetCityCodeField = Config::get('il_location_fetch.street_city_code_field', '');

        $this->cityChunkSize = Config::get('il_location_fetch.city_fetch_chunk_size', 1300);
        $this->streetChunkSize = Config::get('il_location_fetch.street_fetch_chunk_size', 1000);

        $this->citiesEntity = Config::get('il_location_fetch.city_entity', '');
        $this->streetsEntity = Config::get('il_location_fetch.street_entity', '');

        $this->ckanFetcher = $ckanFetcher;
        $this->recordTransformer = $recordTransformer;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!class_exists($this->citiesEntity) || !is_subclass_of($this->citiesEntity, Model::class)) {
            throw new InvalidArgumentException("Class {$this->citiesEntity} does not exists or is not instance of Eloquent Model!");
        }

        if (!class_exists($this->streetsEntity) || !is_subclass_of($this->streetsEntity, Model::class)) {
            throw new InvalidArgumentException("Class {$this->streetsEntity} does not exists or is not instance of Eloquent Model!");
        }

        DB::beginTransaction();

        $this->comment("Started: ".Carbon::now()->format('Y-m-d H:i:s'));

        try {

            $this->comment("Importing cities...");
            $this->importCities();

            $this->comment("Importing streets...");
            $this->importStreets();

        } catch (Exception $e) {
            DB::rollBack();
            $this->comment("Error occurred. All changes will be reverted!");
            $this->error($e->getMessage());
            return 1;
        }

        DB::commit();

        $this->comment("Ended: ".Carbon::now()->format('Y-m-d H:i:s'));
        $this->comment("Import successful");

        return 0;
    }

    private function importCities()
    {
        $chunkNumber = 0;
        $totalProceeded = 0;

        $this->ckanFetcher
            ->setCKANServer($this->ckanLocationsServer)
            ->setDataStoreResourceId($this->cityResourceId);

        $this->recordTransformer
            ->setTransformMap($this->transformCityRecordMap);

        do {
            $citiesRecords = $this->ckanFetcher->fetchRecords([
                'limit'     => $this->cityChunkSize,
                'offset'    => $chunkNumber * $this->cityChunkSize,
            ]);

            $retrievedCitiesCount = count($citiesRecords);

            foreach ($citiesRecords as $record) {
                $this->importCity($record);
            }

            $chunkNumber++;
            $totalProceeded += $retrievedCitiesCount;

            echo ($totalProceeded)." cities processed\n";

        } while ($retrievedCitiesCount >= $this->cityChunkSize);
    }

    private function importCity(array $record)
    {
        $record = $this->recordTransformer->transform($record);

        $city = $this->citiesEntity::query()
            ->where('code', $record['city_code'])
            ->firstOrNew();

        if ($city->name !== null && $record['name'] !== $city->name) {
            $this->warn("Possible city double: ");
            $this->warn("Record {$record['name']}, {$record['city_code']}");
            $this->warn("Record {$city->name}, {$city->code}");
        }

        $city->name = $record['name'];
        $city->code = $record['city_code'];
        $city->save();

        return $city;
    }

    private function importStreets()
    {
        $chunkNumber = 0;
        $totalProceeded = 0;

        $this->ckanFetcher
            ->setCKANServer($this->ckanLocationsServer)
            ->setDataStoreResourceId($this->streetsResourceId);

        $this->recordTransformer
            ->setTransformMap($this->transformStreetRecordMap);

        do {
            $records = $this->ckanFetcher
                ->fetchRecords([
                    'limit'     => $this->streetChunkSize,
                    'offset'    => $chunkNumber * $this->streetChunkSize,
                    'sort'      => $this->streetCityCodeField, //by city code
                ]);

            $retrievedStreetsCount = count($records);

            foreach ($records as $record) {
                $this->importStreet($record);
            }

            $chunkNumber++;
            $totalProceeded += $retrievedStreetsCount;

            echo ($totalProceeded)." streets processed\n";

        } while ($retrievedStreetsCount >= $this->streetChunkSize);
    }

    private function importStreet(array $record)
    {
        $record = $this->recordTransformer->transform($record);

        $street = new $this->streetsEntity();

//        ::query()
//            ->where('code', $record['street_code'])
//            ->where('city_code', $record['city_code'])
//            ->firstOrNew();

        $city = $this->fetchCityFromLocalDB($record['city_code']);

        if ($street->name !== null && $street->name !== $record['name']) {
            $this->warn("Possible street double:");
            $this->warn("Record {$record['name']}, City: {$record['city_code']}, Street: {$record['street_code']}");
            $this->warn("DB Model {$street->name}, City: {$street->city_code}, Street: {$street->code}");
        }

        $street->name = $record['name'];
        $street->code = $record['street_code'];
        $street->city_code = $record['city_code'];
        $street->city_id = $city->id;
        $street->save();

        return $street;
    }

    private function fetchCityFromLocalDB(int $cityCode)
    {
        if (null === $this->cachedCity || $this->cachedCity->code !== $cityCode) {
            $this->cachedCity = $this->citiesEntity::query()->where([
                'code' => $cityCode,
            ])->firstOrFail();
        }

        return $this->cachedCity;
    }
}
