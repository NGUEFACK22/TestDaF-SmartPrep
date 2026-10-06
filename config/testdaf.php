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
        'hoeren' => 'Hören',
        'schreiben' => 'Schreiben',
        'sprechen' => 'Sprechen',
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
    | Sprechen — objectifs de temps de parole par type de tâche
    |--------------------------------------------------------------------------
    | Référentiel du TestDaF digital (temps indicatifs de production orale).
    | Le candidat dispose par ailleurs d'un temps de préparation qui fait
    | partie du déroulement de chaque tâche.
    */
    'sprechen' => [
        'prep_seconds_default' => 60,
        'targets' => [
            'rat_geben' => 45,
            'optionen_abwaegen' => 90,
            'text_zusammenfassen' => 120,
            'informationen_abgleichen_stellung_nehmen' => 90,
            'thema_praesentieren' => 150,
            'argumente_wiedergeben_stellung_nehmen' => 120,
            'massnahmen_kritisieren' => 90,
        ],
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
            'hoeren' => [
                'title' => 'HÖREN — méthode C1',
                'competences' => [
                    'suivre une discussion ;',
                    'identifier l\'idée principale ;',
                    'repérer les détails importants ;',
                    'distinguer important / secondaire ;',
                    'comprendre une intention et une information implicite ;',
                    'suivre la structure d\'un exposé ;',
                    'prendre rapidement des notes.',
                ],
                'method' => [
                    'Avant l\'écoute : lire rapidement les éléments visibles de la tâche.',
                    'Pendant l\'écoute : ne pas essayer de traduire chaque mot.',
                    'Chercher : Wer ? Was ? Warum ? Wie ? Welche Folge ? Welche Position ? Welche Meinung ?',
                    'Pour le C1 : comprendre le raisonnement, pas seulement reconnaître des mots.',
                ],
                'tip' => 'Dans « Fehler in Zusammenfassung erkennen », comparez ce qui est dit avec le résumé écrit : '
                    .'chaque écart (faux, omis, ajouté) compte.',
            ],
            'schreiben' => [
                'title' => 'SCHREIBEN — méthode C1',
                'tasks' => [
                    'Aufgabe 1 — Argumentativen Text schreiben : minimum 200 mots.',
                    'Aufgabe 2 — Informationen aus Lesetext und Grafik zusammenfassen : environ 100–150 mots.',
                ],
                'structure' => [
                    'Introduction : présenter le problème.',
                    'Position : exprimer clairement son point de vue.',
                    'Argument 1 + Argument 2 : argument, explication, exemple.',
                    'Contre-argument : présenter une autre position.',
                    'Réfutation / nuance : montrer pourquoi sa propre position reste défendable.',
                    'Conclusion : résumer sans simplement répéter l\'introduction.',
                ],
                'prefer' => [
                    'Aus meiner Sicht …',
                    'Es lässt sich argumentieren, dass …',
                    'Ein wesentlicher Vorteil besteht darin, dass …',
                    'Demgegenüber ist zu berücksichtigen, dass …',
                ],
                'avoid' => [
                    'Répéter « Ich denke … » : privilégier une expression différenciée.',
                    'Recopier les données brutes du graphique : il faut les comparer, les sélectionner et les reformuler.',
                ],
            ],
            'sprechen' => [
                'title' => 'SPRECHEN — méthode C1',
                'competences' => [
                    'parler clairement + logiquement + précisément + naturellement (l\'objectif n\'est pas de parler vite) ;',
                    'adapter son discours à la situation universitaire ;',
                    'structurer un discours en temps limité.',
                ],
                'structure' => [
                    'Introduction',
                    'Idée principale',
                    'Argument',
                    'Explication',
                    'Exemple',
                    'Nuance / comparaison',
                    'Conclusion',
                ],
                'connectors' => [
                    'zunächst',
                    'darüber hinaus',
                    'einerseits … andererseits',
                    'allerdings',
                    'dennoch',
                    'hingegen',
                    'folglich',
                    'daher',
                    'aus diesem Grund',
                    'abschließend',
                ],
                'evaluation' => [
                    'L\'effet global : le discours est-il fluide, clair, compréhensible, correctement structuré ?',
                    'La réalisation de la tâche : répond-on vraiment au sujet, tous les éléments demandés sont-ils traités, la situation est-elle respectée ?',
                    'Les moyens linguistiques : registre, vocabulaire, syntaxe, adéquation des formulations, erreurs, facilité de compréhension.',
                ],
            ],
        ],
    ],
];
