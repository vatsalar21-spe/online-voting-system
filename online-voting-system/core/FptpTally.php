<?php

namespace Core;

class FptpTally implements TallyStrategy
{
    public function tally(array $candidates, array $blocks, ?int $selectN = null): array
    {
        $totals = [];
        foreach ($candidates as $candidate) {
            $totals[(int) $candidate['id']] = 0;
        }

        foreach ($blocks as $block) {
            $choice = (int) json_decode($block['choice_data'], true);
            if (array_key_exists($choice, $totals)) {
                $totals[$choice]++;
            }
        }

        $max = $totals === [] ? 0 : max($totals);
        $winners = $max > 0
            ? array_keys(array_filter($totals, fn($count) => $count === $max))
            : [];

        return ['totals' => $totals, 'winners' => $winners];
    }
}
