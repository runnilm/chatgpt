<?php

require_once '../config.php'; // Assuming MAX_THREAD_LENGTH is defined in this file
require_once 'openai.class.php';

class _ChatGPT extends _OpenAI
{
    private $tools; // Private array to hold the tools
    private $messages = []; // Initialize messages array

    public function __construct($apiKey, $apiBase = OPENAI_API_BASE)
    {
        parent::__construct($apiKey, $apiBase); // Call the parent constructor

        // Load tools from the configuration file
        $this->tools = require '../chatgpttools.php';
    }

    /**
     * Chat with the model using various options.
     * 
     * @param array $messages A list of messages comprising the conversation so far.
     * @param array $options Additional options for the request.
     * @return string The response message for the user.
     */
    public function chat(array $messages, array $options = [])
    {
        // Initialize the internal messages array with the passed messages
        $this->messages = $messages;

        // Define required parameters
        $data = [
            'messages' => $this->messages,
            'model' => $options['model'] ?? 'gpt-3.5-turbo', // Default model if not specified
            'tools' => $this->tools, // Include the tools in the data
        ];

        // Merge optional parameters dynamically
        $optionalParams = [
            'frequency_penalty',
            'function_call',
            'functions',
            'logit_bias',
            'logprobs',
            'max_tokens',
            'n',
            'parallel_tool_calls',
            'presence_penalty',
            'response_format',
            'seed',
            'service_tier',
            'stop',
            'stream',
            'stream_options',
            'temperature',
            'tool_choice',
            'tools',
            'top_logprobs',
            'top_p',
            'user'
        ];

        foreach ($optionalParams as $param) {
            if (isset($options[$param])) {
                $data[$param] = $options[$param];
            }
        }

        $response = $this->request('/chat/completions', $data);

        if (!empty($response) && isset($response['choices'][0]['message'])) {
            $messageData = $response['choices'][0]['message'];
            $userMessage = $this->filterText($messageData['content'] ?? ''); // Apply text filtering
            $functionCall = $messageData['tool_calls'][0]['function'] ?? null;

            if ($functionCall) {
                $functionName = $functionCall['name'] ?? '';
                $functionArgs = !empty($functionCall['arguments']) ? json_decode($functionCall['arguments'], true) : [];

                // Call the relevant function with the relevant $functionArgs and handle internally
                switch ($functionName) {
                    case 'getTime':
                        $currentTime = $this->getTime();
                        $this->addMessage('system', "Current time is " . $currentTime . ".");
                        return $this->chat($this->messages, $options); // Recurse with updated context
                    case 'scheduleClass':
                        return $this->scheduleClass($functionArgs);
                    case 'listClasses':
                        return $this->listClasses($functionArgs['timeframe'] ?? '');
                    case 'emailSupport':
                        return $this->emailSupport($functionArgs['content'] ?? '');
                    default:
                        error_log('Unknown function name: ' . $functionName);
                        return 'Sorry, I didn\'t understand your request.';
                }
            } elseif (!empty($userMessage)) {
                // Add the assistant's message to the context
                $this->addMessage('assistant', $userMessage);
                return $userMessage;
            } else {
                error_log('Unexpected response structure or empty message.');
                return 'Sorry, something went wrong.';
            }
        }

        // Log error if the response is empty or does not contain expected structure
        error_log('Invalid or empty response from the API.');
        return 'Sorry, something went wrong.';
    }

    /**
     * Adds a message to the internal messages array and trims if necessary.
     * 
     * @param string $role The role of the message sender (e.g., 'user', 'assistant', 'system').
     * @param string $content The content of the message.
     */
    private function addMessage($role, $content)
    {
        // Add new message to the array
        $this->messages[] = [
            'role' => $role,
            'content' => $content
        ];

        // Trim the array if it exceeds the maximum length
        error_log(count($this->messages));
        while (count($this->messages) > MAX_THREAD_LENGTH) {
            array_shift($this->messages); // Remove the first (oldest) element from the array
            error_log('Context trim: Message array exceeded maximum length, removing oldest message.');
        }
        error_log(json_encode($this->messages, JSON_PRETTY_PRINT)); // Log the response for debugging
    }

    // Method to handle getting the current time and feeding it back into the API
    private function getTime()
    {
        return date('Y-m-d H:i:s'); // Return current time in a standard format
    }

    // Method to handle scheduling a class
    private function scheduleClass($args)
    {
        $datetime = $args['datetime'] ?? null;
        $studentName = $args['student_name'] ?? null;
        $className = $args['class_name'] ?? null;

        error_log($datetime . ' ' . $studentName . ' ' . $className);
        if (isset($datetime, $studentName, $className)) {
            // Logic to schedule the class goes here (e.g., save to database)
            return "Class '" . $className . "' scheduled for " . $studentName . " on " . $datetime . ".";
        }

        error_log('Invalid arguments for scheduleClass: ' . json_encode($args, JSON_PRETTY_PRINT));
        return 'Unable to schedule the class due to missing or incorrect information.';
    }

    // Method to handle listing classes over a timeframe
    private function listClasses($timeframe)
    {
        if ($timeframe) {
            // Logic to retrieve and list classes goes here (e.g., query from database)
            return "Listing classes for the next " . $timeframe . ".";
        }

        error_log('Invalid timeframe for listClasses: ' . $timeframe);
        return 'Please provide a valid timeframe to list classes.';
    }

    // Method to handle emailing support
    private function emailSupport($content)
    {
        if ($content) {
            // Logic to send an email to support goes here (e.g., use mail() function)
            return "Your message has been sent to support: " . $content;
        }

        error_log('Invalid content for emailSupport: ' . $content);
        return 'Unable to send your message to support due to missing content.';
    }

    // Method to filter and format text, replacing newlines with <br> and escaping HTML
    private function filterText($text)
    {
        $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); // Escape HTML special characters
        return nl2br($escapedText); // Replace newline characters with <br> tags
    }
}
