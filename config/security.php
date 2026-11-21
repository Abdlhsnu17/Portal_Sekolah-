<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Autentikasi dua faktor
    |--------------------------------------------------------------------------
    |
    | MFA ditangguhkan untuk sementara. Ubah MFA_ENABLED=true ketika sekolah
    | siap menerapkan alur enrolment dan pemulihan akun kepada petugas.
    |
    */
    'mfa_enabled' => (bool) env('MFA_ENABLED', false),
];
