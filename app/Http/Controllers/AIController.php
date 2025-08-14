<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Document;

class AIController extends Controller
{
    public function getLastSummary()
    {
        $lastDoc = \App\Models\Document::latest()->first();

        if (!$lastDoc) {
            return response()->json([
                'summary' => 'Belum ada PDF yang diproses.'
            ]);
        }

        return response()->json([
            'filename' => $lastDoc->filename,
            'summary' => $lastDoc->summary
        ]);
    }

    public function process(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:pdf|max:10240',
            ]);

            $path = $request->file('file')->store('pdfs');
            $pdfpath = storage_path("app/$path");
            $filename = $request->file('file')->getClientOriginalName();

            $scriptPath = base_path('python/extract_text.py');
            $extractedText = shell_exec("python \"$scriptPath\" \"$pdfpath\" 2>&1");
            $extractedText = mb_convert_encoding($extractedText, 'UTF-8', 'UTF-8');

            if (!$extractedText || strlen(trim($extractedText)) < 10) {
                return response()->json([
                    'summary' => 'Gagal membaca isi PDF.',
                    'audio_url' => null
                ], 400);
            }

            $shortText = mb_substr($extractedText, 0, 8000);

            $apiKey = env('GEMINI_API_KEY');
            $geminiResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => "Ringkas dan jelaskan isi dokumen berikut. Jangan gunakan simbol seperti tanda bintang (*), garis datar (-), atau markdown lainnya:\n\n$shortText"]
                        ]
                    ]
                ]
            ])->json();

            $summary = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? 'Tidak ada hasil dari Gemini.';
            Session::put('pdf_summary', $summary);

            Document::create([
                'filename' => $filename,
                'summary' => $summary
            ]);

            $ttsResponse = Http::post('http://127.0.0.1:5000/tts', [
                'text' => $summary,
                'lang' => 'id',
                'speed' => 1.2
            ]);

            if (!$ttsResponse->ok()) {
                return response()->json([
                    'summary' => $summary,
                    'audio_url' => null,
                    'error' => 'Gagal mengubah teks ke audio melalui gTTS Flask API: ' . $ttsResponse->body()
                ], 500);
            }

            $audioBinary = $ttsResponse->body();
            $audioFileName = 'dryymate_' . time() . '.mp3';
            Storage::disk('public')->put("audio/$audioFileName", $audioBinary);

            session(['pdf_text' => $extractedText]);

            return response()->json([
                'summary' => $summary,
                'audio_url' => asset("storage/audio/$audioFileName")
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'summary' => 'Terjadi kesalahan internal.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
