<?php

require_once '../config.php';
require_once '../classes/chatgpt.class.php';

class _View
{
    private $chatGPT;
    private $messages;

    public function __construct()
    {
        $this->chatGPT = new _ChatGPT(OPENAI_API_KEY); // Initialize with API key from config

        // Initialize messages from POST data, ensuring it is an array
        $this->messages = isset($_POST['messages']) ? json_decode($_POST['messages'], true) : [];

        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == true) {
            $this->onAJAX();
        }
    }

    private function onAJAX()
    {
        if (!empty($_POST['action'])) {
            switch ($_POST['action']) {
                case 'sendMessage':
                    if (!empty($_POST['message'])) {
                        // Ensure $messages is an array before appending
                        if (!is_array($this->messages)) {
                            $this->messages = [];
                        }

                        // Append the user message to the context
                        $userMessage = ['role' => 'user', 'content' => $_POST['message']];
                        $this->messages[] = $userMessage;

                        // Get the response from ChatGPT
                        $response = $this->chatGPT->chat($this->messages);

                        if ($response) {
                            // Append the assistant's response to the context
                            $assistantMessage = [
                                'role' => 'assistant',
                                'content' => $response['choices'][0]['message']['content']
                            ];
                            $this->messages[] = $assistantMessage;
                            error_log(json_encode($this->messages, JSON_PRETTY_PRINT));

                            // Send the response back along with the updated context
                            echo json_encode([
                                'success' => true,
                                'message' => $assistantMessage['content'],
                                'messages' => $this->messages // Updated context
                            ]);
                            exit;
                        }
                    }
                    break;
            }
        }

        echo json_encode(['success' => false]);
        exit;
    }
}

// Instantiate the handler
new _View();
