<?php

namespace App\Services;

use App\Models\WhatsappNumber;
use App\Models\WhatsappTemplate;

/**
 * Sends business-initiated messages on Cloud numbers through an approved template.
 */
class WhatsappTemplateSender
{
    public function __construct(protected WhatsappCloudService $cloud) {}

    /**
     * @param  array<string, mixed>  $vars  keyed by the template's variable keys
     */
    public function send(WhatsappNumber $number, string $purpose, string $to, array $vars): WhatsappSendResult
    {
        $template = $this->pickTemplate($number, $purpose);

        if (! $template) {
            return WhatsappSendResult::failed("No approved WhatsApp template for purpose '{$purpose}'");
        }

        $parameters = [];
        foreach ((array) $template->variables as $key) {
            $parameters[] = ['type' => 'text', 'text' => $this->sanitize($vars[$key] ?? null)];
        }

        $components = $parameters === [] ? [] : [['type' => 'body', 'parameters' => $parameters]];

        try {
            return $this->cloud->sendTemplate($number, $to, $template->name, $template->language, $components);
        } catch (\Throwable $e) {
            return WhatsappSendResult::failed('Template send failed: '.$e->getMessage());
        }
    }

    protected function pickTemplate(WhatsappNumber $number, string $purpose): ?WhatsappTemplate
    {
        if (! $number->waba_id) {
            return null;
        }

        $templates = WhatsappTemplate::approved()
            ->forPurpose($purpose)
            ->where('waba_id', $number->waba_id)
            ->orderBy('id')
            ->get();

        return $templates->firstWhere('language', 'en') ?? $templates->first();
    }

    /**
     * Meta rejects newlines, tabs and 4+ consecutive spaces in body parameters, and empty values.
     */
    public function sanitize(mixed $value): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        $text = preg_replace('/[\r\n\t]+/', ' ', $text);
        $text = preg_replace('/ {4,}/', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return '-';
        }

        return rtrim(mb_substr($text, 0, 900));
    }
}
