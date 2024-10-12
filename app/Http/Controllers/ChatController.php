<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function chat(String $prompt) {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'temperature' => 0.7,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        // Extract the response content and clean the markdown code block
        $aiResponse = $response->json()['choices'][0]['message']['content'];
        $cleanedResponse = preg_replace('/^```json\n|```$/', '', $aiResponse);

        // Convert the cleaned string to an associative array if necessary
        $jsonResponse = json_decode($cleanedResponse, true);

        // Return as a valid JSON response
        return $jsonResponse;
    }

    public function analyzeText(Request $request) {
        $text = $request->input('text');
        $prompt = "Create a foot note based on this piece of text: " . $text . " Please, return the response as a JSON object with footNote as its key.";

        return response()->json($this->chat($prompt));
    }

    public function analyzeAuthor(Request $request) {
        $name = $request->input('name');
        $prompt = "Generate a JSON object with the following keys for the literary author $name:
            themes: Common themes in their works.
            genres: Genres they worked in.
            period: Literary period they belong to.
            keyFacts: Key facts and major life events.
            bornYear: Year of birth.
            deathYear: Year of death (if applicable).
            famousWorks: Top 3 famous works.
            nationality: Their nationality.
            only fields with plural keys should be arrays and the dates can be strings if needed to.";

        return response()->json($this->chat($prompt));
    }
}
