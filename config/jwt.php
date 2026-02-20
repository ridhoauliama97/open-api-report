<?php

return [
    // Shared key for HS256 signing.
    'secret' => env('JWT_SECRET'),

    // Internal token lifetime in minutes.
    'ttl' => (int) env('JWT_TTL', 60),

    // Only HS256 is supported in the internal implementation.
    'algo' => env('JWT_ALGO', 'HS256'),

    // Comma-separated required claims.
    'required_claims' => array_values(array_filter(array_map(
        static fn(string $claim): string => trim($claim),
        explode(',', (string) env('JWT_REQUIRED_CLAIMS', 'iss,iat,exp,nbf,sub,jti'))
    ))),
];
