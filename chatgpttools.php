<?php

return [
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
