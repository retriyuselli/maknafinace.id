<?php

return [
    /*
    | Saat tes lokal, set ABSENSI_SKIP_GEOFENCE=true agar Clock In
    | tidak ditolak hanya karena GPS di luar radius kantor.
    | Production wajib false.
    */
    'skip_geofence' => (bool) env('ABSENSI_SKIP_GEOFENCE', false),
];
