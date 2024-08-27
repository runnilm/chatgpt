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
        if (!empty($_POST['action'])) {
            switch ($_POST['action']) {
                case 'sendMessage':
                    if (!empty($_POST['message'])) {
                        // Get the structured response from ChatGPT
                        $response = $this->chatGPT->sendMessage($_POST['message'], $this->context);

                        // Update session context after each interaction
                        $_SESSION['chat_context'] = $this->context;

                        // Echo only the user_message part of the structured response
                        echo json_encode(['success' => true, 'message' => $response]);
                        exit;
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
