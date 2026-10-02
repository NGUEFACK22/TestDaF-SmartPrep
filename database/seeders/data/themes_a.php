<?php

// Thèmes 1 à 5 — contenus originaux (textes, consignes, corrigés).
return [

    [
        'title' => 'Modelltest 1 — Wohnen und Studium',
        'theme' => 'Wohnen',
        'lesen' => [
            'text' => 'Viele internationale Studierende suchen zu Beginn ihres Studiums eine günstige Wohnung in der Nähe der Universität. Besonders beliebt sind Studentenwohnheime, weil die Miete dort oft deutlich niedriger ist als auf dem privaten Wohnungsmarkt. Außerdem lernt man in einem Wohnheim schnell neue Leute kennen, da Küche und Aufenthaltsräume gemeinsam genutzt werden. Ein Nachteil kann jedoch der Lärm sein, vor allem am Wochenende. Wer Ruhe zum Lernen braucht, sollte sich deshalb rechtzeitig um ein Einzelzimmer bemühen.',
            'questions' => [
                [
                    'prompt' => 'Warum sind Studentenwohnheime bei internationalen Studierenden beliebt?',
                    'options' => [
                        'A' => 'Weil sie besonders ruhig sind.',
                        'B' => 'Weil die Miete dort oft niedriger ist.',
                        'C' => 'Weil jedes Zimmer eine eigene Küche hat.',
                    ],
                    'correct' => ['B'],
                    'explanation' => 'Der Text nennt die niedrigere Miete als Hauptgrund.',
                ],
                [
                    'prompt' => 'Was wird als Nachteil des Wohnheims genannt?',
                    'options' => [
                        'A' => 'Der Lärm am Wochenende.',
                        'B' => 'Die hohen Kosten.',
                        'C' => 'Die große Entfernung zur Universität.',
                    ],
                    'correct' => ['A'],
                    'explanation' => 'Der Text erwähnt den Lärm als möglichen Nachteil.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Anna: Hast du schon eine Wohnung in Heidelberg gefunden? Ben: Ja, ich wohne jetzt in einem Wohnheim. Es ist günstig und ich habe schnell Freunde gefunden. Anna: Ist es nicht zu laut? Ben: Unter der Woche ist es ruhig, nur am Wochenende ist es manchmal laut.',
            'questions' => [
                [
                    'prompt' => 'Wo wohnt Ben?',
                    'options' => [
                        'A' => 'Bei seinen Eltern.',
                        'B' => 'In einem Studentenwohnheim.',
                        'C' => 'In einer eigenen Wohnung.',
                    ],
                    'correct' => ['B'],
                    'explanation' => 'Ben sagt, dass er in einem Wohnheim wohnt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die wichtigsten Informationen des Lesetextes zum studentischen Wohnen zusammen und nehmen Sie Stellung: Ist das Wohnen im Wohnheim für internationale Studierende empfehlenswert? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Studentisches Wohnen in Deutschland: Rund 60 % der internationalen Studierenden wohnen in Wohnheimen. Vorteile sind die niedrigen Kosten und die sozialen Kontakte. Nachteile sind Lärm und wenig Privatsphäre.',
            'graphic' => 'Umfrage: 60 % Wohnheim, 25 % Wohngemeinschaft, 15 % eigene Wohnung.',
        ],
        'sprechen' => [
            [
                'title' => 'Aufgabe 1 — Rat geben',
                'instruction' => 'Ein Freund möchte in Deutschland studieren und sucht eine Wohnung. Geben Sie ihm einen Rat. Sprechen Sie ca. 60 Sekunden.',
                'preparation' => 60,
                'recording' => 90,
                'duration' => 180,
            ],
            [
                'title' => 'Aufgabe 2 — Thema präsentieren',
                'instruction' => 'Präsentieren Sie das Thema „Wohnen als Student“. Beschreiben Sie Vor- und Nachteile und geben Sie Ihre eigene Meinung. Sprechen Sie ca. 90 Sekunden.',
                'preparation' => 90,
                'recording' => 120,
                'duration' => 240,
            ],
        ],
    ],

    [
        'title' => 'Modelltest 2 — Digitale Universität',
        'theme' => 'Digitalisierung',
        'lesen' => [
            'text' => 'Immer mehr Universitäten bieten digitale Lehrveranstaltungen an. Vorlesungen werden aufgezeichnet, sodass Studierende sie jederzeit wiederholen können. Das ist besonders für Berufstätige und Eltern praktisch. Allerdings fehlt vielen der direkte Kontakt zu Dozierenden und Kommilitonen. Studien zeigen, dass die Motivation im Selbststudium häufig sinkt. Deshalb kombinieren viele Hochschulen heute Präsenz- und Online-Angebote, das sogenannte Blended Learning.',
            'questions' => [
                [
                    'prompt' => 'Für wen sind aufgezeichnete Vorlesungen besonders praktisch?',
                    'options' => [
                        'A' => 'Für Berufstätige und Eltern.',
                        'B' => 'Für alle Dozierenden.',
                        'C' => 'Nur für Erstsemester.',
                    ],
                    'correct' => ['A'],
                    'explanation' => 'Der Text nennt Berufstätige und Eltern als Beispiel.',
                ],
                [
                    'prompt' => 'Was ist ein Problem des reinen Online-Studiums?',
                    'options' => [
                        'A' => 'Die hohen Kosten.',
                        'B' => 'Der fehlende direkte Kontakt und die sinkende Motivation.',
                        'C' => 'Die fehlende Technik.',
                    ],
                    'correct' => ['B'],
                    'explanation' => 'Kontaktmangel und Motivationsverlust werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Professor: Ab nächster Woche finden die Vorlesungen online statt. Die Aufzeichnungen stehen Ihnen jederzeit zur Verfügung. Studentin: Können wir auch Fragen stellen? Professor: Ja, in der wöchentlichen Sprechstunde und im Forum.',
            'questions' => [
                [
                    'prompt' => 'Wo können Fragen gestellt werden?',
                    'options' => [
                        'A' => 'Nur in der Vorlesung.',
                        'B' => 'In der Sprechstunde und im Forum.',
                        'C' => 'Gar nicht.',
                    ],
                    'correct' => ['B'],
                    'explanation' => 'Der Professor nennt Sprechstunde und Forum.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Argumente des Textes zum digitalen Studium zusammen und diskutieren Sie: Sollte das Studium künftig überwiegend online stattfinden? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Digitales Studium: Online-Vorlesungen sind flexibel und jederzeit abrufbar. Nachteile sind fehlender Kontakt und geringere Motivation. Viele Hochschulen setzen auf Blended Learning.',
            'graphic' => 'Umfrage: 55 % bevorzugen eine Kombination, 25 % reine Präsenz, 20 % reines Online-Studium.',
        ],
        'sprechen' => [
            [
                'title' => 'Aufgabe 1 — Rat geben',
                'instruction' => 'Eine Freundin beginnt ein Online-Studium. Geben Sie ihr Tipps für erfolgreiches Lernen. Sprechen Sie ca. 60 Sekunden.',
                'preparation' => 60,
                'recording' => 90,
                'duration' => 180,
            ],
            [
                'title' => 'Aufgabe 2 — Thema präsentieren',
                'instruction' => 'Präsentieren Sie das Thema „Studieren im Internet“. Sprechen Sie ca. 90 Sekunden.',
                'preparation' => 90,
                'recording' => 120,
                'duration' => 240,
            ],
        ],
    ],

    [
        'title' => 'Modelltest 3 — Klima und Forschung',
        'theme' => 'Umwelt',
        'lesen' => [
            'text' => 'Der Klimawandel stellt die Wissenschaft vor große Herausforderungen. Deutsche Universitäten forschen intensiv an erneuerbaren Energien wie Wind- und Solarenergie. Besonders im Norden entstehen große Windparks, die immer mehr Haushalte versorgen. Kritiker weisen jedoch auf die hohen Kosten und die Abhängigkeit vom Wetter hin. Forscher arbeiten deshalb an besseren Speichermöglichkeiten, um die Energie auch bei Windstille nutzen zu können.',
            'questions' => [
                [
                    'prompt' => 'Woran forschen deutsche Universitäten besonders intensiv?',
                    'options' => ['A' => 'An fossilen Brennstoffen.', 'B' => 'An erneuerbaren Energien.', 'C' => 'An Kernenergie.'],
                    'correct' => ['B'],
                    'explanation' => 'Wind- und Solarenergie werden als Schwerpunkt genannt.',
                ],
                [
                    'prompt' => 'Was kritisieren Kritiker?',
                    'options' => ['A' => 'Die hohen Kosten und die Wetterabhängigkeit.', 'B' => 'Die fehlenden Windparks.', 'C' => 'Die zu geringe Forschung.'],
                    'correct' => ['A'],
                    'explanation' => 'Kosten und Wetterabhängigkeit werden genannt.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Forscherin: Unsere neuen Speicher können Solarstrom auch nachts bereitstellen. Journalist: Wann sind sie marktreif? Forscherin: In etwa drei Jahren.',
            'questions' => [
                [
                    'prompt' => 'Was können die neuen Speicher?',
                    'options' => ['A' => 'Solarstrom auch nachts bereitstellen.', 'B' => 'Windparks ersetzen.', 'C' => 'Kosten senken ohne Speicherung.'],
                    'correct' => ['A'],
                    'explanation' => 'Die nächtliche Verfügbarkeit wird genannt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zu erneuerbaren Energien zusammen und nehmen Sie Stellung: Sollte Deutschland vollständig auf erneuerbare Energien setzen? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Erneuerbare Energien versorgen immer mehr Haushalte. Probleme sind Kosten, Wetterabhängigkeit und Speicherung.',
            'graphic' => 'Energiemix: 45 % erneuerbar, 30 % Kohle/Gas, 25 % sonstige.',
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Optionen abwägen', 'instruction' => 'Fahrrad oder öffentlicher Verkehr? Wägen Sie die Optionen ab. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Klimaschutz an Universitäten“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 4 — Gesundheit und Ernährung',
        'theme' => 'Gesundheit',
        'lesen' => [
            'text' => 'Gesunde Ernährung spielt an deutschen Hochschulen eine immer größere Rolle. Viele Mensen bieten vegetarische und vegane Gerichte an. Eine ausgewogene Ernährung mit Gemüse, Obst und Vollkornprodukten soll die Konzentration verbessern. Doch viele Studierende essen aus Zeitmangel Fast Food. Experten empfehlen, Mahlzeiten zu planen und viel Wasser zu trinken.',
            'questions' => [
                [
                    'prompt' => 'Was bieten viele Mensen an?',
                    'options' => ['A' => 'Nur Fast Food.', 'B' => 'Vegetarische und vegane Gerichte.', 'C' => 'Nur Fleischgerichte.'],
                    'correct' => ['B'],
                    'explanation' => 'Vegetarische und vegane Angebote werden genannt.',
                ],
                [
                    'prompt' => 'Was empfehlen Experten?',
                    'options' => ['A' => 'Mahlzeiten zu planen und Wasser zu trinken.', 'B' => 'Nur abends zu essen.', 'C' => 'Auf Gemüse zu verzichten.'],
                    'correct' => ['A'],
                    'explanation' => 'Planung und Wasser werden empfohlen.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Student: Heute gibt es nur vegane Gerichte. Studentin: Gut, ich esse seit einem Jahr vegetarisch und fühle mich fitter.',
            'questions' => [
                [
                    'prompt' => 'Wie ernährt sich die Studentin?',
                    'options' => ['A' => 'Vegetarisch.', 'B' => 'Nur mit Fast Food.', 'C' => 'Ohne Gemüse.'],
                    'correct' => ['A'],
                    'explanation' => 'Sie isst vegetarisch.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zur Ernährung zusammen und diskutieren Sie: Sollten Mensen nur gesunde Gerichte anbieten? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Mensen bieten mehr vegetarische Gerichte an. Gesunde Ernährung verbessert die Konzentration. Viele essen aus Zeitmangel Fast Food.',
            'graphic' => 'Befragung: 50 % regelmäßig in der Mensa, 30 % gelegentlich, 20 % nie.',
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Rat geben', 'instruction' => 'Ein Kommilitone ernährt sich ungesund. Geben Sie ihm einen Rat. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Argumente wiedergeben', 'instruction' => 'Geben Sie Argumente für und gegen vegetarische Mensen wieder und nehmen Sie Stellung. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

    [
        'title' => 'Modelltest 5 — Mobilität in der Stadt',
        'theme' => 'Verkehr',
        'lesen' => [
            'text' => 'In vielen Städten wird der öffentliche Verkehr immer wichtiger. Busse, Bahnen und U-Bahnen bringen Studierende schnell ans Ziel. Mit dem Semesterticket nutzen sie oft das gesamte regionale Netz. Trotzdem fahren viele mit dem Auto, weil sie flexibel sein möchten. Stadtplaner fordern mehr Fahrradwege und günstigere Tickets, um den Autoverkehr zu reduzieren.',
            'questions' => [
                [
                    'prompt' => 'Was ermöglicht das Semesterticket?',
                    'options' => ['A' => 'Die Nutzung des regionalen Netzes.', 'B' => 'Kostenlose Taxifahrten.', 'C' => 'Nur eine Buslinie.'],
                    'correct' => ['A'],
                    'explanation' => 'Das regionale Netz wird als Vorteil genannt.',
                ],
                [
                    'prompt' => 'Was fordern Stadtplaner?',
                    'options' => ['A' => 'Mehr Parkplätze.', 'B' => 'Mehr Fahrradwege und günstigere Tickets.', 'C' => 'Ein Busverbot.'],
                    'correct' => ['B'],
                    'explanation' => 'Fahrradwege und Tickets werden gefordert.',
                ],
            ],
        ],
        'hoeren' => [
            'transcript' => 'Durchsage: Wegen Bauarbeiten fährt die Linie 5 nur bis zum Hauptbahnhof. Bitte steigen Sie in die Busse der Linie E um.',
            'questions' => [
                [
                    'prompt' => 'Was müssen die Fahrgäste tun?',
                    'options' => ['A' => 'In die Linie E umsteigen.', 'B' => 'Zu Fuß gehen.', 'C' => 'Warten.'],
                    'correct' => ['A'],
                    'explanation' => 'Der Umstieg wird angesagt.',
                ],
            ],
        ],
        'schreiben' => [
            'instruction' => 'Fassen Sie die Informationen zum Stadtverkehr zusammen und nehmen Sie Stellung: Sollte der Autoverkehr in Innenstädten verboten werden? Schreiben Sie ca. 180 Wörter.',
            'source' => 'Der öffentliche Verkehr ist schnell und günstig. Viele fahren dennoch Auto. Gefordert werden mehr Fahrradwege und billigere Tickets.',
            'graphic' => 'Verkehrsmittel: 40 % ÖPNV, 30 % Fahrrad, 20 % Auto, 10 % zu Fuß.',
        ],
        'sprechen' => [
            ['title' => 'Aufgabe 1 — Maßnahmen kritisieren', 'instruction' => 'Ihre Stadt plant höhere Parkgebühren. Kritisieren oder verteidigen Sie die Maßnahme. Sprechen Sie ca. 60 Sekunden.', 'preparation' => 60, 'recording' => 90, 'duration' => 180],
            ['title' => 'Aufgabe 2 — Thema präsentieren', 'instruction' => 'Präsentieren Sie das Thema „Mobilität der Zukunft“. Sprechen Sie ca. 90 Sekunden.', 'preparation' => 90, 'recording' => 120, 'duration' => 240],
        ],
    ],

];
