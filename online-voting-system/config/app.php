<?php

return [
    'timezone' => 'Asia/Kolkata',
    // Base path the app is served under, e.g. '/online-voting-system/public'.
    // Empty string if served from the web root.
    'base_path' => '',
    // Used to derive voter_hash = sha256(voter_id . ':' . election_id . ':' . ledger_secret)
    // for ledger blocks. Must match the value used when database/schema.sql's
    // seed ledger blocks were generated, or the seeded chain won't verify.
    'ledger_secret' => 'ovs-demo-secret-change-me',
];
