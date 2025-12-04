<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class OpenAIService
{
    public static function householdAssistant(string $question, array $context): ?string
    {
        // Use Laravel config, NOT a constant
        $apiKey = config('services.openai.key');  // or config('openai.key') if you made that

        if (!$apiKey) {
            Log::error('OpenAI API key missing.');
            return null;
        }

        $system = <<<SYS
You are **HousePlan AI**, an assistant for a smart pantry, meal planner, and budget app.

You receive a JSON object called "context" with these keys:
- "pantry_items": ALL current pantry items for this household.
    Each item can have:
    {
      "id": number,
      "ingredient_id": number | null,
      "name": string | null,
      "quantity": number | null,
      "unit": string | null,
      "location": string | null,
      "expiry_date": "YYYY-MM-DD" | null
    }
- "shopping_lists": shopping lists with items.
- "expenses": household expenses.

Rules:

1. When the user asks about items that will expire soon, ALWAYS scan the ENTIRE pantry_items array.
   - Pay special attention to the item(s) with the **nearest future expiry_date**.
   - If several items share the earliest expiry date, mention all of them.
2. Never invent items, dates, or amounts that are not present in the JSON.
3. If the JSON does not contain enough information to answer something, say you don't have that data.
4. For budget questions, use only the provided "expenses".
5. Keep answers short and practical (2–6 sentences), in friendly plain English.

Always base your answer ONLY on the context JSON and the question.
SYS;

        $userContent = "User question:\n" . $question . "\n\n"
            . "Here is the household context as JSON:\n"
            . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $payload = [
            'model'    => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $userContent],
            ],
            'temperature' => 0.2,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: ' . 'Bearer ' . $apiKey,   // ✅ use $apiKey
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
        ]);

        $resp      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            Log::error('OpenAI curl error: ' . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            Log::error('OpenAI HTTP error: ' . $httpCode . ' body: ' . $resp);
            return null;
        }

        $j = json_decode($resp, true);
        $content = $j['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            Log::error('OpenAI response missing content: ' . $resp);
            return null;
        }

        return trim($content);
    }
}
