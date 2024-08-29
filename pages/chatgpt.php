<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ChatGPT</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="styles.css" />
</head>

<body>

    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">ChatGPT</h4>
            </div>
            <div class="card-body" id="chat-box">
                <!-- Chat messages will appear here -->
            </div>
            <div class="input-group">
                <input type="text" id="user-input" class="form-control" placeholder="Type your message...">
                <button id="send-btn" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            let messages = []; // Initialize the messages array to keep chat context

            $("#send-btn").click(function() {
                sendUserMessage();
            });

            $("#user-input").keypress(function(e) {
                if (e.which === 13) {
                    sendUserMessage();
                }
            });

            function sendUserMessage() {
                const userInput = $("#user-input").val().trim();

                if (userInput) {
                    // Display user's message
                    $("#chat-box").append(`
                        <div class="message-wrapper chat-container">
                            <div class="chat-bubble user-bubble">${userInput}</div>
                        </div>
                    `);
                    $("#user-input").val('');
                    $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);

                    // Add user's message to the messages array
                    messages.push({
                        role: 'user',
                        content: userInput
                    });

                    // Show typing indicator inside a chat bubble
                    const typingBubble = $(`
                        <div class="message-wrapper chat-container">
                            <div class="chat-bubble bot-bubble">
                                <div class="typing-indicator" id="typing-indicator">
                                    <div></div>
                                    <div></div>
                                    <div></div>
                                </div>
                            </div>
                        </div>
                    `);
                    $("#chat-box").append(typingBubble);
                    $("#typing-indicator").show();

                    $.ajax({
                        url: "chatgpt.cb.php",
                        type: "POST",
                        data: {
                            ajax: true,
                            action: 'sendMessage',
                            message: userInput,
                            messages: JSON.stringify(messages) // Send the full context as a JSON string
                        },
                    }).done(function(response) {
                        response = JSON.parse(response);
                        if (response.success) {
                            // Replace the typing indicator with the response text using typing animation
                            typingBubble.find("#typing-indicator").remove();
                            typeText(response.message, typingBubble.find(".chat-bubble"));

                            // Update the messages array with the assistant's response
                            messages.push({
                                role: 'assistant',
                                content: response.message
                            });
                        } else {
                            console.error('Something went wrong.');
                            typingBubble.find("#typing-indicator").remove();
                            typingBubble.find(".chat-bubble").text("Sorry, something went wrong.");
                        }
                    }).fail(function(xhr, status, error) {
                        console.error("AJAX Error: " + status + error);
                        typingBubble.find("#typing-indicator").remove();
                        typingBubble.find(".chat-bubble").text("Error: Unable to get response.");
                    });
                }
            }

            function typeText(text, element) {
                let i = 0;
                const duration = 1000; // Total time for typing animation in milliseconds
                const speed = Math.max(duration / text.length, 10); // Calculate speed per character

                function typeWriter() {
                    if (i < text.length) {
                        element.append(text.charAt(i));
                        i++;
                        $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);
                        setTimeout(typeWriter, speed);
                    }
                }

                typeWriter();
            }
        });
    </script>
</body>

</html>