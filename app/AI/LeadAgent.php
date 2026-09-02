<?php

declare(strict_types=1);

/**
 * AI Lead Agent.
 *
 * Uses a deterministic fallback when no AI provider is configured. When
 * OPENAI_API_KEY is present, the same service boundary can call an LLM.
 * The agent only receives the lead/activity context supplied here.
 */
final class LeadAgent
{
    public function analyze(array $lead, array $activities): array
    {
        $baseline = $this->baseline($lead);

        $apiKey = getenv('OPENAI_API_KEY') ?: '';
        if ($apiKey === '') {
            return $baseline + ['provider' => 'baseline'];
        }

        $result = $this->callOpenAI($apiKey, $lead, $activities, $baseline);
        return $result ?: ($baseline + ['provider' => 'baseline']);
    }

    private function baseline(array $lead): array
    {
        $score = (int)($lead['ai_score'] ?? 0);
        $factors = [];

        if (!empty($lead['email'])) { $factors[] = 'Email available'; }
        if (!empty($lead['phone'])) { $factors[] = 'Phone available'; }
        if (!empty($lead['company'])) { $factors[] = 'Company identified'; }
        if (!empty($lead['source'])) { $factors[] = 'Known lead source'; }
        if ((float)($lead['estimated_value'] ?? 0) >= 100000) { $factors[] = 'High opportunity value'; }
        if (!empty($lead['next_follow_up'])) { $factors[] = 'Follow-up scheduled'; }

        $qualification = $score >= 75 ? 'hot' : ($score >= 50 ? 'warm' : ($score > 0 ? 'cold' : 'unknown'));
        $nextAction = $qualification === 'hot'
            ? 'Contact the lead within 24 hours and move toward a meeting or proposal.'
            : ($qualification === 'warm'
                ? 'Follow up with a focused qualification question and confirm timeline/budget.'
                : 'Collect missing contact and requirement details before investing sales time.');

        return [
            'score' => max(0, min(100, $score)),
            'qualification' => $qualification,
            'summary' => 'Lead analyzed using the CRM data currently available.',
            'next_action' => $nextAction,
            'suggested_followup' => 'Hi ' . trim((string)$lead['name']) . ', just following up on your enquiry. Is this still a good time to discuss your requirements?',
            'factors' => $factors,
        ];
    }

    private function callOpenAI(string $apiKey, array $lead, array $activities, array $baseline): ?array
    {
        $payload = [
            'model' => getenv('OPENAI_MODEL') ?: 'gpt-5-mini',
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'You are a CRM sales assistant. Analyze the supplied lead data only. Return strict JSON with keys score (0-100 integer), qualification (hot|warm|cold|unknown), summary, next_action, suggested_followup, factors (array of short strings). Never invent facts.'
                ],
                [
                    'role' => 'user',
                    'content' => json_encode(['lead' => $lead, 'activities' => $activities, 'baseline' => $baseline], JSON_UNESCAPED_UNICODE)
                ]
            ],
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            return null;
        }

        $response = json_decode($raw, true);
        $text = $response['output'][0]['content'][0]['text'] ?? null;
        if (!is_string($text)) {
            return null;
        }

        $data = json_decode($text, true);
        if (!is_array($data) || !isset($data['score'], $data['qualification'], $data['summary'], $data['next_action'])) {
            return null;
        }

        $data['score'] = max(0, min(100, (int)$data['score']));
        $data['qualification'] = in_array($data['qualification'], ['hot','warm','cold','unknown'], true) ? $data['qualification'] : 'unknown';
        $data['factors'] = is_array($data['factors'] ?? null) ? $data['factors'] : [];
        $data['suggested_followup'] = (string)($data['suggested_followup'] ?? '');
        $data['provider'] = 'openai';
        return $data;
    }
}
