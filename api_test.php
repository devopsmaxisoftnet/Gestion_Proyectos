<?php


header('Content-Type: application/json; charset=utf-8');

$url = "https://contratacionesabiertas.oece.gob.pe/api/v1/releases";


$ch = curl_init();


curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout de 15 segundos


curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Accept-Language: es-ES,es;q=0.9',
    'Connection: keep-alive'
]);

// Ejecutar la petición
$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Formatear la respuesta
$result = [
    'status_code' => $http_status,
    'success' => ($http_status >= 200 && $http_status < 300),
    'message' => '',
    'data' => null
];

if ($result['success']) {
    $result['message'] = "¡Conexión exitosa a la API desde este servidor!";
    $result['data'] = json_decode($response, true);
} else {
    $result['message'] = "Error conectando a la API.";
    $result['error_details'] = $error ?: "El servidor de OECE respondió con un código HTTP $http_status.";
    $result['raw_response'] = $response;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
