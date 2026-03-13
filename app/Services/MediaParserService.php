<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MediaParserService
{
    protected OpenAiService $openAi;

    protected const IMAGE_MIMETYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/jpg',
    ];

    protected const DOCUMENT_MIMETYPES = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
    ];

    protected const UNSUPPORTED_LEGACY = [
        'application/msword',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-excel',
    ];

    public function __construct(OpenAiService $openAi)
    {
        $this->openAi = $openAi;
    }

    public function parseMediaForEvent(string $base64, string $mimetype, ?string $filename, ?string $caption): ?array
    {
        if (in_array($mimetype, self::IMAGE_MIMETYPES)) {
            return $this->extractEventFromImage($base64, $mimetype, $caption);
        }

        if (in_array($mimetype, self::DOCUMENT_MIMETYPES)) {
            return $this->extractEventFromDocument($base64, $mimetype, $filename, $caption);
        }

        if (in_array($mimetype, self::UNSUPPORTED_LEGACY)) {
            return ['error' => 'unsupported_format'];
        }

        return ['error' => 'unsupported_type'];
    }

    protected function extractEventFromImage(string $base64, string $mimetype, ?string $caption): ?array
    {
        $today = now()->format('Y-m-d (l)');
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');

        $userContent = [];
        if ($caption) {
            $userContent[] = ['type' => 'text', 'text' => "Additional context: {$caption}"];
        }
        $userContent[] = ['type' => 'text', 'text' => 'Extract event details from this image.'];
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => ['url' => "data:{$mimetype};base64,{$base64}"],
        ];

        $result = $this->openAi->chatCompletion(
            messages: [
                [
                    'role' => 'system',
                    'content' => "You are an event detail extractor. Today is {$today}, timezone: {$timezone}. Extract event details from the image provided. Return a JSON object with:\n- title: event name/title (string)\n- date: YYYY-MM-DD format\n- time: HH:MM format (24h), or null if not found\n- location: venue/place or null\n- description: brief description or null\n- has_event: boolean - true if this image contains event information, false if not\n\nIf the image does not contain any event, meeting, appointment, or schedule information, set has_event to false and leave other fields null.\n\nRespond ONLY with valid JSON.",
                ],
                [
                    'role' => 'user',
                    'content' => $userContent,
                ],
            ],
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: 500,
            jsonMode: true,
        );

        if (! $result['success']) {
            Log::error('MediaParser image extraction failed', ['error' => $result['error']]);

            return null;
        }

        $parsed = json_decode($result['content'], true);

        if (! $parsed || empty($parsed['has_event']) || empty($parsed['title']) || empty($parsed['date'])) {
            return null;
        }

        return [
            'title' => $parsed['title'],
            'date' => $parsed['date'],
            'time' => $parsed['time'] ?? '09:00',
            'location' => $parsed['location'] ?? null,
            'description' => $parsed['description'] ?? null,
        ];
    }

    protected function extractEventFromDocument(string $base64, string $mimetype, ?string $filename, ?string $caption): ?array
    {
        $decoded = base64_decode($base64);
        if ($decoded === false) {
            Log::error('MediaParser: base64 decode failed');

            return null;
        }

        $tempPath = sys_get_temp_dir().'/media_'.uniqid().'_'.($filename ?? 'file');

        try {
            file_put_contents($tempPath, $decoded);

            $text = match ($mimetype) {
                'application/pdf' => $this->extractTextFromPdf($tempPath),
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractTextFromDocx($tempPath),
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => $this->extractTextFromPptx($tempPath),
                'text/plain', 'text/csv' => $decoded,
                default => null,
            };
        } finally {
            @unlink($tempPath);
        }

        if (empty($text)) {
            Log::warning('MediaParser: no text extracted from document', ['mimetype' => $mimetype]);

            return null;
        }

        // Truncate to ~4000 chars to keep AI context reasonable
        if (is_string($text) && strlen($text) > 4000) {
            $text = substr($text, 0, 4000).'... [truncated]';
        }

        return $this->extractEventFromText($text, $caption);
    }

    protected function extractEventFromText(string $text, ?string $caption): ?array
    {
        $today = now()->format('Y-m-d (l)');
        $timezone = config('app.timezone', 'Asia/Kuala_Lumpur');

        $prompt = 'Extract event details from this document text.';
        if ($caption) {
            $prompt .= "\n\nAdditional context from user: {$caption}";
        }

        $result = $this->openAi->chatCompletion(
            messages: [
                [
                    'role' => 'system',
                    'content' => "You are an event detail extractor. Today is {$today}, timezone: {$timezone}. Extract event details from the document text provided. Return a JSON object with:\n- title: event name/title (string)\n- date: YYYY-MM-DD format\n- time: HH:MM format (24h), or null if not found\n- location: venue/place or null\n- description: brief description or null\n- has_event: boolean - true if text contains event information, false if not\n\nIf the text does not contain any event, meeting, appointment, or schedule information, set has_event to false and leave other fields null.\n\nRespond ONLY with valid JSON.",
                ],
                [
                    'role' => 'user',
                    'content' => "{$prompt}\n\n---\n\n{$text}",
                ],
            ],
            model: 'gpt-4o',
            temperature: 0.1,
            maxTokens: 500,
            jsonMode: true,
        );

        if (! $result['success']) {
            Log::error('MediaParser text extraction failed', ['error' => $result['error']]);

            return null;
        }

        $parsed = json_decode($result['content'], true);

        if (! $parsed || empty($parsed['has_event']) || empty($parsed['title']) || empty($parsed['date'])) {
            return null;
        }

        return [
            'title' => $parsed['title'],
            'date' => $parsed['date'],
            'time' => $parsed['time'] ?? '09:00',
            'location' => $parsed['location'] ?? null,
            'description' => $parsed['description'] ?? null,
        ];
    }

    protected function extractTextFromPdf(string $filePath): string
    {
        $parser = new \Smalot\PdfParser\Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    protected function extractTextFromDocx(string $filePath): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText().' ';
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText().' ';
                        }
                    }
                }
            }
        }

        return trim($text);
    }

    protected function extractTextFromPptx(string $filePath): string
    {
        $pptx = \PhpOffice\PhpPresentation\IOFactory::load($filePath);
        $text = '';

        foreach ($pptx->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        $text .= $paragraph->getPlainText().' ';
                    }
                }
            }
        }

        return trim($text);
    }
}
