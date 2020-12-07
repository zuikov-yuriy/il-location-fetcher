<?php

return [

    'ckan_server'        => 'https://data.gov.il',

    'city_resource_id'   => '5c78e9fa-c2e2-4771-93ff-7f400a12f7ba',

    'street_resource_id' => '9ad3862c-8391-4b2f-84a4-2d4c68625f4b',

    'city_transform_record_map' => [
        'שם_ישוב'  => 'name',
        'סמל_ישוב' => 'city_code',
    ],

    'street_transform_record_map' =>  [
        'שם_רחוב'  => 'name',
        'סמל_ישוב' => 'city_code',
        'סמל_רחוב' => 'street_code',
    ],

    'street_city_code_field' => 'סמל_ישוב',

    'city_fetch_chunk_size'   => 1300,

    'street_fetch_chunk_size' => 5000,

    'city_entity'        => '',

    'street_entity'      => '',

];