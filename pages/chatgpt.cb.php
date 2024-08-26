<?php

require_once '../classes/chatgpt.class.php';

session_start(); // Start session to keep context

class _View
{
    private $chatGPT;
    private $context;

    public function __construct()
    {
        $this->chatGPT = new ChatGPT();
        $this->context = isset($_SESSION['chat_context']) ? $_SESSION['chat_context'] : [];

        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] == true) {
            $this->onAJAX();
        }
    }

    private function onAJAX()
    {
        $message = isset($_REQUEST['message']) ? $_REQUEST['message'] : '';

        if ($message) {
            // Get the structured response from ChatGPT
            $response = $this->chatGPT->sendMessage($message, $this->context);

            // Update session context after each interaction
            $_SESSION['chat_context'] = $this->context;

            // Echo only the user_message part of the structured response
            echo $response;
        } else {
            echo 'Error: No message provided.';
        }
    }
}

// Instantiate the handler
new _View();
