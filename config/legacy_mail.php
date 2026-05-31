<?php
$localConfig = __DIR__ . '/mail.local.php';

if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        return $local;
    }
}

return [
    'driver' => getenv('MAIL_DRIVER') ?: (getenv('MAIL_MAILER') ?: 'mail'),
    'host' => getenv('MAIL_HOST') ?: '',
    'port' => (int)(getenv('MAIL_PORT') ?: 587),
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
    'username' => getenv('MAIL_USERNAME') ?: '',
    'password' => getenv('MAIL_PASSWORD') ?: '',
    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'contratos@colvatel.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'ColvaContratos',
    'timeout' => (int)(getenv('MAIL_TIMEOUT') ?: 20),
];
