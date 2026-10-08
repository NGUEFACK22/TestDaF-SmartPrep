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
        'text_input',
    ],

    /*
    |--------------------------------------------------------------------------
    | Familles d'exercices Lesen (référence pédagogique TestDaF, QCM écrit)
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
    | Intelligence artificielle (fournisseurs interchangeables)
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('AI_ENABLED', false),
        'provider' => env('AI_PROVIDER', 'gemini'),
        'model' => env('AI_MODEL', 'gemini-3.5-flash-lite'),
        'gemini_api_key' => env('GEMINI_API_KEY'),
        'gemini_endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'mistral_api_key' => env('MISTRAL_API_KEY'),
        'mistral_model' => env('MISTRAL_MODEL', 'mistral-small-latest'),
        'mistral_endpoint' => env('MISTRAL_ENDPOINT', 'https://api.mistral.ai/v1'),
        'whisper_endpoint' => env('WHISPER_ENDPOINT'),
        'languagetool_url' => env('LANGUAGETOOL_API_URL', 'https://api.languagetool.org/v2'),
        'speechace_api_key' => env('SPEECHACE_API_KEY'),
        'max_requests' => (int) env('MAX_AI_REQUESTS', 100),
        'auto_evaluation' => env('AI_AUTO_EVALUATION', true),
        'manual_review' => env('AI_MANUAL_REVIEW', true),
        // Grille d'évaluation pédagogique indicative (jamais une note officielle TestDaF).
        'rubric_max' => 20,
        'rubric' => [
            // Critères demandés au LLM par compétence (valeurs 0..rubric_max).
            // Schreiben : compréhension de consigne + argumentation (task_completion),
            // structure, cohérence, cohésion, vocabulaire, syntaxe/grammaire, reformulation.
            'schreiben' => [
                'task_completion' => 'Verstehen der Aufgabenstellung und Argumentation',
                'structure' => 'Gliederung / Aufbau',
                'coherence' => 'Kohärenz',
                'cohesion' => 'Kohäsion (Verknüpfung, Konnektoren)',
                'vocabulary' => 'Wortschatz (Vielfalt, Präzision)',
                'grammar' => 'Syntaxe und Grammatik',
                'rephrasing' => 'Reformulation (keine Wiederholungen)',
            ],
            // Sprechen : réalisation de tâche, organisation, cohérence, vocabulaire,
            // syntaxe/grammaire, fluidité, intelligibilité, pertinence.
            'sprechen' => [
                'task_completion' => 'Aufgabenerfüllung (Auftrag vollständig bearbeitet)',
                'structure' => 'Organisation (Einleitung, Hauptteil, Schluss)',
                'coherence' => 'Kohärenz des Gedankengangs',
                'vocabulary' => 'Wortschatz (Vielfalt, Präzision)',
                'grammar' => 'Syntaxe und Grammatik',
                'fluency' => 'Flüssigkeit (Tempo, Pausen angemessen)',
                'intelligibility' => 'Intelligibilität (Klarheit der Aussprache, aus dem Transkript abgeleitet)',
                'relevance' => 'Passung zum Thema und zur Situation (university-/Alltagskontext)',
            ],
        ],
        // Grille d'évaluation pédagogique indicative (jamais une note officielle TestDaF).
        'rubric_criteria' => [
            'task_completion', 'structure', 'vocabulary', 'grammar', 'coherence',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Espace Élite — Défi IA (QCM générés après 2 scores parfaits consécutifs)
    |--------------------------------------------------------------------------
    | L'IA régénère à chaque demande une partie inédite (jamais les mêmes
    | questions), calibrée sur les faiblesses analysées, dans le cadre TestDaF.
    */
    'challenge' => [
        // 2 tentatives terminées à 100 % d'affilée sur le même test.
        'consecutive_perfect' => 2,
        // Minimum absolu de questions générées par défi.
        'min_questions' => 20,
        // Numéros réservés aux tests générés (invisibles de la liste publique).
        'test_number_base' => 900,
        // Minuteur PAR QUESTION selon la difficulté (secondes, autorité serveur).
        'time_by_difficulty' => [
            'B2' => 90,
            'C1' => 120,
            'C1+' => 150,
        ],
        'default_difficulty' => 'C1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Libellés compétences
    |--------------------------------------------------------------------------
    */
    'skills' => [
        'lesen' => 'Lesen',
    ],

    /*
    |--------------------------------------------------------------------------
    | Échelle TDN (conversion pédagogique — référentiel TestDaF)
    |--------------------------------------------------------------------------
    | Chaque partie est notée séparément sur 0–20 points ; le TestDaF ne
    | produit jamais une note globale unique. Les points 0–20 sont estimés
    | ici à partir du pourcentage de bonnes réponses (indicatif — jamais
    | une note officielle TestDaF).
    |   0–4   : sous TDN 3
    |   5–9   : TDN 3
    |   10–15 : TDN 4
    |   16–20 : TDN 5
    | Objectif C1 : 16–20 points sur chaque compétence.
    */
    'tdn' => [
        'scale_max' => 20,
        'bands' => [
            ['min' => 0, 'max' => 4, 'label' => 'sous TDN 3'],
            ['min' => 5, 'max' => 9, 'label' => 'TDN 3'],
            ['min' => 10, 'max' => 15, 'label' => 'TDN 4'],
            ['min' => 16, 'max' => 20, 'label' => 'TDN 5'],
        ],
        'target' => ['min' => 16, 'max' => 20, 'label' => 'TDN 5 (C1)'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Méthodologie C1 — contenu de coaching affiché dans les espaces de
    | préparation (méthode uniquement, aucun contenu protégé TestDaF).
    |-------------------------------------------------------------------------- */
    'c1' => [
        'philosophy' => 'Objectif : apprendre à fonctionner en allemand dans un contexte universitaire '
            .'(cours, séminaires, discussions, groupes de travail, lecture de textes, traitement de graphiques, '
            .'exposés) — le TestDaF est construit autour de ces situations. Il ne s\'agit pas seulement de '
            .'« répondre correctement à des questions ».',
        'exam_rule' => 'Chaque tâche dispose de son propre temps. Vous pouvez avancer plus tôt, mais vous ne '
            .'pouvez pas revenir à une tâche déjà traitée : à l\'expiration, le passage à la tâche suivante est automatique.',
        'modes' => [
            'training' => 'Mode apprentissage : vous répondez, la correction est immédiate et expliquée, '
                .'vous pouvez refaire l\'exercice. Sans chronomètre, sans ordre imposé.',
            'exam' => 'Mode examen : timer individuel par tâche, passage immédiat après votre avance, '
                .'temps restant perdu, tâche verrouillée, aucune marche arrière.',
        ],
        'progression' => [
            'Niveau 0 — Diagnostic' => 'Un test initial identifie votre profil par compétence '
                .'(ex. Lesen 48 %, Hören 42 %, Schreiben 55 %, Sprechen 39 %).',
            'Niveau 1 — Remise à niveau B2' => 'Vocabulaire académique, grammaire, compréhension, '
                .'connecteurs, construction de phrases.',
            'Niveau 2 — Passage B2 → C1' => 'Textes complexes, implicite, reformulations (paraphrases), '
                .'argumentation, reformulation, synthèse.',
            'Niveau 3 — Entraînement par type de tâche' => 'Chaque type de tâche est travaillé séparément, '
                .'compétence par compétence (Typ 1, Typ 2, …).',
            'Niveau 4 — Entraînement chronométré' => 'Introduction progressive des vrais temps de tâche.',
            'Niveau 5 — Simulation complète' => 'Les Modelltests en conditions strictes (mode examen).',
        ],
        'skill' => [
            'lesen' => [
                'title' => 'LESEN — méthode C1',
                'competences' => [
                    'comprendre des textes complexes ;',
                    'extraire des informations implicites ;',
                    'comprendre la structure d\'un raisonnement ;',
                    'relier plusieurs informations ;',
                    'distinguer fait, argument, opinion, hypothèse et conclusion ;',
                    'reformuler avec précision.',
                ],
                'method' => [
                    'Étape 1 — Comprendre la structure : titre, thème, position de l\'auteur, paragraphes, connecteurs, opposition, cause, conséquence, exemple, conclusion.',
                    'Étape 2 — Identifier les mots clés et connecteurs : obwohl, dennoch, hingegen, allerdings, deshalb, folglich, während, einerseits/andererseits, insbesondere, daraus ergibt sich.',
                    'Étape 3 — Ne pas chercher seulement les mêmes mots : le TestDaF peut reformuler l\'information (ex. « Der Verkehr nimmt zu » → « Das Verkehrsaufkommen steigt »).',
                    'Étape 4 — Distinguer information explicite et information implicite (compétence clé au TDN 5).',
                ],
                'tip' => 'Les temps d\'exemple officiels (ex. Lückentext 4 min, Multiple Choice 15 min) sont indicatifs : '
                    .'les vrais temps sont stockés par tâche, ils varient selon le test.',
            ],
        ],
    ],
];
