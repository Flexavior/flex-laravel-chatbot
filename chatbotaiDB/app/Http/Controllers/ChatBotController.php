<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class ChatBotController extends Controller
{
    public function showChat(Request $request)
    {
        $nonce = Str::random(32);
        $request->session()->put('csp_nonce', $nonce);

        return view('chatbot', [
            'cspNonce' => $nonce,
            'customer_id' => $request->query('customer_id'),
            'service_number' => $request->query('service_number')
        ]);
    }

    public function sendChat(Request $request)
    {
        try {
            $input = $request->input('input', 'Hello, this is a test');

            $allChunks = $this->readKnowledgeBaseChunks();

            $relevantChunks = $this->findRelevantChunks($allChunks, $input, 3);
            $knowledgeContext = implode("\n\n---\n\n", $relevantChunks);

            $systemPrompt = <<<PROMPT
You are a concise and factual customer support assistant. Use ONLY the provided knowledge base to answer the question.

- Do not go beyond what is available in the knowledge base.
- Keep responses under 100 words.
- Answer in conversational tone
- Format lists or steps clearly, one item per line.
- If no answer is found, reply with:
  Contact: (+959) 98765432  
  Email: enquiry@flexavior.com

Knowledge Base:
{$knowledgeContext}
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'deepseek/deepseek-chat-v3-0324:free',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $input]
                ],
            ]);

            if ($response->successful()) {
                return response()->json([
                    'response' => $response->json()['choices'][0]['message']['content']
                ]);
            }

            return response()->json([
                'error' => 'OpenRouter API call failed.',
                'details' => $response->json()
            ], $response->status());

        } catch (\Throwable $e) {
            Log::error('Chat error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Chat error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function readKnowledgeBaseChunks()
    {
        $chunks = [];
        $files = Storage::allFiles('uploads'); 
        $parser = new PdfParser();

        foreach ($files as $filePath) {
            $fullPath = storage_path("app/{$filePath}");
            $fileName = basename($filePath);

            try {
                if (Str::endsWith($fileName, '.pdf')) {
                    $text = $parser->parseFile($fullPath)->getText();
                    $fileChunks = $this->chunkText($text, 1200);
                } elseif (Str::endsWith($fileName, '.txt')) {
                    $text = file_get_contents($fullPath);
                    $fileChunks = $this->chunkText($text, 1200);
                } else {
                    continue;
                }

                foreach ($fileChunks as $chunk) {
                    $chunks[] = "[From: {$fileName}]\n{$chunk}";
                }

            } catch (\Exception $e) {
                Log::warning("Failed to parse or chunk file: {$fileName}", ['error' => $e->getMessage()]);
                continue;
            }
        }

        return $chunks;
    }

    protected function chunkText($text, $maxChunkSize = 1000)
    {
        $paragraphs = preg_split("/(\r?\n){2,}/", $text);
        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $para) {
            if (strlen($currentChunk . "\n\n" . $para) > $maxChunkSize) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $para;
            } else {
                $currentChunk .= "\n\n" . $para;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    protected function findRelevantChunks(array $chunks, string $query, $maxChunks = 10)
    {
        $relevant = [];

        foreach ($chunks as $chunk) {
            if (Str::contains(strtolower($chunk), strtolower($query))) {
                $relevant[] = $chunk;
                if (count($relevant) >= $maxChunks) break;
            }
        }

        return count($relevant) > 0 ? $relevant : array_slice($chunks, 0, $maxChunks);
    }
}
