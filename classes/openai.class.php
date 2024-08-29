<?php

class _OpenAI
{
    private $apiKey;
    private $apiBase;

    public function __construct($apiKey, $apiBase = OPENAI_API_BASE)
    {
        $this->apiKey = $apiKey;
        $this->apiBase = rtrim($apiBase, '/') . '/'; // Ensure the base URL ends with a slash
    }

    /**
     * Make a request to the OpenAI API
     * 
     * @param string $endpoint The API endpoint to interact with.
     * @param array $data The data to send with the request.
     * @return array|false The response array on success, false on failure.
     */
    public function request($endpoint, $data = [])
    {
        $url = $this->apiBase . ltrim($endpoint, '/'); // Construct the full URL
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Handle SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // Verify host

        $response = curl_exec($ch);

        // Check if the request was successful
        if ($response === false) {
            $error_msg = curl_error($ch);
            $error_no = curl_errno($ch);
            error_log('cURL error: ' . $error_msg . ' (Error Number: ' . $error_no . ')');
            curl_close($ch);
            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode !== 200) {
            // Log the HTTP status code and response
            error_log('OpenAI API request failed. HTTP Status Code: ' . $httpCode);
            error_log('Response: ' . $response);
            return false;
        }

        return json_decode($response, true);
    }
}
