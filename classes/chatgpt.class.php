<?php

require_once 'openai.class.php';

class _ChatGPT extends _OpenAI
{
    private $tools; // Private array to hold the tools

    public function __construct($apiKey, $apiBase = OPENAI_API_BASE)
    {
        parent::__construct($apiKey, $apiBase); // Call the parent constructor

        // Initialize the $tools array with four different tools
        $this->tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "getTime",
                    "description" => "Get the current time. Call this whenever you need to know the current time, for example when a user says 'I want to schedule a class next Thursday at 4PM' or 'What are my classes for the next 48 hours?'",
                    'strict' => true,
                    "parameters" => [
                        "type" => "object",
                        "properties" => (object)[],
                        "required" => [],
                        "additionalProperties" => false,
                    ],
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "scheduleClass",
                    "description" => "Schedules a class session for the given class and student at the given time. For example, call this after getting the current time when the user says 'I want to schedule a class next Thursday at 4PM' or 'I want to schedule a class tonight.'",
                    'strict' => true,
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "datetime" => [
                                "type" => "string",
                                "description" => "The date and time that the class will be scheduled; must be divisible by 5 minutes. Must be formatted like 'August 28th, 2024 4:05PM'"
                            ],
                            "student_name" => [
                                "type" => "string",
                                "description" => "The first and last name of the student. For example, 'Ronnie Mayberry'."
                            ],
                            "class_name" => [
                                "type" => "string",
                                "description" => "The name of the class to be scheduled. For example, 'Math 101'."
                            ],
                        ],
                        "required" => ["datetime", "student_name", "class_name"],
                        "additionalProperties" => false,
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listClasses",
                    "description" => "Gets a list of all of the given student's classes over the given timeframe. For example, call this when the user requests their schedule like 'What are my classes for this week?' or 'What does my schedule look like tonight?'",
                    'strict' => true,
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "timeframe" => [
                                "type" => "string",
                                "description" => "The given timeframe that the user is trying to see their schedule over. For example, '48 hours' or '7 days', etc."
                            ],
                        ],
                        "required" => ["timeframe"],
                        "additionalProperties" => false,
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "emailSupport",
                    "description" => "Sends an email from the user to the support staff. For example, call this when the user requests to message support like 'I want to send a message to support that says 'Help me schedule this class.' or 'My payment is not working''",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "content" => [
                                "type" => "string",
                                "description" => "The text that will be sent in the email to support. For example, 'Help me schedule this class.' or 'My payment is not working''"
                            ],
                        ],
                        "required" => ["content"],
                        "additionalProperties" => false,
                    ]
                ]
            ]
        ];
    }

    /**
     * Chat with the model using various options.
     * 
     * @param array $messages A list of messages comprising the conversation so far.
     * @param array $options Additional options for the request.
     * @return array|false The response array on success, false on failure.
     */
    public function chat(array $messages, array $options = [])
    {
        // Define required parameters
        $data = [
            'messages' => $messages,
            'model' => $options['model'] ?? 'gpt-3.5-turbo', // Default model if not specified
            'tools' => $this->tools, // Include the tools in the data
        ];

        // Optional parameters (alphabetical order with data types and descriptions)
        $optionalParams = [
            'frequency_penalty',    // float | null: Adjusts the model's likelihood to repeat the same line. Range: -2.0 to 2.0
            'function_call',        // string | array | null: Controls which function is called by the model.
            'functions',            // array | null: List of functions the model may generate JSON inputs for.
            'logit_bias',           // array | null: Modifies the likelihood of specified tokens appearing. Maps token IDs to bias values.
            'logprobs',             // boolean | null: Whether to return log probabilities of the output tokens.
            'max_tokens',           // integer | null: Maximum number of tokens to generate in the response.
            'n',                    // integer | null: Number of chat completion choices to generate.
            'parallel_tool_calls',  // boolean: Enables parallel calling of tools during the chat.
            'presence_penalty',     // float | null: Penalizes new tokens based on whether they appear in the text so far. Range: -2.0 to 2.0
            'response_format',      // object | null: Specifies the format of the response, such as JSON schema.
            'seed',                 // integer | null: Seed for deterministic sampling to ensure repeatable results.
            'service_tier',         // string | null: Specifies the latency tier for processing the request.
            'stop',                 // string | array | null: Sequences where the API will stop generating further tokens.
            'stream',               // boolean | null: If true, streams partial message deltas like in ChatGPT.
            'stream_options',       // object | null: Options for configuring streaming behavior.
            'temperature',          // float | null: Sampling temperature to use. Higher values = more random output. Range: 0 to 2
            'tool_choice',          // string | object | null: Controls which tool (if any) the model uses.
            'tools',                // array | null: List of tools the model can call.
            'top_logprobs',         // integer | null: Number of top tokens to return with log probabilities. Requires logprobs to be true.
            'top_p',                // float | null: Nucleus sampling parameter. Range: 0 to 1
            'user',                 // string | null: Unique identifier for the end-user to help OpenAI monitor abuse.
        ];

        // Merge optional parameters dynamically
        foreach ($optionalParams as $param) {
            if (isset($options[$param])) {
                $data[$param] = $options[$param];
            }
        }

        $response = $this->request('/chat/completions', $data);
        error_log(json_encode($response, JSON_PRETTY_PRINT));

        if (!empty($response)) {
            $userMessage = $response['choices'][0]['message']['content'] ?? '';
            $functionCall = $response['choices'][0]['message']['tool_calls'][0]['function'] ?? [];
            $functionName = $functionCall['name'] ?? '';
            $functionArgs = $functionCall['arguments'] ?? [];

            if (!empty($functionName)) {
                // call the relevant function with the relevant $functionArgs.
                switch ($functionName) {
                    case 'getTime':
                        // this is the case when the API wants to call the getTime() method to get the current time, so we need to send it back to the API.
                        break;
                    case 'scheduleClass':
                        // this is the case when the API wants to call the scheduleClass() method to schedule a class using the arguments datetime, student_name, and class_name.
                        break;
                    case 'listClasses':
                        // this is the case when the API wants to call the listClasses() method to list all of the user's classes using the argument timeframe, so we can just display this list as a message back to the user.
                        break;
                    case 'emailSupport':
                        // this is the case when the API wants to call the emailSupport() method to email the support staff using the argument content, so we can just send a message back to the user that their email has been sent.
                        break;
                }
            } else if (!empty($userMessage)) {
                // return this message like normal, it's a response to the user.
            } else {
                // something went wrong.
            }
        }

        // Make the API request and return the response
        return $response;
    }

    private function getTime() {}

    private function scheduleClass($timestamp) {}

    private function listClasses($timeframe) {}

    private function emailSupport($content) {}
}
