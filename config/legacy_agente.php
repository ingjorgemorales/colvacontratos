<?php
// Configuración del Agente de Pólizas (motor Flask interno, colvatel-app).
// El módulo "Agente de Pólizas" de ColvaContratos usa estos valores para hablar
// con el motor por HTTP interno. El navegador nunca toca el Flask directamente:
// siempre pasa por el proxy PHP (protegido por el login de ColvaContratos).
return [
    // URL base del motor Flask (sin barra final). Local: http://127.0.0.1:5000
    'url' => getenv('AGENTE_URL') ?: 'http://127.0.0.1:5000',
    // Clave compartida; debe coincidir con INTERNAL_API_KEY del .env del Flask.
    'key' => getenv('AGENTE_KEY') ?: '',
    // Segundos máximos de espera (el análisis con IA puede tardar).
    'timeout' => (int)(getenv('AGENTE_TIMEOUT') ?: 200),
];
