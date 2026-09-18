<?php

// PERBAIKAN AUDIT (item B - BLOCKER BISNIS): radius validasi check-in
// Sales terhadap titik lokasi Customer.
return [

    'check_in_radius_meters' => (int) env('SALES_CHECK_IN_RADIUS_METERS', 150),

];
