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
        if ((float)($lead['estimated_value'] ?? 0) >= 50000) { $score += 15; $reasons[] = 'High estimated value'; }
        elseif ((float)($lead['estimated_value'] ?? 0) >= 10000) { $score += 8; $reasons[] = 'Meaningful estimated value'; }
        if (!empty($lead['next_follow_up'])) { $score += 10; $reasons[] = 'Follow-up scheduled'; }

        $score = min(100, $score);
        $recommendation = $score >= 75 ? 'Prioritize this lead and follow up soon.' : ($score >= 50 ? 'Qualify this lead and schedule the next action.' : 'Collect more information before prioritizing.');

        return ['score' => $score, 'reasons' => $reasons, 'recommendation' => $recommendation];
    }
}
