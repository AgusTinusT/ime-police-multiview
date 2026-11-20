<?php

return [
    // Officer dianggap hiatus bila last_duty_at lebih lama dari ini & tidak sedang live.
    'hiatus_days' => (int) env('POLICE_HIATUS_DAYS', 14),

    // Officer yang baru ditambahkan ke database di-highlight di dashboard selama ini (hari).
    'new_officer_days' => (int) env('POLICE_NEW_OFFICER_DAYS', 14),
];

