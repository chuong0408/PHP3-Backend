<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'prompt'   => 'required|string|max:2000',
            'context'  => 'nullable|string',
            'history'  => 'nullable|array',
        ]);

        $prompt         = $request->input('prompt');
        $productContext = $request->input('context', '');
        $history        = $request->input('history', []);
        $apiKey         = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'error' => 'GEMINI_API_KEY chưa được cấu hình trong file .env'
            ], 500);
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

        // Tạo system prompt
        $systemPrompt = "Bạn là trợ lý bán hàng thông minh của Green Electric - cửa hàng điện gia dụng tại Buôn Ma Thuột. "
            . "Nhiệm vụ của bạn là tư vấn sản phẩm, trả lời câu hỏi của khách hàng một cách thân thiện và chuyên nghiệp. "
            . "Hãy trả lời ngắn gọn, súc tích, dễ hiểu bằng tiếng Việt. "
            . "Khi gợi ý sản phẩm, hãy kèm ID sản phẩm ở cuối câu theo định dạng [ID] hoặc [ID1,ID2] nếu có nhiều sản phẩm. "
            . "Nếu không có sản phẩm phù hợp thì không cần kèm ID. "
            . ($productContext ? "\n\nDanh sách sản phẩm hiện có:\n{$productContext}" : "");

        // Xây dựng mảng contents với lịch sử hội thoại
        $contents = [];

        // Thêm system context vào tin nhắn đầu tiên
        $firstUserText = $systemPrompt . "\n\nKhách hỏi: " . $prompt;

        if (empty($history)) {
            // Không có lịch sử → gửi thẳng
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $firstUserText]],
            ];
        } else {
            // Có lịch sử → đưa system vào tin nhắn user đầu tiên trong history
            foreach ($history as $idx => $item) {
                $role = $item['role'] === 'user' ? 'user' : 'model';
                $text = $item['content'];

                if ($idx === 0 && $role === 'user') {
                    $text = $systemPrompt . "\n\nKhách hỏi: " . $text;
                }

                $contents[] = [
                    'role'  => $role,
                    'parts' => [['text' => $text]],
                ];
            }

            // Thêm tin nhắn mới nhất
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ];
        }

        $response = Http::timeout(30)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents'         => $contents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 800,
            ],
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => 'Không thể kết nối tới Gemini API. Vui lòng thử lại sau.'
            ], $response->status());
        }

        $data  = $response->json();
        $text  = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            return response()->json([
                'error' => 'AI không trả về nội dung. Vui lòng thử lại.'
            ], 500);
        }

        return response()->json([
            'text' => $text,
        ]);
    }
}