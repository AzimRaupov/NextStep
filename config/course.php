<?php

return [
    'model' => env('OPENAI_COURSE_MODEL', 'gpt-5-nano'),
    'placement_question_count' => 6,
    'step_question_count' => 4,

    /*
     * How many steps a roadmap must contain, per resolved student level.
     * The lower the level, the longer the path: a student starting from
     * scratch needs the prerequisites and the basics broken down into
     * small, digestible steps, not a handful of broad topics.
     */
    'roadmap_steps' => [
        'beginner' => ['min' => 18, 'max' => 26],
        'intermediate' => ['min' => 14, 'max' => 20],
        'advanced' => ['min' => 10, 'max' => 16],
    ],

    'pass_score' => 70,
];
