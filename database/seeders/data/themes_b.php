<?php

// Thèmes 6 à 10 — contenus originaux (textes, consignes, corrigés).
return [

    [
        'title' => 'Modelltest 6 — Arbeit und Praktikum',
        'theme' => 'Beruf',
        'lesen' => [
            'text' => 'Ein Praktikum während des Studiums gilt in Deutschland als wichtiger Karriereschritt. Studierende sammeln praktische Erfahrung und knüpfen Kontakte zu Unternehmen. Viele Firmen übernehmen gute Praktikanten später als feste Mitarbeiter. Wichtig ist, sich frühzeitig zu bewerben und die Anforderungen genau zu lesen. Auch ein kurzes Anschreiben auf Deutsch kann die Chancen deutlich verbessern.',
            'questions' => [
                [
                    'prompt' => 'Warum ist ein Praktikum wichtig?',
                    'options' => ['A' => 'Man sammelt Erfahrung und Kontakte.', 'B' => 'Man verdient sofort sehr viel.', 'C' => 'Man muss nicht mehr studieren.'],
                    'correct' => ['A'],
                    'explanation' => 'Erfahrung und Kontakte werden genannt.',
                ],
                [
                    'prompt' => 'Was verbessert die Chancen?',
                    'options' => ['A' => 'Eine späte Bewerbung.', 'B' => 'Ein Anschreiben auf Deutsch.', 'C' => 'Keine Bewerbung.'],
                    'correct' => ['B'],
                    'explanation' => 'Das Anschreiben wird empfohlen.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Berater: Für ein Praktikum brauchen Sie einen Lebenslauf und ein Anschreiben. Student: Muss das Anschreiben auf Deutsch sein? Berater: Ja, das erhöht Ihre Chancen deutlich.',
            'questions' => [
                [
                    'prompt' => 'Was braucht man für ein Praktikum?',
                    'options' => ['A' => 'Lebenslauf und Anschreiben.', 'B' => 'Nur einen Personalausweis.', 'C' => 'Nichts.'],
                    'correct' => ['A'],
                    'explanation' => 'Beide Dokumente werden genannt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zum Praktikum zusammen und diskutieren Sie: Sollte ein Praktikum für alle Studiengänge Pflicht sein? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Praktika bieten Erfahrung und Kontakte. Viele Firmen übernehmen Praktikanten. Wichtig sind frühe Bewerbung und ein deutsches Anschreiben.',
            'graphic' => 'Übernahmequote: 40 % der Praktikanten erhalten ein Angebot.',
            'chart' => ['title' => 'Übergang nach dem Praktikum', 'labels' => ['erhalten ein Arbeitsangebot', 'kein Angebot'], 'values' => [40, 60]],
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Rat geben', 'instruction' => 'Ein Freund sucht ein Praktikum. Geben Sie ihm Tipps. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Informationen abgleichen', 'instruction' => 'Vergleichen Sie zwei Praktikumsangebote und nehmen Sie Stellung. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 7 — Medien und Gesellschaft',
        'theme' => 'Medien',
        'lesen' => [
            'text' => 'Soziale Medien verändern die Art, wie Studierende lernen und kommunizieren. Lernvideos und Online-Foren ergänzen heute viele Vorlesungen. Gleichzeitig warnen Experten vor Ablenkung und Falschinformationen. Wer seriöse Quellen nutzt und seine Bildschirmzeit begrenzt, kann von den digitalen Angeboten profitieren. Universitäten bieten deshalb Kurse zur Medienkompetenz an.',
            'questions' => [
                [
                    'prompt' => 'Wie ergänzen soziale Medien das Studium?',
                    'options' => ['A' => 'Durch Lernvideos und Foren.', 'B' => 'Sie ersetzen alle Vorlesungen.', 'C' => 'Sie sind verboten.'],
                    'correct' => ['A'],
                    'explanation' => 'Videos und Foren werden genannt.',
                ],
                [
                    'prompt' => 'Wovor warnen Experten?',
                    'options' => ['A' => 'Vor Ablenkung und Falschinformationen.', 'B' => 'Vor Büchern.', 'C' => 'Vor Professoren.'],
                    'correct' => ['A'],
                    'explanation' => 'Ablenkung und Falschinformationen werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Dozentin: Nutzen Sie für Ihre Hausarbeit bitte wissenschaftliche Quellen. Student: Sind Online-Videos erlaubt? Dozentin: Nur als Ergänzung, nicht als Hauptquelle.',
            'questions' => [
                [
                    'prompt' => 'Was ist bei der Hausarbeit erlaubt?',
                    'options' => ['A' => 'Videos nur als Ergänzung.', 'B' => 'Nur Videos.', 'C' => 'Keine Quellen.'],
                    'correct' => ['A'],
                    'explanation' => 'Videos sind nur Ergänzung.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zu sozialen Medien im Studium zusammen und diskutieren Sie: Fördern soziale Medien das Lernen? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Lernvideos und Foren ergänzen Vorlesungen. Risiken sind Ablenkung und Falschinformationen. Medienkompetenz wird empfohlen.',
            'graphic' => 'Nutzung: 70 % Lernvideos, 50 % Foren, 30 % Podcasts.',
            'chart' => ['title' => 'Mediennutzung zum Lernen', 'labels' => ['Lernvideos', 'Foren', 'Podcasts'], 'values' => [70, 50, 30]],
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Text zusammenfassen', 'instruction' => 'Fassen Sie einen kurzen Artikel über Medienkompetenz zusammen. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Soziale Medien im Studium“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 8 — Kultur und Freizeit',
        'theme' => 'Kultur',
        'lesen' => [
            'text' => 'Das kulturelle Angebot an deutschen Universitätsstädten ist vielfältig. Museen, Theater und Konzerte bieten oft ermäßigte Tickets für Studierende an. Viele besuchen in ihrer Freizeit Sprachcafés, um neue Leute kennenzulernen und Deutsch zu üben. Auch Sportvereine sind beliebt und meist günstig. Wer aktiv am Hochschulleben teilnimmt, fühlt sich schneller zu Hause.',
            'questions' => [
                [
                    'prompt' => 'Was bieten Kulturinstitutionen oft an?',
                    'options' => ['A' => 'Ermäßigte Tickets für Studierende.', 'B' => 'Kostenlose Autos.', 'C' => 'Nur teure Tickets.'],
                    'correct' => ['A'],
                    'explanation' => 'Ermäßigungen werden genannt.',
                ],
                [
                    'prompt' => 'Wozu dienen Sprachcafés?',
                    'options' => ['A' => 'Zum Kennenlernen und Deutschüben.', 'B' => 'Nur zum Kaffeetrinken.', 'C' => 'Zum Arbeiten.'],
                    'correct' => ['A'],
                    'explanation' => 'Kontakte und Sprache werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Anrufbeantworter: Das Theater am Park bietet Studierenden jeden Donnerstag ermäßigte Karten. Reservieren Sie bitte online.',
            'questions' => [
                [
                    'prompt' => 'Wann gibt es ermäßigte Karten?',
                    'options' => ['A' => 'Jeden Donnerstag.', 'B' => 'Nur sonntags.', 'C' => 'Nie.'],
                    'correct' => ['A'],
                    'explanation' => 'Donnerstag wird genannt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zum Kulturangebot zusammen und nehmen Sie Stellung: Ist Kultur wichtig für internationale Studierende? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Museen und Theater bieten Ermäßigungen. Sprachcafés und Sportvereine fördern Kontakte und Sprache.',
            'graphic' => 'Freizeit: 45 % Sport, 30 % Kultur, 25 % Sonstiges.',
            'chart' => ['title' => 'Freizeitaktivitäten von Studierenden', 'labels' => ['Sport', 'Kultur', 'sonstiges'], 'values' => [45, 30, 25]],
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Rat geben', 'instruction' => 'Ein neuer Student kennt niemanden. Geben Sie ihm Freizeittipps. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Kultur in meiner Stadt“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 9 — Wissenschaft und Innovation',
        'theme' => 'Forschung',
        'lesen' => [
            'text' => 'Deutsche Hochschulen gehören zu den innovativsten Forschungseinrichtungen Europas. In Laboren arbeiten Studierende und Professoren gemeinsam an neuen Technologien, zum Beispiel in der Robotik oder der Medizin. Förderprogramme unterstützen besonders junge Forscherinnen und Forscher. Ein erfolgreiches Projekt kann später als Start-up weitergeführt werden. So entstehen aus wissenschaftlichen Ideen neue Arbeitsplätze.',
            'questions' => [
                [
                    'prompt' => 'Woran arbeiten Hochschulteams gemeinsam?',
                    'options' => ['A' => 'An neuen Technologien wie Robotik.', 'B' => 'Nur an alten Büchern.', 'C' => 'An nichts.'],
                    'correct' => ['A'],
                    'explanation' => 'Robotik und Medizin werden genannt.',
                ],
                [
                    'prompt' => 'Was kann aus einem Projekt entstehen?',
                    'options' => ['A' => 'Ein Start-up mit Arbeitsplätzen.', 'B' => 'Nichts.', 'C' => 'Nur eine Note.'],
                    'correct' => ['A'],
                    'explanation' => 'Start-ups und Arbeitsplätze werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Professor: Unser Labor sucht studentische Hilfskräfte für ein Robotikprojekt. Bewerbungen bitte bis Freitag per E-Mail.',
            'questions' => [
                [
                    'prompt' => 'Wofür werden Hilfskräfte gesucht?',
                    'options' => ['A' => 'Für ein Robotikprojekt.', 'B' => 'Für die Mensa.', 'C' => 'Für den Sport.'],
                    'correct' => ['A'],
                    'explanation' => 'Das Robotikprojekt wird genannt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zur Hochschulforschung zusammen und diskutieren Sie: Sollten Studierende früh forschen? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Hochschulen forschen innovativ. Förderprogramme unterstützen junge Forschende. Aus Projekten entstehen Start-ups und Arbeitsplätze.',
            'graphic' => 'Drittmittel: 55 % Staat, 30 % Wirtschaft, 15 % Sonstige.',
            'chart' => ['title' => 'Drittmittel der Hochschulforschung', 'labels' => ['Staat', 'Wirtschaft', 'sonstige'], 'values' => [55, 30, 15]],
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Rat geben', 'instruction' => 'Eine Freundin möchte an einem Forschungsprojekt teilnehmen. Geben Sie ihr einen Rat. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Forschung an Universitäten“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 10 — Zusammenleben und Integration',
        'theme' => 'Gesellschaft',
        'lesen' => [
            'text' => 'Das Zusammenleben von Menschen aus verschiedenen Ländern bereichert den Universitätsalltag. Internationale Abende, Tandemprogramme und gemeinsame Projekte fördern den Austausch. Trotzdem erleben manche Studierende Sprachbarrieren oder Heimweh. Beratungsstellen und Mentorenprogramme helfen, diese Schwierigkeiten zu überwinden. Wer offen auf andere zugeht, findet schnell Freunde aus aller Welt.',
            'questions' => [
                [
                    'prompt' => 'Was fördert den Austausch?',
                    'options' => ['A' => 'Internationale Abende und Tandemprogramme.', 'B' => 'Isolation.', 'C' => 'Nur Prüfungen.'],
                    'correct' => ['A'],
                    'explanation' => 'Abende und Tandems werden genannt.',
                ],
                [
                    'prompt' => 'Wer hilft bei Schwierigkeiten?',
                    'options' => ['A' => 'Beratungsstellen und Mentoren.', 'B' => 'Niemand.', 'C' => 'Nur Freunde.'],
                    'correct' => ['A'],
                    'explanation' => 'Beratung und Mentoren werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Mentorin: Unser Tandemprogramm verbindet deutsche und internationale Studierende. Treffen Sie sich wöchentlich und üben Sie beide Sprachen.',
            'questions' => [
                [
                    'prompt' => 'Was verbindet das Tandemprogramm?',
                    'options' => ['A' => 'Deutsche und internationale Studierende.', 'B' => 'Nur Professoren.', 'C' => 'Nur E-Mails.'],
                    'correct' => ['A'],
                    'explanation' => 'Beide Gruppen werden genannt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zum interkulturellen Zusammenleben zusammen und nehmen Sie Stellung: Fördert das Studium im Ausland die Persönlichkeit? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Internationale Programme fördern Austausch. Sprachbarrieren und Heimweh sind Herausforderungen. Beratung und Mentoren helfen.',
            'graphic' => 'Herkunft: 40 % Europa, 35 % Asien, 15 % Afrika, 10 % Amerika.',
            'chart' => ['title' => 'Herkunft internationaler Studierender', 'labels' => ['Europa', 'Asien', 'Afrika', 'Amerika'], 'values' => [40, 35, 15, 10]],
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Rat geben', 'instruction' => 'Ein neuer internationaler Student hat Heimweh. Geben Sie ihm einen Rat. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Leben in zwei Kulturen“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

];
