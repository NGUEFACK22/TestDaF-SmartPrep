<?php

/**
 * Questions officielles des 7 démos Hörverstehen (TestDaF digital).
 *
 * Source : PDF officiel « Beispielaufgaben aus der Demo-Version des digitalen
 * TestDaF — Prüfungsteil Hören » (g.a.s.t. / TestDaF-Institut, 2020) : tâches,
 * items et solutions officielles correspondant aux 7 fichiers de démo publics
 * (content/media/hoeren_demo/). Réponses modèle en allemand (langue de la
 * tâche), explications en français.
 *
 * Sémantique de correction = moteur d'examen (ScoringService) :
 * - single_choice   : QCM à une seule bonne réponse (libellé a/b/c/d) ;
 * - multiple_choice : QCM multi-sélection (équivalence d'ensembles de libellés) ;
 * - data.segments   (T7) : texte avec mots-candidats cliquables (adaptation :
 *                     8 candidats au lieu de tous les mots du texte).
 *
 * Les tâches T1/T2/T5 (officiellement « Kurzantwort ») sont proposées en QCM
 * (norme TestDaF digital : correction objective et automatique) ; la bonne
 * réponse de chaque QCM = une solution officielle du PDF, les options
 * (distracteurs) sont tirées du même contexte audio pour rester fidèles à
 * la logique de la tâche originale.
 */
return [

    // ------------------------------------------------------------------
    // Démo 1 — QCM (adapté de « Kurzantwort: Übersicht ergänzen »)
    // (« Jobmesse für Ingenieure » : 5 QCM, une seule bonne réponse chacun)
    // Solutions officielles : Workshops, öffentlichen Dienst, USB-Stick,
    // Raum 5, Vortrag.
    // ------------------------------------------------------------------
    1 => [
        [
            'type' => 'single_choice',
            'prompt' => 'Feld 1 — Die Anmeldung ist erforderlich für …',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — (die) Workshops'],
            'explanation' => "La foire elle-même est ouverte (1re édition) ; seuls les « Workshops » ont un nombre limité de places — il faut s'y inscrire.",
            'options' => [
                ['label' => 'a', 'text' => 'die gesamte Jobmesse'],
                ['label' => 'b', 'text' => 'die Workshops'],
                ['label' => 'c', 'text' => 'die Messehalle'],
                ['label' => 'd', 'text' => 'das Mittagessen'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Feld 2 — Die Präsentation am Dienstagvormittag trägt den Titel: „Karriere im …“',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — öffentlichen Dienst'],
            'explanation' => "Sofie empfiehlt die Präsentation über Karrieremöglichkeiten im öffentlichen Dienst (Dienstagvormittag).",
            'options' => [
                ['label' => 'a', 'text' => 'öffentlichen Dienst'],
                ['label' => 'b', 'text' => 'Fahrzeugbau'],
                ['label' => 'c', 'text' => 'Robotik'],
                ['label' => 'd', 'text' => 'Energieversorgung'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Feld 3 — Für die Workshops „Programmieren für Ingenieure“ müssen die Teilnehmenden … mitbringen.',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — USB-Stick'],
            'explanation' => "Avec un USB-Stick, les participants emportent les fichiers d'exercice de la séance.",
            'options' => [
                ['label' => 'a', 'text' => 'ihre Bewerbungsfotos'],
                ['label' => 'b', 'text' => 'einen Auszug aus dem Lebenslauf'],
                ['label' => 'c', 'text' => 'einen USB-Stick'],
                ['label' => 'd', 'text' => 'eine aktuelle Zeitungsanzeige'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Feld 4 — Die Workshops „Programmieren für Ingenieure“ finden in … statt.',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Raum 5'],
            'explanation' => 'Le workshop a lieu le mercredi matin dans la salle 5 du bâtiment C.',
            'options' => [
                ['label' => 'a', 'text' => 'Raum 25'],
                ['label' => 'b', 'text' => 'Raum 5'],
                ['label' => 'c', 'text' => 'Hörsaal 3'],
                ['label' => 'd', 'text' => 'der Messehalle'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Feld 5 — Am Dienstag steht zum Thema „Berufe in der Energieversorgung“ … auf dem Programm.',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — Vortrag'],
            'explanation' => "Mardi, il y a une conférence (« Vortrag ») sur les métiers de l'approvisionnement énergétique.",
            'options' => [
                ['label' => 'a', 'text' => 'eine Diskussion'],
                ['label' => 'b', 'text' => 'ein Workshop'],
                ['label' => 'c', 'text' => 'ein Vortrag'],
                ['label' => 'd', 'text' => 'eine Bewerbungsfotos-Präsentation'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 2 — QCM (adapté de « Kurzantwort: Textstellen zu Begriffspaar
    // notieren ») (Podiumsdiskussion « Schulnoten » : Forderung/Argument)
    // Solutions officielles : Jansson — Abschaffung + Studie Israel ;
    // Kruse — Kombination mit Kommentaren + Überblick/motivierend.
    // ------------------------------------------------------------------
    2 => [
        [
            'type' => 'single_choice',
            'prompt' => 'Frau Jansson — Welche Forderung stellt sie auf?',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Schulnoten abschaffen / durch Gespräche oder Berichte ersetzen'],
            'explanation' => 'Jansson veut abolir les notes : elles doivent être remplacées par des entretiens de développement ou des rapports écrits.',
            'options' => [
                ['label' => 'a', 'text' => 'Schulnoten sollten mit Kommentaren kombiniert werden.'],
                ['label' => 'b', 'text' => 'Schulnoten sollten abgeschafft und durch Gespräche oder Berichte ersetzt werden.'],
                ['label' => 'c', 'text' => 'Schulnoten sollten bei Studentinnen und Studenten abgeschafft werden.'],
                ['label' => 'd', 'text' => 'Schulnoten sollten nur noch mündlich erteilt werden.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frau Jansson — Welches Argument führt sie an?',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Studie aus Israel: Noten wirken demotivierend'],
            'explanation' => "Ses argument : une étude (Israël) montre que les notes démotivent ; les élèves qui n'ont reçu que des retours voulaient faire plus d'exercices.",
            'options' => [
                ['label' => 'a', 'text' => 'Noten geben einen schnellen Überblick über die Leistung von Schülerinnen und Schülern.'],
                ['label' => 'b', 'text' => 'Eine Studie aus Israel zeigt, dass Noten demotivierend wirken.'],
                ['label' => 'c', 'text' => 'Noten sind leicht zu verstehen und motivierender als Berichte.'],
                ['label' => 'd', 'text' => 'Noten sind ein wichtiger Anreiz für gute Leistungen an der Universität.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Herr Kruse — Welche Forderung stellt er auf?',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Noten sollten nicht alleinstehen, sondern mit Kommentaren verbunden werden'],
            'explanation' => "Kruse garde la note chiffrée, mais veut toujours l'associer à des commentaires qui montrent les erreurs et les points à améliorer.",
            'options' => [
                ['label' => 'a', 'text' => 'Schulnoten sollten abgeschafft werden.'],
                ['label' => 'b', 'text' => 'Schulnoten sollten nicht alleinstehen, sondern immer mit Kommentaren verbunden werden.'],
                ['label' => 'c', 'text' => 'Noten sollten durch schriftliche Berichte über die Leistungen ersetzt werden.'],
                ['label' => 'd', 'text' => 'Schülerinnen und Schüler sollten ohne jede Bewertung auskommen.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Herr Kruse — Welches Argument führt er an?',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — Die Note gibt einen schnellen Überblick über die Leistungsentwicklung'],
            'explanation' => 'La note donne un aperçu rapide et clair de la progression — un retour plus motivant.',
            'options' => [
                ['label' => 'a', 'text' => 'Noten wirken oft demotivierend auf Schülerinnen und Schüler.'],
                ['label' => 'b', 'text' => 'Gleiche Leistungen erhalten oft unterschiedliche Noten.'],
                ['label' => 'c', 'text' => 'Die Note zeigt auf einen Blick, ob sich die Leistung verbessert oder verschlechtert hat — einfach zu verstehen und motivierend.'],
                ['label' => 'd', 'text' => 'Ohne Noten verbessern sich die Schüler schneller in ihren Schwachbereichen.'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 3 — Fehler in Zusammenfassung erkennen
    // (« Die Wiederbelebung schrumpfender Dörfer » : 2 fausses parmi 7)
    // Solutions officielles : Sätze 1 und 5.
    // ------------------------------------------------------------------
    3 => [
        [
            'type' => 'multiple_choice',
            'prompt' => 'Markieren Sie die ZWEI Sätze in der Zusammenfassung, die falsch sind (exakt zwei auswählen).',
            'correct_answer' => ['1', '5'],
            'data' => [],
            'explanation' => 'Satz 1 est faux : selon le texte audio, plus de la moitié des Allemands vivent dans de petites communes rurales (et non « nur noch wenige »). Satz 5 est faux : selon le texte audio, une forte densité associative attire les nouveaux arrivants.',
            'options' => [
                ['label' => '1', 'text' => 'Nur noch wenige Menschen in Deutschland wohnen in kleinen Orten auf dem Land, und immer mehr ziehen von dort weg.'],
                ['label' => '2', 'text' => 'Von den Menschen, die noch in Dörfern wohnen, pendeln viele jeden Tag zum Arbeitsplatz in die Stadt.'],
                ['label' => '3', 'text' => 'Wissenschaftler untersuchen, welche Faktoren das Leben auf dem Land wieder attraktiver machen könnten.'],
                ['label' => '4', 'text' => 'Als wichtiger Faktor gilt die Dorfgemeinschaft, deren Engagement sich oft in den ortsansässigen Vereinen zeigt.'],
                ['label' => '5', 'text' => 'Wenn es in einem Dorf viele Vereine gibt, ist es allerdings für neu zugezogene Personen schwieriger, in die Dorfgemeinschaft aufgenommen zu werden.'],
                ['label' => '6', 'text' => 'Um in Dörfern Arbeitsplätze zu schaffen, muss der Internetzugang verbessert werden.'],
                ['label' => '7', 'text' => 'Auch neue Logistikkonzepte werden entwickelt, weil sich in Dörfern die herkömmliche Personen- oder Paketbeförderung oft nicht lohnt.'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 4 — Aussagen Personen zuordnen
    // (Video « Zoos » : Herr Ebert / Frau Kottmeier / beide / keiner)
    // Solutions officielles : 1 c, 2 d, 3 b, 4 a, 5 b, 6 c.
    // ------------------------------------------------------------------
    4 => [
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Der Staat trägt eine Verantwortung für das Wohl von Tieren.“',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — beide'],
            'explanation' => "Les deux intervenants défendent la responsabilité de l'État envers le bien-être des animaux.",
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Es sollte mehr Gesetze zum Schutz von Tieren geben.“',
            'correct_answer' => 'd',
            'data' => ['model' => 'd — keiner'],
            'explanation' => 'Aucun des deux ne réclame explicitement de nouvelles lois de protection des animaux.',
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Der Staat sollte eine ‚In-situ-Arterhaltung‘ finanziell unterstützen.“',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Frau Kottmeier'],
            'explanation' => "C'est la position de Frau Kottmeier : soutenir financièrement la conservation des espèces dans leur milieu naturel.",
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Zoos leisten einen großen Beitrag zur Erhaltung bedrohter Tierarten.“',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — Herr Ebert'],
            'explanation' => "C'est l'argument de Herr Ebert, qui défend la contribution des zoos à la conservation des espèces menacées.",
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Tiere in Zoos verhalten sich aufgrund einer nicht artgerechten Haltung unnatürlich.“',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — Frau Kottmeier'],
            'explanation' => "C'est la critique formulée par Frau Kottmeier (elle s'appuie sur des données de recherche sur les zoos).",
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Zu wem passt die Aussage: „Diese Person stützt ihre Meinung auf wissenschaftliche Erkenntnisse.“',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — beide'],
            'explanation' => "Les deux s'appuient sur des études : Kottmeier sur une étude de conservation, Ebert sur des examens physiologiques hormonaux menés sur les dauphins.",
            'options' => [
                ['label' => 'a', 'text' => 'Herr Ebert'],
                ['label' => 'b', 'text' => 'Frau Kottmeier'],
                ['label' => 'c', 'text' => 'beide'],
                ['label' => 'd', 'text' => 'keiner'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 5 — QCM (adapté de « Kurzantwort: Gliederungspunkte zu Vortrag
    // ergänzen ») (Vortrag « Gesichtserkennung / Gesichtszellen »)
    // Solutions officielles : Merkmal — besonders aktiv bei Gesichtern ;
    // früher — eine Zelle erkennt ein ganzes Gesicht ; neu — nur ein Teil/
    // ein Aspekt ; Algorithmus — berechnen, wie das Gesicht aussah.
    // ------------------------------------------------------------------
    5 => [
        [
            'type' => 'single_choice',
            'prompt' => 'Merkmal von Gesichtszellen: …',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — (sie) sind besonders aktiv bei der Betrachtung von Gesichtern'],
            'explanation' => "Les « Gesichtszellen » (lobe temporal) sont très actives lors de l'observation de visages, et pas pour d'autres objets.",
            'options' => [
                ['label' => 'a', 'text' => 'sie sind besonders aktiv bei der Betrachtung von Gesichtern.'],
                ['label' => 'b', 'text' => 'sie reagieren am stärksten auf Alltagsgegenstände wie Tassen und Schuhe.'],
                ['label' => 'c', 'text' => 'sie befinden sich im Frontallappen des Gehirns.'],
                ['label' => 'd', 'text' => 'sie sind bisher nur bei Affen nachgewiesen worden.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frühere, widerlegte Annahme: …',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — (man ging davon aus, dass) jede Gesichtszelle ein ganzes Gesicht erkennt'],
            'explanation' => "On pensait jusqu'à récemment qu'une seule cellule faciale codait le visage entier.",
            'options' => [
                ['label' => 'a', 'text' => 'jede Gesichtszelle erkennt nur einen Teil des Gesichts.'],
                ['label' => 'b', 'text' => 'jede Gesichtszelle erkennt ein ganzes Gesicht.'],
                ['label' => 'c', 'text' => 'Gesichtszellen erkennen Gesichter nur im Halbdunkel.'],
                ['label' => 'd', 'text' => 'das Erkennen von Gesichtern ist allein den Menschen vorbehalten.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Neue Erkenntnis aus den Forschungsergebnissen: …',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — jede Gesichtszelle erkennt nur einen Teil / einen bestimmten Aspekt des Gesichts'],
            'explanation' => "Chaque cellule ne capture qu'un aspect / une partie (une information spatiale) du visage.",
            'options' => [
                ['label' => 'a', 'text' => 'jede Gesichtszelle erkennt nur einen bestimmten Aspekt (eine räumliche Information) des Gesichts.'],
                ['label' => 'b', 'text' => 'Gesichtszellen erkennen Gesichter erst, wenn viele von ihnen zusammenarbeiten.'],
                ['label' => 'c', 'text' => 'die Gesichtszellen sitzen im Occipitallappen des Gehirns.'],
                ['label' => 'd', 'text' => 'das Gehirn speichert Gesichter vor allem im Stirnlappen.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Aufgabe des Computer-Algorithmus in der Studie: …',
            'correct_answer' => 'b',
            'data' => ['model' => 'b — berechnen bzw. rekonstruieren, wie das Gesicht aussah, das der Affe gesehen hatte'],
            'explanation' => "L'algorithme recalcule, à partir de l'activité neuronale mesurée, le visage que le singe avait regardé.",
            'options' => [
                ['label' => 'a', 'text' => 'die Aktivität der Gesichtszellen im Ohr des Affen zu messen.'],
                ['label' => 'b', 'text' => 'berechnen bzw. rekonstruieren, wie das Gesicht aussah, das der Affe gesehen hatte.'],
                ['label' => 'c', 'text' => 'die Anzahl der Gesichtszellen im Temporallappen zu zählen.'],
                ['label' => 'd', 'text' => 'festzustellen, welche Gesichter der Affe im Traum sah.'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 6 — Multiple Choice (Vortrag « Mehrsprachigkeit: Mythen und
    // Wirklichkeit » : 5 questions, 4 options chacune)
    // Solutions officielles : 1 c, 2 c, 3 a, 4 a, 5 c.
    // ------------------------------------------------------------------
    6 => [
        [
            'type' => 'single_choice',
            'prompt' => 'Frage 1: Mehrsprachigkeit ist laut der Sprecherin …',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — oft mit falschen Vorstellungen verbunden'],
            'explanation' => 'Selon la conférencière, le multilinguisme est entouré de fausses représentations — le texte traite précisément des « Mythen und Wirklichkeit ».',
            'options' => [
                ['label' => 'a', 'text' => 'besonders in Deutschland stark verbreitet.'],
                ['label' => 'b', 'text' => 'das Lernen mehrerer Sprachen von Geburt an.'],
                ['label' => 'c', 'text' => 'oft mit falschen Vorstellungen verbunden.'],
                ['label' => 'd', 'text' => 'weltweit nur wenig verbreitet.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frage 2: Ältere Menschen in Deutschland sprechen häufig …',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — weniger Sprachen als der weltweite Durchschnitt'],
            'explanation' => 'En Allemagne, une partie de la population âgée vit pratiquement en monolingue (la « Reinform einsprachig ») — donc moins de langues que la moyenne mondiale.',
            'options' => [
                ['label' => 'a', 'text' => 'mehrere Sprachen mittelmäßig gut.'],
                ['label' => 'b', 'text' => 'mehrere Sprachen wie eine Muttersprache.'],
                ['label' => 'c', 'text' => 'weniger Sprachen als der weltweite Durchschnitt.'],
                ['label' => 'd', 'text' => 'weniger Sprachen als Menschen in Großbritannien.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frage 3: Alle Teilnehmenden der Untersuchung in Berlin …',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — hatten Türkisch schon als Baby gelernt'],
            'explanation' => "Tous les ~100 participants avaient appris le turc dès la naissance ; l'allemand n'est arrivé qu'à des âges différents.",
            'options' => [
                ['label' => 'a', 'text' => 'hatten Türkisch schon als Baby gelernt.'],
                ['label' => 'b', 'text' => 'sprachen besser Türkisch als Deutsch.'],
                ['label' => 'c', 'text' => 'waren in der Türkei geboren.'],
                ['label' => 'd', 'text' => 'waren Kinder im Alter von 6 – 7 Jahren.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frage 4: Als Resultat der Untersuchung …',
            'correct_answer' => 'a',
            'data' => ['model' => 'a — empfiehlt die Sprecherin, Sprachen früher zu lehren'],
            'explanation' => "Résultat : commencer l'éducation bilingue plus tôt que d'habitude (dès la maternelle) — mais pas nécessairement avant, ni « je früher, desto besser ».",
            'options' => [
                ['label' => 'a', 'text' => 'empfiehlt die Sprecherin, Sprachen früher zu lehren.'],
                ['label' => 'b', 'text' => 'hebt die Sprecherin die Vorteile von bilingualer Erziehung hervor.'],
                ['label' => 'c', 'text' => 'kritisiert die Sprecherin den üblichen Sprachunterricht in Schulen.'],
                ['label' => 'd', 'text' => 'warnt die Sprecherin davor, bilinguale Erziehung zu spät zu beginnen.'],
            ],
        ],
        [
            'type' => 'single_choice',
            'prompt' => 'Frage 5: Das Hauptziel des Vortrags besteht darin, …',
            'correct_answer' => 'c',
            'data' => ['model' => 'c — falsche Meinungen über Mehrsprachigkeit richtigzustellen'],
            'explanation' => "L'objectif de la conférence : rectifier les opinions erronées (mythes) sur le multilinguisme.",
            'options' => [
                ['label' => 'a', 'text' => 'den Nutzen von Mehrsprachigkeit infrage zu stellen.'],
                ['label' => 'b', 'text' => 'den Umgang mit Mehrsprachigkeit zu kritisieren.'],
                ['label' => 'c', 'text' => 'falsche Meinungen über Mehrsprachigkeit richtigzustellen.'],
                ['label' => 'd', 'text' => 'von den Vorteilen von Mehrsprachigkeit zu überzeugen.'],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Démo 7 — Laut- und Schriftbild abgleichen
    // (Text « Zufriedenheit » : 4 mots du texte diffèrent de l'audio)
    // Solutions officielles : le texte montre « dann / besten / Es / Garantie »,
    // l'audio prononce « denn / ehesten / So / Garanten ».
    // Adaptation : 8 mots-candidats cliquables (4 à marquer + 4 leurre).
    // ------------------------------------------------------------------
    7 => [
        [
            'type' => 'multiple_choice',
            'prompt' => 'Vier Wörter im Text entsprechen NICHT dem Hörtext. Klicken Sie genau diese vier Wörter an (ein zweiter Klick löscht die Markierung).',
            'correct_answer' => ['dann', 'besten', 'es', 'garantie'],
            'data' => [
                'model' => 'dann, besten, Es, Garantie',
                'segments' => [
                    ['t' => 'Die Grundstimmung, mit der wir durchs Leben gehen, hängt nur erstaunlich '],
                    ['w' => 'wenig', 'id' => 'wenig'],
                    ['t' => ' von den Wendungen unserer '],
                    ['w' => 'Biografie', 'id' => 'biografie'],
                    ['t' => ' ab. Aber worauf baut Zufriedenheit '],
                    ['w' => 'dann', 'id' => 'dann'],
                    ['t' => ' auf? Und wie erreicht man sie am '],
                    ['w' => 'besten', 'id' => 'besten'],
                    ['t' => '? Im Urteil darüber, wie dieser '],
                    ['w' => 'Zustand', 'id' => 'zustand'],
                    ['t' => ' der Zufriedenheit zu erreichen ist, irren Menschen offenbar erstaunlich oft. '],
                    ['w' => 'Es', 'id' => 'es'],
                    ['t' => ' gelten gemeinhin vor allem materieller Wohlstand, Gesundheit oder eine '],
                    ['w' => 'Heirat', 'id' => 'heirat'],
                    ['t' => ' als '],
                    ['w' => 'Garantie', 'id' => 'garantie'],
                    ['t' => ' für ein zufriedenes Leben. Etliche Studien zeigen jedoch, dass derartige Ereignisse und Umstände die Art und Weise, wie wir das Leben betrachten, nur in engen Grenzen beeinflussen.'],
                ],
            ],
            'explanation' => 'Dans l\'audio : « …baut Zufriedenheit denn auf » (et non „dann"), « am ehesten » (et non „am besten"), « So gelten » (et non „Es gelten"), « als Garanten » (et non „Garantie").',
        ],
    ],
];
