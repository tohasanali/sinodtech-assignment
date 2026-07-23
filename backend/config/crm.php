<?php

return [
    'lost_customer_days' => (int) env('LOST_CUSTOMER_DAYS', 90),
    'reactivation_points' => (int) env('REACTIVATION_KPI_POINTS', 10),
    'recontact_cooldown_days' => (int) env('RECONTACT_COOLDOWN_DAYS', 7),
];
