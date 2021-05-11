**ILLocation fetcher**

_General description_

This library makes simple http-requests to open Israeli governmental database to fetch list of it's all settlements and streets.
Access to the database is organized using CKAN-API standard. CKAN docs you can find here: https://docs.ckan.org/en/2.9/

_Installation_

1. You must add this repository as a source of packages in composer.json file.
   `{
   "type": "vcs",
   "url": "https://github.com/Isreal-IT/il-location-fetcher"
   }
   `
2. Install the package using composer
    `composer install israel-it/il-location-fetcher`

3. Run following command:
    `php artisan il_locations:install`

4. Move to config/il_location_fetch.php and in city_entity, street_entity put class name of according entities:
   `'city_entity'   => City::class,
   'street_entity'  => Street::class,
   `
   
**Usage**

Run following command to import il_locations:
    `php artisan il_locations:fetch`

_Overview and considerations_

In config file il_location_fetch.php you can find following options.
`ckan_server` - server location of ckan governmental database. Currently it is set to https://data.gov.il
`city_resource_id` - resource id of database of cities. Currently set to 5c78e9fa-c2e2-4771-93ff-7f400a12f7ba'
`street_resource_id` - resource id of database with streets. Currently set to 9ad3862c-8391-4b2f-84a4-2d4c68625f4b
You can access directly to ckan database without doing any requests. The site provides UI for it and you can download resources as a json file.

_Chunking._ Library takes data from databases IP in chunks. If you have problems with performance you can tweak chunk size by following parameters in config:
`
    'city_fetch_chunk_size'   => 1300,
    'street_fetch_chunk_size' => 5000,
`
_Settlements identity._ Cities and streets in those databases have each their own id, BUT real indetification of city and street is made by fields
"city_code" and "street_code". City_code is unique for each city. Streets can have the same street_code, but they can not have the same street_code and city_code at the same time. I.e. to streets with the same code can't be located in the same city.

_Updates in database._ In ckan database of IL cities and streets sometimes can be updated. Updated database is made with different resource. I.e. if someone on makes major changes
to database than new city and street resources will be created with new id each. Previous resources are usually NOT discarded and still active.

_Database structure._
Some resources can have hebrew characters in their field names. To battle this you can configure mapping in config/il_location_fetch.php file using city_transform_record_map and street_transform_record_map parameters.
_These parameters bind database field to field in laravel model._

_Other caveats._ I don't know if city and street codes will remain consistent between old and updated versions of resources. So, if you want to update list of streets and cities be careful and do research of codes consistency.
