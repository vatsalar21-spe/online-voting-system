<?php

namespace Core;

class MultiChoiceTally implements TallyStrategy
{
    public function tally(array $candidates, array $blocks, ?int $selectN = null): array
    {
        $totals = [];
        foreach ($candidates as $candidate) {
            $totals[(int) $candidate['id']] = 0;
        }

        foreach ($blocks as $block) {
            $choices = json_decode($block['choice_data'], true) ?: [];
            foreach ($choices as $choice) {
                $choice = (int) $choice;
                if (array_key_exists($choice, $totals)) {
                    $totals[$choice]++;
                }
            }
        }

        $n = $selectN ?? count($totals);
        arsort($totals);
        $winners = array_slice(array_keys($totals), 0, $n);

        // Include ties at the cutoff so a borderline candidate isn't silently
        // dropped for the sake of hitting exactly N winners.
        if (count($totals) > $n) {
            $cutoffCount = array_values($totals)[$n - 1] ?? null;
            if ($cutoffCount !== null) {
                foreach ($totals as $candidateId => $count) {
                    if ($count === $cutoffCount && !in_array($candidateId, $winners, true)) {
                        $winners[] = $candidateId;
                    }
                }
            }
        }

        return ['totals' => $totals, 'winners' => $winners];
    }
}
