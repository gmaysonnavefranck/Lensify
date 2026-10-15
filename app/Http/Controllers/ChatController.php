<?php

namespace App\Http\Controllers;

use App\Enums\TextType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(string $prompt)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4-turbo',
            'temperature' => 0.0,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        // Extract the response content and clean the markdown code block
        Log::info($response->json());
        $aiResponse = $response->json()['choices'][0]['message']['content'];
        // Clean the response (remove markdown code block markers)
        $cleanedResponse = preg_replace('/^```json\n|```$/', '', $aiResponse);

        // Convert the cleaned string into an associative array (ensure it’s valid JSON)
        $jsonResponse = json_decode($cleanedResponse, true);

        // Ensure the response is a valid JSON array
        if (json_last_error() === JSON_ERROR_NONE) {
            return $jsonResponse;  // Return the decoded JSON as a response
        } else {
            // Handle JSON decoding errors if needed
            return response()->json([
                'error' => 'Failed to decode AI response.',
                'message' => json_last_error_msg(),
            ], 400);
        }
    }

    public function analyzeText(Request $request)
    {
        $text = $request->input('text');
        $prompt = 'Create a foot note based on this piece of text: ' . $text . ' Please, return the response as a JSON object with footNote as its key.';

        return response()->json($this->chat($prompt));
    }

    public function findAuthorInformation(Request $request)
    {
        $name = $request->input('name');
        $prompt = "You are a literary expert, generate a JSON object with the following keys for the literary author {$name}:
            themes: Common themes in the author works.
            genres: Genres the author worked in.
            period: Literary movement the author belongs to.
            key_facts: Major life events that contributed for the style and themes of the author.
            born_year: Year of birth.
            death_year: Year of death (if applicable).
            famous_works: Top 3 famous works.
            nationality: the author nationality.
            only fields with plural keys should be arrays and the dates can be strings if needed to.
            success: If you can't find the author return false here, otherwise return true.";

        return response()->json($this->chat($prompt));
    }

    public function findTextInformation(Request $request)
    {
        $content = ($request->input('content'));
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        $content = preg_replace('/\n/', ' ', $content);

        $prompt = "You are a literary expert, generate a JSON obejct with the following keys for this piece of text {$content}
            title: Title of the text as it was published.
            author: Author of the text.
            year: Year of publication.
            language: Language of the provided text.
            type: Type of text from this list: " . TextType::toCommaSeparated();

        return response()->json($this->chat($prompt));
    }
}
