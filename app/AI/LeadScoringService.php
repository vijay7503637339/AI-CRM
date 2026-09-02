<?php

declare(strict_types=1);

final class LeadScoringService
{
    /**
     * Deterministic baseline scorer. The same interface can later be backed by an LLM
     * without changing the CRM pages. Score is intentionally explainable.
     */
    public function score(array $lead): array
    {
        $score = 20;
        $reasons = [];

        if (!empty($lead['email'])) { $score += 15; $reasons[] = 'Email available'; }
        if (!empty($lead['phone'])) { $score += 15; $reasons[] = 'Phone available'; }
        if (!empty($lead['company'])) { $score += 10; $reasons[] = 'Company identified'; }
        if (!empty($lead['source'])) { $score += 10; $reasons[] = 'Lead source identified'; }
        if ((float)($lead['value'] ?? 0) >= 50000) { $score += 15; $reasons[] = 'High lead value'; }
        elseif ((float)($lead['value'] ?? 0) >= 10000) { $score += 8; $reasons[] = 'Meaningful lead value'; }
        if (!empty($lead['follow_up_at'])) { $score += 10; $reasons[] = 'Follow-up scheduled'; }

        $score = min(100, $score);
        $priority = $score >= 75 ? 'hot' : ($score >= 50 ? 'warm' : 'cold');
        $recommendation = $score >= 75 ? 'Prioritize this lead and follow up soon.' : ($score >= 50 ? 'Qualify this lead and schedule the next action.' : 'Collect more information before prioritizing.');

        return [
            'score' => $score,
            'priority' => $priority,
            'summary' => 'Lead analyzed using the CRM data currently available.',
            'reasons' => $reasons,
            'recommendation' => $recommendation,
        ];
    }
}
