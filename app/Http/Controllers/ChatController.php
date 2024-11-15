<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function chat(string $prompt)
    {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . '***REMOVED***',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'temperature' => 0.7,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        // Extract the response content and clean the markdown code block
        $aiResponse = $response->json()['choices'][0]['message']['content'];
        $cleanedResponse = preg_replace('/^```json\n|```$/', '', $aiResponse);

        // Convert the cleaned string to an associative array if necessary
        $jsonResponse = json_decode($cleanedResponse, true);

        // Return as a valid JSON response
        return $jsonResponse;
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
            themes: Common themes in their works.
            genres: Genres they worked in.
            period: Literary period they belong to.
            key_facts: Key facts and major life events.
            born_year: Year of birth.
            death_year: Year of death (if applicable).
            famous_works: Top 3 famous works.
            nationality: Their nationality.
            only fields with plural keys should be arrays and the dates can be strings if needed to.
            success: If you can't find the author return false here, otherwise return true.";

        return response()->json($this->chat($prompt));
    }
}
