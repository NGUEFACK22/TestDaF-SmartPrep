<?php

/**
 * Contenu pédagogique des 7 démos officielles du TestDaF digital (Hörverstehen).
 *
 * Chaque entrée décrit un type de tâche officiel (structure pédagogique publique,
 * matériel du TestDaF Institute) : consigne officielle (DE), description (FR),
 * structure, durée indicative calibrée sur le fichier de démo, phase de
 * vérification (lorsqu'elle est documentée), conseils C1.
 *
 * Fichiers démo : content/media/hoeren_demo/ — importés par HoerenDemoSeeder
 * (disque privé, meta.public = true). Durées « ≈ » : indicatives.
 */
return [

    // Ressources externes communes (liens officiels vérifiés).
    'resources' => [
        ['name' => 'TestDaF digital — Aufbau der Prüfung (structure officielle des sections)', 'url' => 'https://www.testdaf.de/de/teilnehmende/der-digitale-testdaf/aufbau-des-digitalen-testdaf/'],
        ['name' => 'TestDaF digital — Beispielaufgaben Hören (PDF de démo officiel)', 'url' => 'https://www.testdaf.de/fileadmin/testdaf/downloads/Demo_Version_digitaler_TestDaF/Beispielaufgaben_Demo-Version_digitaler_TestDaF_Hoeren.pdf'],
        ['name' => 'TestDaF digital — Trainingsmaterialien (préparation officielle)', 'url' => 'https://www.testdaf.de/de/teilnehmende/der-digitale-testdaf/pruefungsvorbereitung/trainingsmaterialien/'],
        ['name' => 'ÖSD — Zertifikat C1 (ZeC1) : page de l\'examen (Musterprüfung + Audios, téléchargement libre)', 'url' => 'https://www.osd.at/die-pruefungen/osd-prufungen/oesd-zertifikat-c1-zc1/'],
        ['name' => 'CIB — Modelltest C1 avec audios (entraînement C1 Hören)', 'url' => 'https://www.cib.or.at/modelltest-c1'],
        ['name' => 'telc Deutsch C1 Hochschule — page officielle (Musterprüfungen, version 1 : MP3 gratuites)', 'url' => 'https://www.telc.net/sprachpruefungen/zertifikatspruefung/deutsch/telc-deutsch-c1-hochschule/'],
        ['name' => 'Goethe-Zertifikat C1 — Modellsatz (Hören : PDF + MP3)', 'url' => 'https://www.goethe.de/pro/relaunch/prf/materialien/C1/c1_modellsatz.pdf'],
    ],

    'demos' => [
        [
            'number' => 1,
            'type' => 'kurzantwort_uebersicht_ergaenzen',
            'media_file' => 'hoeren_demo_typ1.mp3',
            'media_type' => 'audio',
            'mime' => 'audio/mpeg',
            'original_name' => 'Hoeren_Aufgabentyp_1_Demoversion.mp3',
            'title' => 'Démo officielle — Aufgabe 1 : Übersicht ergänzen',
            'title_de' => 'Kurzantwort – Übersicht ergänzen',
            'official_instruction' =>
                "Aufgabe 1 von 7\n"
                . "Sie hören eine Übersicht. In der Übersicht fehlen verschiedene Textstellen. "
                . "Ergänzen Sie die Übersicht mit den fehlenden Textstellen (Kurzantwort : 1 bis 5 Wörter). "
                . "Sie hören den Text einmal.",
            'fr_description' =>
                'Une fiche (Übersicht) résumant le sujet contient des trous : noms, chiffres ou notions '
                . 'manquants. Écoutez le texte une seule fois et complétez chaque trou par une réponse courte '
                . '(1 à 5 mots), reprise du texte.',
            'structure' => [
                'Une Übersicht (fiche / tableau / plan) avec des lacunes à compléter',
                'Écoute unique du texte (pas de répétition)',
                'Réponses courtes : 1 à 5 mots ou une expression courte',
                'Phase de vérification après l\'écoute',
            ],
            'duration_hint' => '≈ 3 min (fichier de démo)',
            'control_time' => null,
            'duration_seconds' => 240,
            'tips' => [
                'Avant l\'écoute : lisez la Übersicht et identifiez le type d\'information attendu dans chaque trou (nom, chiffre, lieu, notion).',
                'La réponse reprend presque toujours les mots du texte : attendez le groupe nominal correspondant.',
                'Si vous hésitez, écrivez phonétiquement : vous corrigez pendant la phase de vérification.',
                'Niveau C1 : le texte reformule souvent — repérez le mot-clé, pas la formulation exacte.',
            ],
        ],
        [
            'number' => 2,
            'type' => 'kurzantwort_textstellen_begriffspaar',
            'media_file' => 'hoeren_demo_typ2.mp3',
            'media_type' => 'audio',
            'mime' => 'audio/mpeg',
            'original_name' => 'Hoeren_Aufgabentyp_2_Demoversion.mp3',
            'title' => 'Démo officielle — Aufgabe 2 : Textstellen zu Begriffspaar notieren',
            'title_de' => 'Kurzantwort – Textstellen zu Begriffspaar notieren',
            'official_instruction' =>
                "Aufgabe 2 von 7\n"
                . "Sie hören einen Text. Im Text kommt ein Begriffspaar vor : zu beiden Begriffen werden Textstellen genannt. "
                . "Notieren Sie, was im Text zu den beiden Begriffen gesagt wird. Notieren Sie für jeden Begriff eine oder zwei Textstellen. "
                . "Sie hören den Text einmal.",
            'fr_description' =>
                'Le texte met en relation deux notions opposées ou complémentaires (Begriffspaar). '
                . 'Écoutez et notez, pour chaque notion, les passages (Textstellen) qui lui sont associés — en mots-clés du texte.',
            'structure' => [
                'Un texte centré sur un Begriffspaar (deux notions opposées ou complémentaires)',
                'Écoute unique du texte',
                'Une ou deux Textstellen par notion (mots-clés, pas de phrases complètes)',
                'Phase de vérification après l\'écoute',
            ],
            'duration_hint' => '≈ 3,5 min (fichier de démo)',
            'control_time' => null,
            'duration_seconds' => 300,
            'tips' => [
                'Repérez d\'abord le Begriffspaar dans la consigne : qui sont les deux pôles ?',
                'Surveillez les connecteurs de contraste (jedoch, während, im Gegenteil, aber) : ils signalent le changement de pôle.',
                'Notez le mot-clé, pas la phrase entière — la consigne attend des Textstellen, pas une transcription.',
                'Une même information peut toucher les deux pôles : relisez mentalement avant d\'attribuer.',
            ],
        ],
        [
            'number' => 3,
            'type' => 'fehler_in_zusammenfassung_erkennen',
            'media_file' => 'hoeren_demo_typ3.mp3',
            'media_type' => 'audio',
            'mime' => 'audio/mpeg',
            'original_name' => 'Hoeren_Aufgabentyp_3_Demoversion.mp3',
            'title' => 'Démo officielle — Aufgabe 3 : Fehler in Zusammenfassung erkennen',
            'title_de' => 'Fehler in Zusammenfassung erkennen',
            'official_instruction' =>
                "Aufgabe 3 von 7\n"
                . "Sie lesen eine Zusammenfassung des Textes mit vier Aussagen. Zwei der Aussagen enthalten Fehler. "
                . "Hören Sie den Text und markieren Sie die beiden Aussagen mit Fehlern. "
                . "Sie hören den Text einmal. Nach dem Hören haben Sie 2 Minuten und 30 Sekunden Zeit, um Ihre Antworten zu überprüfen.",
            'fr_description' =>
                'Une synthèse écrite (4 énoncés) du texte est présentée avant l\'écoute. Deux énoncés contiennent '
                . 'une erreur par rapport au texte. Écoutez et cochez les deux énoncés erronés.',
            'structure' => [
                'Lecture d\'une Zusammenfassung (4 énoncés) AVANT l\'écoute',
                'Écoute unique du texte',
                'Marquer les 2 énoncés contenant une erreur',
                'Phase de vérification officielle : 2 min 30 s',
            ],
            'duration_hint' => '≈ 4,5 min (fichier de démo) + 2 min 30 s de vérification',
            'control_time' => '2 min 30 s',
            'duration_seconds' => 420,
            'tips' => [
                'Pièges classiques : contraire, nombre changé, personnes ou lieux inversés, causalité retournée, information ajoutée ou omise.',
                'Avant l\'écoute, lisez chaque énoncé et repérez son point de comparaison (qui ? combien ? quand ? pourquoi ?).',
                'Ne cochez qu\'une erreur certaine : vous devez en marquer exactement deux.',
                'La phase de vérification (2 min 30 s) sert à relire vos deux choix — utilisez-la activement.',
            ],
        ],
        [
            'number' => 4,
            'type' => 'aussagen_personen_zuordnen',
            'media_file' => 'hoeren_demo_typ4.mp4',
            'media_type' => 'video',
            'mime' => 'video/mp4',
            'original_name' => 'Hoeren_Aufgabentyp_4_Video.mp4',
            'title' => 'Démo officielle — Aufgabe 4 : Aussagen Personen zuordnen',
            'title_de' => 'Aussagen Personen zuordnen',
            'official_instruction' =>
                "Aufgabe 4 von 7\n"
                . "Sie sehen und hören eine Präsentation. Es werden mehrere Aussagen gemacht. "
                . "Ordnen Sie jede Aussage der Person zu, die diese Aussage macht. "
                . "Sie sehen und hören das Video einmal. Nach dem Video haben Sie 45 Sekunden Zeit, um Ihre Antworten zu überprüfen.",
            'fr_description' =>
                'Vous regardez et écoutez une présentation (vidéo). Plusieurs énoncés (Aussagen) sont listés : '
                . 'associez chacun à la personne qui le formule.',
            'structure' => [
                'Vidéo (présentation / dialogue) — visionnage unique',
                'Liste d\'Aussagen (énoncés) à attribuer',
                'Associer chaque énoncé à la personne qui le formule',
                'Phase de vérification officielle : 45 s',
            ],
            'duration_hint' => '≈ 5–7 min (vidéo de démo) + 45 s de vérification',
            'control_time' => '45 s',
            'duration_seconds' => 540,
            'tips' => [
                'Avant le visionnage : lisez les énoncés et repérez leurs mots-clés — vous reconnaîtrez l\'énoncé dans le flux.',
                'Identifiez d\'abord qui parle (présentateur, intervenants, rôles) : le déroulé commence par les intervenants.',
                'Une même personne peut formuler plusieurs énoncés ; d\'autres n\'en formulent aucun.',
                'L\'ordre des énoncés suit souvent l\'ordre du discours : en cas de doute, restez dans l\'ordre entendu.',
            ],
        ],
        [
            'number' => 5,
            'type' => 'kurzantwort_gliederungspunkte',
            'media_file' => 'hoeren_demo_typ5.mp4',
            'media_type' => 'video',
            'mime' => 'video/mp4',
            'original_name' => 'Hoeren_Aufgabentyp_5_Video.mp4',
            'title' => 'Démo officielle — Aufgabe 5 : Gliederungspunkte zu Vortrag ergänzen',
            'title_de' => 'Kurzantwort – Gliederungspunkte zu Vortrag ergänzen',
            'official_instruction' =>
                "Aufgabe 5 von 7\n"
                . "Sie sehen und hören einen Vortrag. Die Gliederung des Vortrags ist unvollständig : es fehlen mehrere Gliederungspunkte. "
                . "Ergänzen Sie die Gliederung mit den fehlenden Gliederungspunkten (Kurzantwort). "
                . "Sie sehen und hören den Vortrag einmal.",
            'fr_description' =>
                'Le plan (Gliederung) d\'un exposé vidéo est incomplet. Écoutez et regardez le Vortrag, '
                . 'puis complétez le plan avec les points manquants (réponses courtes).',
            'structure' => [
                'Un plan (Gliederung) avec des points manquants',
                'Visionnage + écoute uniques du Vortrag (vidéo)',
                'Compléter chaque point manquant (1 à 5 mots)',
                'Phase de vérification après le visionnage',
            ],
            'duration_hint' => '≈ 4–6 min (vidéo de démo)',
            'control_time' => null,
            'duration_seconds' => 480,
            'tips' => [
                'Suivez la structure du discours : introduction, points (Erstens, Zweitens, Drittens…), conclusion.',
                'Le locuteur annonce souvent ses points : captez les connecteurs de progression (zunächst, außerdem, abschließend).',
                'Le Gliederungspunkt reprend le concept du point, pas la phrase entière.',
                'Sur ce type de tâche, la structure visuelle (diapositives) aide — gardez l\'œil sur le plan affiché.',
            ],
        ],
        [
            'number' => 6,
            'type' => 'multiple_choice',
            'media_file' => 'hoeren_demo_typ6.mp3',
            'media_type' => 'audio',
            'mime' => 'audio/mpeg',
            'original_name' => 'Hoeren_Aufgabentyp_6_Demoversion.mp3',
            'title' => 'Démo officielle — Aufgabe 6 : Multiple Choice',
            'title_de' => 'Multiple Choice',
            'official_instruction' =>
                "Aufgabe 6 von 7\n"
                . "Sie hören einen Text. Beantworten Sie die Fragen : Wählen Sie die richtige Antwort aus. "
                . "Sie hören den Text einmal.",
            'fr_description' =>
                'Écoutez un texte et répondez à plusieurs questions à choix multiples '
                . '(3 à 4 options par question) en sélectionnant l\'option correcte.',
            'structure' => [
                'Un texte audio (écouter une seule fois)',
                'Plusieurs questions fermées',
                'Choisir parmi 3 à 4 options par question',
                'Phase de vérification après l\'écoute',
            ],
            'duration_hint' => '≈ 4 min (fichier de démo)',
            'control_time' => null,
            'duration_seconds' => 360,
            'tips' => [
                'Lisez toutes les options AVANT l\'écoute : reformulez mentalement ce que chaque option implique.',
                'L\'option correcte est presque toujours reformulée par rapport au texte (synonymes, paraphrase).',
                'Piège classique : une option reprend des mots du texte avec un sens légèrement différent.',
                'Éliminez d\'abord les options fausses : c\'est plus fiable que de chercher la bonne.',
            ],
        ],
        [
            'number' => 7,
            'type' => 'laut_und_schriftbild_abgleichen',
            'media_file' => 'hoeren_demo_typ7.mp3',
            'media_type' => 'audio',
            'mime' => 'audio/mpeg',
            'original_name' => 'Hoeren_Aufgabentyp_7_Demoversion.mp3',
            'title' => 'Démo officielle — Aufgabe 7 : Laut- und Schriftbild abgleichen',
            'title_de' => 'Laut- und Schriftbild abgleichen',
            'official_instruction' =>
                "Aufgabe 7 von 7\n"
                . "Sie sehen einzelne Wörter und Wortgruppen. Hören Sie, wie die Wörter und Wortgruppen ausgesprochen werden. "
                . "Entscheiden Sie, ob das Lautbild zum Schriftbild passt. "
                . "Nach dem Hören haben Sie 20 Sekunden Zeit, um Ihre Antworten zu überprüfen.",
            'fr_description' =>
                'Des items (mots ou groupes de mots) sont affichés à l\'écran. Chaque item est lu à voix haute : '
                . 'décidez si la prononciation (Lautbild) correspond à l\'orthographe (Schriftbild) affichée.',
            'structure' => [
                'Items affichés à l\'écrit, lus un à un à voix haute',
                'Pour chaque item : la prononciation correspond-elle à l\'orthographe affichée ?',
                'Série d\'items courts (écoute rapide)',
                'Phase de vérification officielle : 20 s',
            ],
            'duration_hint' => '≈ 1 min (fichier de démo) + 20 s de vérification',
            'control_time' => '20 s',
            'duration_seconds' => 150,
            'tips' => [
                'Prononciez mentalement l\'item affiché AVANT de l\'entendre : vous créez votre référence d\'écoute.',
                'Vigilance sur les homophones et fausses amies orthographiques (ex. : « das » / « dass », « vor » / « vor- »).',
                'Ne vous fiez pas à la première impression : la tâche porte sur le détail phonétique.',
                'Cette tâche valorise l\'oreille fine (C1) : entraînez-vous avec des listes de mots proches orthographiquement.',
            ],
        ],
    ],
];