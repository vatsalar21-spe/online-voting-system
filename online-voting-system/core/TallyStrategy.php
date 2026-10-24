<?php

namespace Core;

interface TallyStrategy
{
    /**
     * @param array $candidates rows from the candidates table for one election
     * @param array $blocks ledger_blocks rows for that same election
     * @return array{totals: array<int,int>, winners: int[]}
     */
    public function tally(array $candidates, array $blocks, ?int $selectN = null): array;
}
