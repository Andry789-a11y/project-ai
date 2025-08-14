<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Chat;

class ChatController extends Controller
{
    private function isAskingAboutPdf($input)
    {
        $input = strtolower($input);
        $askKeywords = ['apa isi', 'tentang apa', 'apa yang dibahas', 'is pdf about', 'isi dari pdf'];

        foreach ($askKeywords as $keyword) {
            if (str_contains($input, $keyword)) {
                return true;
            }
        }

        return false;
    }

    public function chat(Request $request)
    {
        try {
            $userId = 1;
            $userInput = $request->input('message');
            $summary = session('pdf_summary');
            $geminiApiKey = env('GEMINI_API_KEY');

            $chatHistory = Chat::where('user_id', $userId)
                ->orderBy('id')
                ->take(20)
                ->get();

            $contents = [];

            $systemPrompt = "Jika pengguna bertanya tentang isi PDF, ketahui bahwa sistem sudah menyediakan ringkasan PDF lewat session bernama 'pdf_summary'. Gunakan informasi itu untuk menjawab.
            Jika pengguna bertanya 'apakah kamu bisa meringkas pdf', jawab 'ya, saya bisa meringkas PDF, silahkan masukkan PDF untuk diringkas'
            Saat ditanya 'siapa kamu', jawab: 'Aku adalah DryyMate, asisten virtual yang ramah dan pintar siap membantumu.'
            Gunakan bahasa yang jelas, sopan, dan membantu pengguna. Hindari markdown seperti ** atau *.
            Jika perlu membuat daftar, gunakan format angka 1., 2., 3., dst. Tambahkan emoji jika sesuai.";

            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => $systemPrompt]]
            ];

            if ($summary && $this->isAskingAboutPdf($userInput)) {
                $contents[] = [
                    'role' => 'model',
                    'parts' => [[
                        'text' => "Ini ringkasan dari PDF yang kamu upload:\n\n" . $summary
                    ]]
                ];
            }

            foreach ($chatHistory as $chat) {
                $contents[] = [
                    'role' => $chat->role,
                    'parts' => [['text' => $chat->message]]
                ];
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userInput]]
            ];

            $response = Http::retry(3, 2000)
                ->timeout(60)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$geminiApiKey}", [
                    'contents' => $contents
                ]);

            if (!$response->ok()) {
                Log::error('Gemini chat error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'reply' => 'Maaf, server AI sedang sibuk. Silakan coba lagi nanti.'
                ], 503);
            }

            $rawReply = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, terjadi kesalahan saat menjawab.';

            $aiReply = preg_replace([
                '/\*\*(.*?)\*\*/s', // hilangkan **
                '/\*(.*?)\*/s',     // hilangkan *
                '/`(.*?)`/s',       // hilangkan `code`
            ], '$1', $rawReply);

            Chat::create([
                'user_id' => $userId,
                'role' => 'user',
                'message' => $userInput,
            ]);

            Chat::create([
                'user_id' => $userId,
                'role' => 'model',
                'message' => $aiReply,
            ]);

            return response()->json(['reply' => $aiReply]);
        } catch (\Exception $e) {
            Log::error('Chat error', ['exception' => $e->getMessage()]);

            return response()->json([
                'reply' => 'Terjadi kesalahan internal.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
