<?php
// config/apisigma.php

define('SIGMA_ENV', 'dev'); // 'dev' or 'prod'

// Note: Ensure the URL ends with / if expected, but the endpoint usually follows. 
// Manual says: https://.../v10/<EndPoint>
define('SIGMA_BASE_URL_DEV', 'https://develop.sig2k.com/sigmasaas/unidespo@sigma/sigma/api/v10/');
define('SIGMA_BASE_URL_PROD', 'https://secure.sig2k.com/sigmasaas/unidespo@sigma/sigma/api/v10/');

// User provided: "unidespo@sigma:VUuN0Az+QWYNthwraKHIEvzWevdDc+EvmPIP1pUPopA="
define('SIGMA_FULL_CREDENTIAL', 'unidespo@sigma:VUuN0Az+QWYNthwraKHIEvzWevdDc+EvmPIP1pUPopA=');

function getSigmaUrl($endpoint)
{
    $baseUrl = (SIGMA_ENV === 'prod') ? SIGMA_BASE_URL_PROD : SIGMA_BASE_URL_DEV;
    return $baseUrl . $endpoint;
}

function callSigmaApi($endpoint, $params = [], $method = 'GET', $data = null)
{
    $url = getSigmaUrl($endpoint);

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    // Verified Method: Header-Full (Use the entire string as the token)
    $token = SIGMA_FULL_CREDENTIAL;

    $ch = curl_init();

    $headers = [
        'X-Auth-Token: ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $responseHeaders = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
        $len = strlen($header);
        $header = explode(':', $header, 2);
        if (count($header) < 2) // ignore invalid headers
            return $len;

        $responseHeaders[trim($header[0])] = trim($header[1]);
        return $len;
    });

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    curl_close($ch);

    if ($error) {
        return ['error' => $error, 'http_code' => $httpCode];
    }

    return [
        'response' => json_decode($response, true),
        'raw_response' => $response,
        'http_code' => $httpCode,
        'final_url' => $finalUrl,
        'headers' => $responseHeaders
    ];
}
?>