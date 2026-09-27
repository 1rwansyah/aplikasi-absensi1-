<?php

return [
    'match_threshold' => (float) env('FACE_MATCH_THRESHOLD', 0.5),
    'min_match_percent' => (int) env('FACE_MIN_MATCH_PERCENT', 74),
    'descriptor_length' => 128,
];
