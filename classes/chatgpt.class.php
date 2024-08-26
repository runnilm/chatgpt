<?php

require_once __DIR__ . '/../config.php'; // Include configuration file for constants

class ChatGPT
{
    private $api_key;
    private $api_url;

    public function __construct()
    {
        $this->api_key = CHATGPT_API_KEY;
        $this->api_url = CHATGPT_API_URL;
    }

    // Function to send a message to the ChatGPT API and handle the response
    public function sendMessage($message, &$context)
    {
        $prompt = "
        You are an assistant that helps with scheduling classes, listing classes, and contacting support.
        Respond with a JSON object with three possible fields:
        1. `user_message`: A natural language message to display to the user.
        2. `backend_action`: A structured field indicating the backend action to be performed with any necessary details.
        3. `error_message`: If you can't understand the user's request or it is outside your capabilities, respond with an `error_message` stating 'Sorry, I can't do that.' and explain your capabilities.

        Example response:
        {
            \"user_message\": \"Your class has been scheduled.\",
            \"backend_action\": {
                \"action\": \"scheduleClass\",
                \"datetime\": \"2023-09-08T15:00:00\"
            }
        }
        User: $message
        ";

        $data = [
            "model" => "gpt-4",
            "messages" => array_merge($context, [["role" => "user", "content" => $prompt]])
        ];

        $options = [
            "http" => [
                "header" => "Content-type: application/json\r\nAuthorization: Bearer " . $this->api_key,
                "method" => "POST",
                "content" => json_encode($data),
            ],
        ];

        $context[] = ["role" => "user", "content" => $message];

        $contextObject = stream_context_create($options);
        $response = file_get_contents($this->api_url, false, $contextObject);
        $responseData = json_decode($response, true);

        if (isset($responseData['choices'][0]['message']['content'])) {
            $assistantResponse = $responseData['choices'][0]['message']['content'];
            $context[] = ["role" => "assistant", "content" => $assistantResponse];

            // Log the entire structured response for debugging
            $this->logResponse($assistantResponse);

            return $this->handleStructuredResponse($assistantResponse);
        } else {
            return json_encode([
                "user_message" => "Error: No valid response from ChatGPT.",
                "backend_action" => null
            ]);
        }
    }

    // Function to handle structured response from ChatGPT
    private function handleStructuredResponse($response)
    {
        // Decode the structured response into an associative array
        $responseArray = json_decode($response, true);

        if ($this->isUserFacingMessage($responseArray)) {
            if (isset($responseArray['user_message'])) {
                return $responseArray['user_message'];
            } elseif (isset($responseArray['error_message'])) {
                return $responseArray['error_message'];
            }
        }

        // If the message is not user-facing, respond with a generic error message
        return 'Sorry, I cannot do that. I can help with scheduling classes, listing your scheduled classes, or contacting support.';
    }

    // Method to check if the response is user-facing
    private function isUserFacingMessage($responseArray)
    {
        // Check for typical backend structures or keywords to filter out technical responses
        if (isset($responseArray['backend_action']) || strpos(json_encode($responseArray), 'backend_action') !== false) {
            return false;
        }

        // Additional checks for known technical phrases or format
        if (strpos(json_encode($responseArray), 'example response') !== false) {
            return false;
        }

        // Otherwise, assume the response is user-facing
        return true;
    }

    // Method to log the entire response for debugging
    private function logResponse($response)
    {
        // Print to console for debugging (in server logs)
        error_log("Debugging ChatGPT Response: " . $response);

        // Alternatively, write to a log file
        // file_put_contents('debug_log.txt', "Debugging ChatGPT Response: " . $response . PHP_EOL, FILE_APPEND);
    }

    // Other methods such as scheduleClass, listClasses, emailSupport remain unchanged
    public function scheduleClass($datetime)
    {
        // Implementation for scheduling a class
        return "Class scheduled for " . $datetime;
    }

    public function listClasses()
    {
        // Implementation for listing classes
        return "Here is a list of your classes.";
    }

    public function emailSupport($message)
    {
        // Implementation for emailing support
        return "Support has been emailed with your message: " . $message;
    }
}
