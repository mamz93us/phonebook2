<?php

namespace App\Services;

class GdmsBranchMapper
{
    // Map SIP server (with port) to branch_id
    protected array $map = [
        '10.1.8.10:5060'  => 10, // JED
        '10.2.88.10:5060' => 20, // RYD
        '10.3.0.10:5060'  => 30, // KBR
        '10.4.0.9:5060'   => 40, // ABH
        '10.9.8.10:5060'  => 60, // CAI
        '10.5.0.10:5060'  => 10, // JED
        '10.6.0.10:5060'  => 20, // RYD
    ];

    public function resolveBranchId(string $sipServer): ?int
    {
        // sipServer may contain multiple servers separated by comma
        // take the first one
        $first = trim(explode(',', $sipServer)[0]);

        // normalize: if only IP without port, you can add :5060 if needed
        if (!str_contains($first, ':')) {
            $first .= ':5060';
        }

        return $this->map[$first] ?? null;
    }
}
