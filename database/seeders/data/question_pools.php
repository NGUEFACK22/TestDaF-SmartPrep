<?php

// Pools de questions supplémentaires pour les formes dynamiques.
// Pour chaque Modelltest : 4 nouvelles questions Lesen, 3 nouvelles
// questions Hören et 4 variantes de Lückentext (paires de mots du texte).
//
// Difficulté : B2 (référence officielle), C1, C1+ (légèrement au-dessus
// du TestDaF officiel). La sélection est pondérée (B2=1, C1=3, C1+=4) :
// en moyenne, les formes tirées se situent un cran au-dessus du niveau
// officiel, conformément au positionnement C1/TDN5 de la plateforme.

return [

    1 => [
        'lesen_new' => [
            [
                'prompt' => 'Was lässt sich über den privaten Wohnungsmarkt schließen?',
                'options' => [
                    'A' => 'Die Mieten liegen dort meist höher als im Wohnheim.',
                    'B' => 'Er ist vor allem für Berufstätige attraktiv.',
                    'C' => 'Er bietet keine Zimmer in der Nähe der Universität.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Der Text stellt die deutlich niedrigere Miete im Wohnheim dem privaten Markt gegenüber.',
            ],
            [
                'prompt' => 'Welche Rolle spielt die gemeinsame Nutzung von Räumen?',
                'options' => [
                    'A' => 'Sie erklärt, warum man im Wohnheim schnell neue Leute lernt.',
                    'B' => 'Sie ist der Hauptgrund für den Lärm.',
                    'C' => 'Sie macht das Einzelzimmer überflüssig.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Kausalbezug im Text: Man lernt Leute kennen, DA Küche und Aufenthaltsräume gemeinsam genutzt werden.',
            ],
            [
                'prompt' => 'Welcher Rat steckt in der letzten Aussage des Textes?',
                'options' => [
                    'A' => 'Ruhesuchende sollten rechtzeitig ein Einzelzimmer in Anspruch nehmen.',
                    'B' => 'Alle sollten den Lärm am Wochenende akzeptieren.',
                    'C' => 'Man sollte möglichst oft das Wohnheim wechseln.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Wer Ruhe zum Lernen braucht, sollte sich deshalb rechtzeitig um ein Einzelzimmer bemühen."',
            ],
            [
                'prompt' => 'Wann ist der Lärm im Wohnheim laut Text besonders stark?',
                'options' => [
                    'A' => 'Am Wochenende.',
                    'B' => 'Am Montagmorgen.',
                    'C' => 'Gleichmäßig an allen Tagen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Der Lärm wird als möglicher Nachteil „vor allem am Wochenende" genannt.',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wie benennt Ben die Atmosphäre am Wochenende im Wohnheim?',
                'options' => [
                    'A' => 'Manchmal laut.',
                    'B' => 'Immer unangenehm.',
                    'C' => 'Völlig ruhig.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Ben sagt: „nur am Wochenende ist es manchmal laut."',
            ],
            [
                'prompt' => 'Was war für Ben neben dem Preis besonders wertvoll?',
                'options' => [
                    'A' => 'Das schnelle Knüpfen von Freundschaften.',
                    'B' => 'Die eigene Küche.',
                    'C' => 'Die direkte Nähe zum Campus.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Ben betont: „ich habe schnell Freunde gefunden."',
            ],
            [
                'prompt' => 'Welche Information erbittet Anna von Ben?',
                'options' => [
                    'A' => 'Ob er eine Wohnung in Heidelberg gefunden hat.',
                    'B' => 'Wie hoch seine Miete ist.',
                    'C' => 'Ob er ein Einzelzimmer braucht.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Erste Aussage Annas: „Hast du schon eine Wohnung in Heidelberg gefunden?"',
            ],
        ],
        'luecken' => [
            ['Studierende', 'Wohnung'],
            ['Universität', 'Wohnheim'],
            ['Miete', 'Lärm'],
            ['Ruhe', 'Einzelzimmer'],
        ],
    ],

    2 => [
        'lesen_new' => [
            [
                'prompt' => 'Was bedeutet „jederzeit wiederholen" für Studierende?',
                'options' => [
                    'A' => 'Die Aufzeichnungen sind zeitlich flexibel nutzbar.',
                    'B' => 'Vorlesungen finden nur noch online statt.',
                    'C' => 'Studierende dürfen alle Vorlesungen selbst aufnehmen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Aufgezeichnete Vorlesungen lassen sich „jederzeit wiederholen".',
            ],
            [
                'prompt' => 'Welche Schlussfolgerung ziehen die genannten Studien?',
                'options' => [
                    'A' => 'Reines Selbststudium kann die Motivation verringern.',
                    'B' => 'Online-Vorlesungen sind immer ineffizient.',
                    'C' => 'Kontakt zu Kommilitonen ist überflüssig.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Studien zeigen, dass die Motivation im Selbststudium häufig sinkt.',
            ],
            [
                'prompt' => 'Warum setzen viele Hochschulen auf Blended Learning?',
                'options' => [
                    'A' => 'Um Flexibilität und persönlichen Kontakt zu verbinden.',
                    'B' => 'Um Dozierende durch Medien zu ersetzen.',
                    'C' => 'Um die Studiengebühren zu senken.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Die Kombination aus Präsenz- und Online-Angebot gleicht die Nachteile aus.',
            ],
            [
                'prompt' => 'Für wen sind die aufgezeichneten Vorlesungen laut Text besonders praktisch?',
                'options' => [
                    'A' => 'Für Berufstätige und Eltern.',
                    'B' => 'Nur für Erstsemester.',
                    'C' => 'Für alle Dozierenden.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Der Text nennt Berufstätige und Eltern als Zielgruppe.',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wann wechseln die Vorlesungen in den Online-Betrieb?',
                'options' => [
                    'A' => 'Ab nächster Woche.',
                    'B' => 'Ab nächstem Semester.',
                    'C' => 'Nach den Prüfungen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Der Professor: „Ab nächster Woche finden die Vorlesungen online statt."',
            ],
            [
                'prompt' => 'Welchen wöchentlichen Austausch bestätigt der Professor?',
                'options' => [
                    'A' => 'Die Sprechstunde.',
                    'B' => 'Das Bibliothekstreffen.',
                    'C' => 'Den Präsenzseminarkurs.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Fragen können „in der wöchentlichen Sprechstunde und im Forum" gestellt werden.',
            ],
            [
                'prompt' => 'Wann sind die Aufzeichnungen abrufbar?',
                'options' => [
                    'A' => 'Jederzeit.',
                    'B' => 'Nur live.',
                    'C' => 'Erst nach der Prüfung.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Die Aufzeichnungen stehen Ihnen jederzeit zur Verfügung."',
            ],
        ],
        'luecken' => [
            ['Universitäten', 'Vorlesungen'],
            ['Berufstätige', 'Eltern'],
            ['Motivation', 'Kontakt'],
            ['Präsenz', 'Online'],
        ],
    ],

    3 => [
        'lesen_new' => [
            [
                'prompt' => 'Wo entstehen laut Text große Windparks?',
                'options' => [
                    'A' => 'Im Norden Deutschlands.',
                    'B' => 'In den Alpen.',
                    'C' => 'In Großstädten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Besonders im Norden entstehen große Windparks."',
            ],
            [
                'prompt' => 'Welches Problem der erneuerbaren Energien adressiert die Speichertechnik?',
                'options' => [
                    'A' => 'Die fehlende Verfügbarkeit bei Windstille.',
                    'B' => 'Die hohen Gestehungskosten.',
                    'C' => 'Die politische Kritik an Windparks.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Forscher entwickeln Speicher, um die Energie „auch bei Windstille nutzen zu können".',
            ],
            [
                'prompt' => 'Welcher Zusammenhang wird im Text hergestellt?',
                'options' => [
                    'A' => 'Bessere Speicher sollen die Wetterabhängigkeit ausgleichen.',
                    'B' => 'Windparks machen Speicher überflüssig.',
                    'C' => 'Kritiker fordern mehr Solarkraftwerke.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Die Speichermöglichkeiten reagieren direkt auf die „Abhängigkeit vom Wetter".',
            ],
            [
                'prompt' => 'Was versorgen die Windparks im Norden?',
                'options' => [
                    'A' => 'Immer mehr Haushalte.',
                    'B' => 'Nur die Industrie.',
                    'C' => 'Kernkraftwerke.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Windparks versorgen „immer mehr Haushalte".',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wann sind die neuen Speicher voraussichtlich marktreif?',
                'options' => [
                    'A' => 'In etwa drei Jahren.',
                    'B' => 'Sofort.',
                    'C' => 'Erst nach zehn Jahren.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Forscherin nennt den Zeitraum „in etwa drei Jahren".',
            ],
            [
                'prompt' => 'Welches Problem lösen die neuen Speicher nach Aussage der Forscherin?',
                'options' => [
                    'A' => 'Solarstrom ist damit auch nachts verfügbar.',
                    'B' => 'Windparks werden überflüssig.',
                    'C' => 'Die Energie wird billiger erzeugt.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Unsere neuen Speicher können Solarstrom auch nachts bereitstellen."',
            ],
            [
                'prompt' => 'Wer spricht im Dialog mit der Forscherin?',
                'options' => [
                    'A' => 'Ein Journalist.',
                    'B' => 'Ein Student.',
                    'C' => 'Ein Minister.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Gegenstimme wird als „Journalist" bezeichnet.',
            ],
        ],
        'luecken' => [
            ['Herausforderungen', 'Wissenschaft'],
            ['Windparks', 'Haushalte'],
            ['Kosten', 'Wetter'],
            ['Speichermöglichkeiten', 'Windstille'],
        ],
    ],

    4 => [
        'lesen_new' => [
            [
                'prompt' => 'Welche Wirkung soll eine ausgewogene Ernährung haben?',
                'options' => [
                    'A' => 'Sie soll die Konzentration verbessern.',
                    'B' => 'Sie senkt die Studiengebühren.',
                    'C' => 'Sie ersetzt den Sport.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Gemüse, Obst und Vollkornprodukte „soll die Konzentration verbessern".',
            ],
            [
                'prompt' => 'Warum greifen viele Studierende zu Fast Food?',
                'options' => [
                    'A' => 'Sie haben im Alltag zu wenig Zeit.',
                    'B' => 'Mensen sind zu teuer.',
                    'C' => 'Vegetarische Gerichte fehlen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„viele Studierende essen aus Zeitmangel Fast Food".',
            ],
            [
                'prompt' => 'Welche Empfehlung der Experten ist im Text enthalten?',
                'options' => [
                    'A' => 'Mahlzeiten vorab zu planen und viel Wasser zu trinken.',
                    'B' => 'Nur in der Mensa zu essen.',
                    'C' => 'Fast Food grundsätzlich zu verbieten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Experten empfehlen „Mahlzeiten zu planen und viel Wasser zu trinken".',
            ],
            [
                'prompt' => 'Welcher Trend wird an deutschen Hochschulen beschrieben?',
                'options' => [
                    'A' => 'Mehr vegetarische und vegane Mensagerichte.',
                    'B' => 'Nur noch Fleischgerichte.',
                    'C' => 'Schließung der Mensen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Viele Mensen bieten vegetarische und vegane Gerichte an.',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wie lange isst die Studentin vegetarisch?',
                'options' => [
                    'A' => 'Seit einem Jahr.',
                    'B' => 'Seit einem Monat.',
                    'C' => 'Erst seit heute.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„ich esse seit einem Jahr vegetarisch".',
            ],
            [
                'prompt' => 'Wie wirkt sich ihre Ernährung nach eigener Aussage aus?',
                'options' => [
                    'A' => 'Sie fühlt sich fitter.',
                    'B' => 'Sie schläft schlechter.',
                    'C' => 'Sie konzentriert sich schlechter.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Sie berichtet: „ich fühle mich fitter".',
            ],
            [
                'prompt' => 'Was gibt es heute in der Mensa?',
                'options' => [
                    'A' => 'Nur vegane Gerichte.',
                    'B' => 'Nur Fleischgerichte.',
                    'C' => 'Garnichts.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Erste Aussage: „Heute gibt es nur vegane Gerichte."',
            ],
        ],
        'luecken' => [
            ['Mensen', 'vegetarische'],
            ['Konzentration', 'Ernährung'],
            ['Zeitmangel', 'Fast Food'],
            ['Wasser', 'Mahlzeiten'],
        ],
    ],

    5 => [
        'lesen_new' => [
            [
                'prompt' => 'Warum fahren trotz guter Anbindung viele mit dem Auto?',
                'options' => [
                    'A' => 'Wegen der gewünschten Flexibilität.',
                    'B' => 'Weil es billiger ist.',
                    'C' => 'Weil keine Tickets existieren.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Viele fahren mit dem Auto, „weil sie flexibel sein möchten".',
            ],
            [
                'prompt' => 'Welches Ziel verfolgt die Forderung nach mehr Fahrradwegen?',
                'options' => [
                    'A' => 'Die Reduktion des Autoverkehrs.',
                    'B' => 'Der Ausbau der Parkplätze.',
                    'C' => 'Die Erhöhung der Buspreise.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Stadtplaner fordern mehr Fahrradwege und günstigere Tickets, „um den Autoverkehr zu reduzieren".',
            ],
            [
                'prompt' => 'Was charakterisiert das Semesterticket?',
                'options' => [
                    'A' => 'Es schaltet das gesamte regionale Netz frei.',
                    'B' => 'Es gilt nur in einer Stadt.',
                    'C' => 'Es ersetzt das Fahrrad.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Mit dem Semesterticket nutzen Studierende „das gesamte regionale Netz".',
            ],
            [
                'prompt' => 'Wofür nutzen Studierende laut Text Busse, Bahnen und U-Bahnen?',
                'options' => [
                    'A' => 'Für schnelle Wege ans Ziel.',
                    'B' => 'Für den Urlaub.',
                    'C' => 'Für Umzüge.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Öffentliche Verkehrsmittel „bringen Studierende schnell ans Ziel".',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Warum fährt die Linie 5 nur eingeschränkt?',
                'options' => [
                    'A' => 'Wegen Bauarbeiten.',
                    'B' => 'Wegen eines Streiks.',
                    'C' => 'Wegen Unwettern.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Durchsage nennt „Bauarbeiten" als Grund.',
            ],
            [
                'prompt' => 'Welche Umleitung wird den Fahrgästen empfohlen?',
                'options' => [
                    'A' => 'Der Umstieg in die Busse der Linie E.',
                    'B' => 'Die Nutzung der Linie 5 trotzdem.',
                    'C' => 'Der Verzicht auf die Fahrt.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Bitte steigen Sie in die Busse der Linie E um."',
            ],
            [
                'prompt' => 'Bis zu welcher Haltestelle fährt die Linie 5?',
                'options' => [
                    'A' => 'Zum Hauptbahnhof.',
                    'B' => 'Zum Zoo.',
                    'C' => 'Zum Flughafen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Linie 5 „fährt nur bis zum Hauptbahnhof".',
            ],
        ],
        'luecken' => [
            ['Studierende', 'Semesterticket'],
            ['Fahrradwege', 'Tickets'],
            ['Stadtplaner', 'Autoverkehr'],
            ['Busse', 'Bahnen'],
        ],
    ],

    6 => [
        'lesen_new' => [
            [
                'prompt' => 'Was holen sich Studierende durch ein Praktikum?',
                'options' => [
                    'A' => 'Praktische Erfahrung und Kontakte.',
                    'B' => 'Sofort eine feste Stelle.',
                    'C' => 'Ein abgeschlossenes Studium.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Studierende sammeln praktische Erfahrung und knüpfen Kontakte zu Unternehmen."',
            ],
            [
                'prompt' => 'Welche Handlung erhöht laut Text die Chancen?',
                'options' => [
                    'A' => 'Frühzeitig bewerben und die Anforderungen genau lesen.',
                    'B' => 'Erst kurz vor Semesterende bewerben.',
                    'C' => 'Nur Online-Bewerbungen ohne Anschreiben nutzen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Wichtig ist, sich frühzeitig zu bewerben und die Anforderungen genau zu lesen."',
            ],
            [
                'prompt' => 'Was sagt der Text über viele Unternehmen?',
                'options' => [
                    'A' => 'Sie stellen gute Praktikanten später fest ein.',
                    'B' => 'Sie zahlen keine Praktikanten.',
                    'C' => 'Sie schließen alle Praktikumsstellen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Viele Firmen übernehmen gute Praktikanten später als feste Mitarbeiter."',
            ],
            [
                'prompt' => 'Welche Wirkung kann ein kurzes deutschsprachiges Anschreiben haben?',
                'options' => [
                    'A' => 'Es verbessert die Chancen deutlich.',
                    'B' => 'Es ist irrelevant.',
                    'C' => 'Es verschlechtert die Chancen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Auch ein kurzes Anschreiben auf Deutsch kann die Chancen deutlich verbessern."',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'In welcher Sprache sollte das Anschreiben laut Berater sein?',
                'options' => [
                    'A' => 'Auf Deutsch.',
                    'B' => 'Nur auf Englisch.',
                    'C' => 'Sprachunabhängig.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Der Berater bestätigt: „Ja [auf Deutsch], das erhöht Ihre Chancen deutlich."',
            ],
            [
                'prompt' => 'Welche Aussage des Beraters ist korrekt?',
                'options' => [
                    'A' => 'Das deutsche Anschreiben erhöht die Chancen deutlich.',
                    'B' => 'Der Lebenslauf genügt allein.',
                    'C' => 'Praktika sind unbesoldet und uninteressant.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Der Berater betont den deutlichen Chancenplus durch das deutsche Anschreiben.',
            ],
            [
                'prompt' => 'Welche Unterlagen braucht der Student für das Praktikum?',
                'options' => [
                    'A' => 'Lebenslauf und Anschreiben.',
                    'B' => 'Nur Zeugnisse.',
                    'C' => 'Keine Dokumente.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Für ein Praktikum brauchen Sie einen Lebenslauf und ein Anschreiben."',
            ],
        ],
        'luecken' => [
            ['Praktikum', 'Karriereschritt'],
            ['Erfahrung', 'Kontakte'],
            ['Anschreiben', 'Chancen'],
            ['frühzeitig', 'Bewerber'],
        ],
    ],

    7 => [
        'lesen_new' => [
            [
                'prompt' => 'Wovor warnen die Experten im Text?',
                'options' => [
                    'A' => 'Vor Ablenkung und Falschinformationen.',
                    'B' => 'Vor dem Buchkauf.',
                    'C' => 'Vor Dozentinnen und Dozenten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Gleichzeitig warnen Experten vor Ablenkung und Falschinformationen."',
            ],
            [
                'prompt' => 'Unter welcher Bedingung können Studierende von digitalen Angeboten profitieren?',
                'options' => [
                    'A' => 'Wenn sie seriöse Quellen nutzen und Bildschirmzeit begrenzen.',
                    'B' => 'Unabhängig von ihrer Mediennutzung.',
                    'C' => 'Nur ohne Universitätskurse.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Wer seriöse Quellen nutzt und seine Bildschirmzeit begrenzt, kann … profitieren."',
            ],
            [
                'prompt' => 'Welche Funktion haben die Kurse zur Medienkompetenz?',
                'options' => [
                    'A' => 'Sie helfen, die Risiken digitaler Medien zu managen.',
                    'B' => 'Sie verkaufen Geräte.',
                    'C' => 'Sie ersetzen alle Vorlesungen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => 'Die Kurse reagieren auf die Warnungen vor Ablenkung und Falschinformationen.',
            ],
            [
                'prompt' => 'Was ergänzen Lernvideos und Online-Foren laut Text?',
                'options' => [
                    'A' => 'Viele Vorlesungen.',
                    'B' => 'Alle Prüfungen.',
                    'C' => 'Die Universitätsbibliothek.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Lernvideos und Online-Foren ergänzen heute viele Vorlesungen."',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Was verlangt die Dozentin für die Hausarbeit?',
                'options' => [
                    'A' => 'Wissenschaftliche Quellen.',
                    'B' => 'Nur Online-Videos.',
                    'C' => 'Keine Quellenangaben.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Nutzen Sie für Ihre Hausarbeit bitte wissenschaftliche Quellen."',
            ],
            [
                'prompt' => 'Wie ordnet die Dozentin Online-Videos an?',
                'options' => [
                    'A' => 'Als Ergänzung, nicht als Hauptquelle.',
                    'B' => 'Als bevorzugte Hauptquelle.',
                    'C' => 'Als grundsätzlich unzulässig.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Nur als Ergänzung, nicht als Hauptquelle."',
            ],
            [
                'prompt' => 'Welche Frage stellt der Student?',
                'options' => [
                    'A' => 'Ob Online-Videos erlaubt sind.',
                    'B' => 'Wie lange die Hausarbeit dauern darf.',
                    'C' => 'Wer die Arbeit korrigiert.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Sind Online-Videos erlaubt?"',
            ],
        ],
        'luecken' => [
            ['Lernvideos', 'Foren'],
            ['Ablenkung', 'Falschinformationen'],
            ['seriöse', 'Quellen'],
            ['Bildschirmzeit', 'profitieren'],
        ],
    ],

    8 => [
        'lesen_new' => [
            [
                'prompt' => 'Welche Vergünstigung erhalten Studierende oft?',
                'options' => [
                    'A' => 'Ermäßigte Tickets für Kulturveranstaltungen.',
                    'B' => 'Kostenlose Wohnungen.',
                    'C' => 'Gratis-Verkehrskarten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Museen, Theater und Konzerte bieten „oft ermäßigte Tickets für Studierende an".',
            ],
            [
                'prompt' => 'Welche Funktion erfüllen Sprachcafés nach dem Text?',
                'options' => [
                    'A' => 'Neue Leute kennenlernen und Deutsch üben.',
                    'B' => 'Nur Prüfungsvorbereitung.',
                    'C' => 'Formelle Vorlesungen halten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„um neue Leute kennenzulernen und Deutsch zu üben".',
            ],
            [
                'prompt' => 'Welche Aussage macht der Text über das Hochschulleben?',
                'options' => [
                    'A' => 'Aktive Teilnahme hilft, sich schneller heimisch zu fühlen.',
                    'B' => 'Freizeit ist für das Studium irrelevant.',
                    'C' => 'Sportvereine sind zu teuer.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Wer aktiv am Hochschulleben teilnimmt, fühlt sich schneller zu Hause."',
            ],
            [
                'prompt' => 'Wie sind Sportvereine laut Text meist im Preis?',
                'options' => [
                    'A' => 'Günstig.',
                    'B' => 'Sehr teuer.',
                    'C' => 'Kostenlos.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Sportvereine sind beliebt und meist günstig."',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wie kann man die ermäßigten Karten sichern?',
                'options' => [
                    'A' => 'Online reservieren.',
                    'B' => 'Nur am Schalter ab 9 Uhr.',
                    'C' => 'Per Post.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Reservieren Sie bitte online."',
            ],
            [
                'prompt' => 'Welcher Hinweis ist in der Ansage enthalten?',
                'options' => [
                    'A' => 'Die Ermäßigung gilt jeden Donnerstag.',
                    'B' => 'Die Karten sind nur sonntags gültig.',
                    'C' => 'Reservierungen sind nur telefonisch möglich.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„bietet Studierenden jeden Donnerstag ermäßigte Karten".',
            ],
            [
                'prompt' => 'Wen betrifft die Ermäßigung?',
                'options' => [
                    'A' => 'Studierende.',
                    'B' => 'Nur Professoren.',
                    'C' => 'Nur Kinder.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Die Ansage richtet sich an Studierende.',
            ],
        ],
        'luecken' => [
            ['Museen', 'Theater'],
            ['Sprachcafés', 'Deutsch'],
            ['Sportvereine', 'günstig'],
            ['Hochschulleben', 'zu Hause'],
        ],
    ],

    9 => [
        'lesen_new' => [
            [
                'prompt' => 'Woran arbeiten Studierende und Professoren gemeinsam?',
                'options' => [
                    'A' => 'An neuen Technologien wie Robotik und Medizin.',
                    'B' => 'Nur an Verwaltungsaufgaben.',
                    'C' => 'An alten Texten.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„In Laboren arbeiten Studierende und Professoren gemeinsam an neuen Technologien, zum Beispiel in der Robotik oder der Medizin."',
            ],
            [
                'prompt' => 'Welche Gruppe wird von den Förderprogrammen besonders unterstützt?',
                'options' => [
                    'A' => 'Junge Forscherinnen und Forscher.',
                    'B' => 'Ruhestandswissenschaftler.',
                    'C' => 'Nur Professoren.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Förderprogramme unterstützen besonders junge Forscherinnen und Forscher."',
            ],
            [
                'prompt' => 'Welche ökonomische Wirkung wird im Text beschrieben?',
                'options' => [
                    'A' => 'Aus wissenschaftlichen Ideen entstehen neue Arbeitsplätze.',
                    'B' => 'Forschung erzeugt keine Stellen.',
                    'C' => 'Start-ups schließen Arbeitsplätze.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„So entstehen aus wissenschaftlichen Ideen neue Arbeitsplätze."',
            ],
            [
                'prompt' => 'Wie werden deutsche Hochschulen im Text eingeordnet?',
                'options' => [
                    'A' => 'Zu den innovativsten Forschungseinrichtungen Europas.',
                    'B' => 'Als reine Lehreinrichtungen.',
                    'C' => 'Als überholt.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Deutsche Hochschulen gehören zu den innovativsten Forschungseinrichtungen Europas."',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Bis wann sind Bewerbungen möglich?',
                'options' => [
                    'A' => 'Bis Freitag.',
                    'B' => 'Bis zum 1. Mai.',
                    'C' => 'Ohne Frist.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Bewerbungen bitte bis Freitag per E-Mail."',
            ],
            [
                'prompt' => 'Wen sucht das Labor?',
                'options' => [
                    'A' => 'Studentische Hilfskräfte.',
                    'B' => 'Dauerpersonal für die Mensa.',
                    'C' => 'Externe Berater.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Unser Labor sucht studentische Hilfskräfte für ein Robotikprojekt."',
            ],
            [
                'prompt' => 'Über welchen Weg laufen die Bewerbungen?',
                'options' => [
                    'A' => 'Per E-Mail.',
                    'B' => 'Per Post.',
                    'C' => 'Mündlich im Sekretariat.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Bewerbungen bitte … per E-Mail."',
            ],
        ],
        'luecken' => [
            ['Robotik', 'Medizin'],
            ['Förderprogramme', 'Forscherinnen'],
            ['Start-up', 'Arbeitsplätze'],
            ['Laboren', 'Professoren'],
        ],
    ],

    10 => [
        'lesen_new' => [
            [
                'prompt' => 'Was erleichtern Beratungsstellen und Mentorenprogramme?',
                'options' => [
                    'A' => 'Das Überwinden von Sprachbarrieren und Heimweh.',
                    'B' => 'Die Prüfungsvorbereitung.',
                    'C' => 'Die Wohnungssuche.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => 'Sie helfen, „diese Schwierigkeiten zu überwinden" (Sprachbarrieren, Heimweh).',
            ],
            [
                'prompt' => 'Welche Haltung fördert laut Text das Finden von Freundschaften?',
                'options' => [
                    'A' => 'Offen auf andere zuzugehen.',
                    'B' => 'Sich bewusst zurückzuziehen.',
                    'C' => 'Nur der eigenen Herkunftsgemeinschaft anzugehören.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Wer offen auf andere zugeht, findet schnell Freunde aus aller Welt."',
            ],
            [
                'prompt' => 'Welche Funktion haben internationale Abende und gemeinsame Projekte?',
                'options' => [
                    'A' => 'Sie fördern den Austausch zwischen Studierenden.',
                    'B' => 'Sie ersetzen Vorlesungen.',
                    'C' => 'Sie sind nur für Dozierende.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„Internationale Abende, Tandemprogramme und gemeinsame Projekte fördern den Austausch."',
            ],
            [
                'prompt' => 'Was erleben manche internationale Studierende?',
                'options' => [
                    'A' => 'Sprachbarrieren oder Heimweh.',
                    'B' => 'Keine Schwierigkeiten.',
                    'C' => 'Nur Prüfungsstress.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Trotzdem erleben manche Studierende Sprachbarrieren oder Heimweh."',
            ],
        ],
        'hoeren_new' => [
            [
                'prompt' => 'Wie oft sollen sich die Tandempartner treffen?',
                'options' => [
                    'A' => 'Wöchentlich.',
                    'B' => 'Monatlich.',
                    'C' => 'Nur zur Prüfung.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Treffen Sie sich wöchentlich…".',
            ],
            [
                'prompt' => 'Was empfiehlt die Mentorin?',
                'options' => [
                    'A' => 'Beide Sprachen zu üben.',
                    'B' => 'Nur Deutsch zu sprechen.',
                    'C' => 'Keine festen Treffen zu planen.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1+',
                'explanation' => '„…und üben Sie beide Sprachen."',
            ],
            [
                'prompt' => 'Wer wird im Tandemprogramm verbunden?',
                'options' => [
                    'A' => 'Deutsche und internationale Studierende.',
                    'B' => 'Nur Dozierende.',
                    'C' => 'Nur Alumni.',
                ],
                'correct' => ['A'],
                'difficulty' => 'C1',
                'explanation' => '„Unser Tandemprogramm verbindet deutsche und internationale Studierende."',
            ],
        ],
        'luecken' => [
            ['Tandemprogramme', 'Austausch'],
            ['Sprachbarrieren', 'Heimweh'],
            ['Beratungsstellen', 'Mentorenprogramme'],
            ['offen', 'Freunde'],
        ],
    ],
];