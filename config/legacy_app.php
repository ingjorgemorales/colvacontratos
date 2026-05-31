<?php
return [
    'name' => getenv('APP_NAME') ?: 'ColvaContratos',
    'base_url' => getenv('APP_URL') ?: '',
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Bogota',
    'debug' => (getenv('APP_DEBUG') ?: 'false') === 'true',
];
