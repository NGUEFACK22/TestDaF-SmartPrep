<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Types de questions (moteur générique QuestionRenderer)
    |--------------------------------------------------------------------------
    */
    'question_types' => [
        'multiple_choice', 'single_choice', 'true_false', 'matching', 'ordering',
        'fill_blank', 'short_answer', 'category_assignment', 'pair_assignment',
        'text_input', 'essay', 'audio_response', 'video_response',
    ],

    /*
    |--------------------------------------------------------------------------
    | Familles d'exercices par compétence (référence pédagogique TestDaF)
    |--------------------------------------------------------------------------
    | Décrit la structure pédagogique ; aucun contenu protégé n'est copié.
    */
    'exercise_types' => [
        'lesen' => [
            'lueckentext_ergaenzen' => 'Lückentext ergänzen',
            'textabschnitte_ordnen' => 'Textabschnitte ordnen',
            'multiple_choice' => 'Multiple Choice',
            'sprachhandlungen_zuordnen' => 'Sprachhandlungen zuordnen',
            'aussagen_kategorien_zuordnen' => 'Aussagen Kategorien zuordnen',
            'aussagen_begriffspaar_zuordnen' => 'Aussagen einem Begriffspaar zuordnen',
            'fehler_in_zusammenfassung_erkennen' => 'Fehler in Zusammenfassung erkennen',
        ],
        'hoeren' => [
            'kurzantwort_uebersicht_ergaenzen' => 'Kurzantwort – Übersicht ergänzen',
            'kurzantwort_textstellen_begriffspaar' => 'Kurzantwort – Textstellen zu Begriffspaar notieren',
            'fehler_in_zusammenfassung_erkennen' => 'Fehler in Zusammenfassung erkennen',
            'aussagen_personen_zuordnen' => 'Aussagen Personen zuordnen',
            'kurzantwort_gliederungspunkte' => 'Kurzantwort – Gliederungspunkte zu Vortrag ergänzen',
            'multiple_choice' => 'Multiple Choice',
            'laut_und_schriftbild_abgleichen' => 'Laut- und Schriftbild abgleichen',
        ],
        'schreiben' => [
            'argumentativen_text_schreiben' => 'Argumentativen Text schreiben',
            'informationen_zusammenfassen' => 'Informationen aus Lesetext und Grafik zusammenfassen',
        ],
        'sprechen' => [
            'rat_geben' => 'Rat geben',
            'optionen_abwaegen' => 'Optionen abwägen',
            'text_zusammenfassen' => 'Text zusammenfassen',
            'informationen_abgleichen_stellung_nehmen' => 'Informationen abgleichen und Stellung nehmen',
            'thema_praesentieren' => 'Thema präsentieren',
            'argumente_wiedergeben_stellung_nehmen' => 'Argumente wiedergeben und Stellung nehmen',
            'massnahmen_kritisieren' => 'Maßnahmen kritisieren',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moteur d'examen
    |--------------------------------------------------------------------------
    */
    'exam' => [
        // Le serveur est l'autorité : le JS n'est qu'un affichage.
        'server_authority' => true,
        // Avertissement visuel (secondes) avant la fin d'une tâche.
        'warning_seconds' => 30,
        // Tolérance de sécurité (secondes) pour les sauvegardes réseau.
        'grace_seconds' => 2,
        // Sauvegarde automatique côté client (intervalle, secondes).
        'autosave_interval' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Médias (stockage privé)
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk' => env('TESTDAF_MEDIA_DISK', 'local'),

        'audio' => [
            'max_kb' => 20480, // 20 Mo
            'mimes' => ['audio/webm', 'audio/ogg', 'audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/x-m4a'],
            'extensions' => ['webm', 'ogg', 'mp3', 'wav', 'm4a'],
        ],
        'video' => [
            'max_kb' => 102400, // 100 Mo
            'mimes' => ['video/webm', 'video/mp4', 'video/ogg', 'video/quicktime'],
            'extensions' => ['webm', 'mp4', 'ogv', 'mov'],
        ],
        'image' => [
            'max_kb' => 5120,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        ],
        'document' => [
            'max_kb' => 10240,
            'mimes' => ['application/pdf'],
            'extensions' => ['pdf'],
        ],

        // Répertoires privés (storage/app/private/...)
        'paths' => [
            'audio' => 'candidate_audio',
            'video' => 'candidate_video',
            'image' => 'candidate_images',
            'document' => 'candidate_documents',
            'content' => 'content_media',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Intelligence artificielle (fournisseurs interchangeables)
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('AI_ENABLED', false),
        'provider' => env('AI_PROVIDER', 'gemini'),
        'model' => env('AI_MODEL', 'gemini-1.5-flash'),
        'gemini_api_key' => env('GEMINI_API_KEY'),
        'gemini_endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'whisper_endpoint' => env('WHISPER_ENDPOINT'),
        'languagetool_url' => env('LANGUAGETOOL_API_URL', 'https://api.languagetool.org/v2'),
        'speechace_api_key' => env('SPEECHACE_API_KEY'),
        'max_requests' => (int) env('MAX_AI_REQUESTS', 100),
        'auto_evaluation' => env('AI_AUTO_EVALUATION', true),
        'manual_review' => env('AI_MANUAL_REVIEW', true),
        // Grille d'évaluation pédagogique indicative (jamais une note officielle TestDaF).
        'rubric_max' => 20,
        'rubric_criteria' => [
            'task_completion', 'structure', 'vocabulary', 'grammar', 'coherence',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Libellés compétences
    |--------------------------------------------------------------------------
    */
    'skills' => [
        'lesen' => 'Lesen',
        'hoeren' => 'Hören',
        'schreiben' => 'Schreiben',
        'sprechen' => 'Sprechen',
    ],
];
