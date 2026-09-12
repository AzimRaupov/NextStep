<?php

return [
    'model' => env('OPENAI_COURSE_MODEL', 'gpt-5-nano'),
    'placement_question_count' => 6,
    'step_question_count' => 4,

    /*
     * A roadmap is a list of sections (parent steps), each broken down into
     * a handful of actionable lessons (child steps). How many sections a
     * roadmap must contain depends on the resolved student level: the lower
     * the level, the longer the path, since a student starting from scratch
     * needs the prerequisites and the basics broken down into small,
     * digestible sections, not a handful of broad topics.
     */
    'roadmap_modules' => [
        'beginner' => ['min' => 5, 'max' => 7],
        'intermediate' => ['min' => 4, 'max' => 6],
        'advanced' => ['min' => 3, 'max' => 5],
    ],

    // How many lessons (child steps) each section must contain.
    'module_steps' => ['min' => 3, 'max' => 5],

    'pass_score' => 70,
];
