<?php
/**
 * ai.php — Gemini AI + Auto Fallback for Smart Trip Planner
 * Supports Google Gemini 1.5 (Free API) + Local fallback
 */

/**
 * Load Gemini API key from .env file or environment variable
 */
function getGeminiApiKey() {
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        $env = parse_ini_file($envPath, true);
        if (!empty($env['GEMINI_API_KEY'])) {
            return trim($env['GEMINI_API_KEY']);
        }
    }
    return getenv('GEMINI_API_KEY') ?: '';
}

/**
 * Generic Gemini API request wrapper
 */
function callGeminiAPI($model, $prompt) {
    $apiKey = getGeminiApiKey();
    if (empty($apiKey)) {
        error_log("[Gemini] Missing API key");
        return null;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$apiKey";

    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => ["temperature" => 0.7, "maxOutputTokens" => 1000]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT => 60, // prevent long hang
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Ensure logs folder exists
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);

    file_put_contents($logDir . '/ai_debug.txt', $response ?: $error);

    if ($error || !$response) return null;

    $json = json_decode($response, true);
    $text = $json["candidates"][0]["content"]["parts"][0]["text"] ?? "";
    if (!$text) return null;

    // Clean markdown wrappers
    $text = preg_replace('/```json|```/i', '', trim($text));

    $decoded = json_decode($text, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Suggest destinations using Gemini (with fallback)
 */
function aiSuggestDestinationsGemini($from, $budget, $days, $type, $region, $people) {
    $prompt = "
Suggest 10 travel destinations starting from $from in $region for a $days-day $type trip for $people people.
Budget: ₹$budget.
Return ONLY JSON like:
[
  {\"city\": \"Goa\", \"reason\": \"Beaches and nightlife\", \"estimatedCost\": 18000},
  {\"city\": \"Jaipur\", \"reason\": \"Culture and forts\", \"estimatedCost\": 20000}
]
";

    $result = callGeminiAPI("gemini-1.5-flash-latest", $prompt);

    // ✅ Auto fallback if Gemini fails
    if (!$result) {
        error_log("[Gemini] Fallback triggered for destination suggestions");

        return [
            ["city" => "Goa", "reason" => "Beaches and nightlife", "estimatedCost" => 20000],
            ["city" => "Jaipur", "reason" => "Culture and forts", "estimatedCost" => 18000],
            ["city" => "Rishikesh", "reason" => "Adventure and river rafting", "estimatedCost" => 15000],
            ["city" => "Agra", "reason" => "Historical monuments", "estimatedCost" => 16000],
            ["city" => "Ooty", "reason" => "Hill station and tea gardens", "estimatedCost" => 14000]
        ];
    }

    return $result;
}

/**
 * Generate daily itinerary using Gemini (with fallback)
 */
function aiGenerateItinerary($city, $days, $type, $budget, $people) {
    $prompt = "
Create a detailed $days-day travel itinerary for $city, India.
Trip type: $type
Budget: ₹$budget for $people people.
Each day must include at least 2 unique activities (no empty or leisure-only days).
Return valid JSON like:
[
  {\"day\": 1, \"activities\": [
    {\"name\": \"Visit Taj Mahal\", \"time\": 3, \"cost\": 1000, \"category\": [\"Historical\"]},
    {\"name\": \"Local food walk\", \"time\": 2, \"cost\": 300, \"category\": [\"Food\"]}
  ]}
]
";

    $result = callGeminiAPI("gemini-1.5-pro-latest", $prompt);

    // ✅ Auto fallback if AI fails
    if (!$result) {
        error_log("[Gemini] Fallback triggered for itinerary");

        return [
            [
                "day" => 1,
                "activities" => [
                    ["name" => "City sightseeing", "time" => 3, "cost" => 500, "category" => ["Cultural"]],
                    ["name" => "Local market visit", "time" => 2, "cost" => 200, "category" => ["Shopping"]]
                ]
            ],
            [
                "day" => 2,
                "activities" => [
                    ["name" => "Try local street food", "time" => 2, "cost" => 300, "category" => ["Food"]],
                    ["name" => "Heritage museum visit", "time" => 3, "cost" => 400, "category" => ["Historical"]]
                ]
            ]
        ];
    }

    return $result;
}
?>
