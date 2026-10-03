# TestDaF Preparation Platform

Plateforme web de préparation au **TestDaF digital** construite avec **Laravel 12** (PHP ≥ 8.2) + frontend Blade/JavaScript (build Vite).

Le candidat suit le parcours complet :

```
Accueil → inscription → connexion → préparation/entraînement
→ Modelltest (Lesen → Hören → Schreiben → Sprechen)
→ correction → résultats → analyse IA → recommandations → progression
```

## Fonctionnalités

- **10 Modelltests complets** (thèmes universitaires originaux, contenus démo fournis en seeder) + bibliothèque d'exercices réutilisables.
- **Moteur d'examen à autorité serveur** : chaque tâche possède sa durée en base (`duration_seconds`) ; le serveur calcule `expires_at = started_at + duration_seconds` et refuse toute réponse hors délai. Le chronomètre JavaScript n'est qu'un affichage : il est impossible de contourner le verrouillage en modifiant le JS, l'URL, l'horloge locale ou en envoyant des requêtes manuelles (testé par les tests AntiCheat).
- **Navigation séquentielle stricte** : ordre imposé (Aufgabe 1 → 2 → …), pas de retour en arrière, passage automatique à l'expiration.
- **12 types de questions** rendus par un moteur générique (`QuestionRenderer`) : `multiple_choice`, `single_choice`, `true_false`, `matching`, `ordering`, `fill_blank`, `short_answer`, `category_assignment`, `pair_assignment`, `text_input`, `essay`, `audio_response`, `video_response`.
- **Hören** : lecture audio/vidéo depuis le stockage privé, streaming contrôlé (aucun fichier public), sauvegarde des réponses.
- **Schreiben** : éditeur avec compteur de mots, autosave toutes les 10 s (refusé côté serveur si expiré), verrouillage après fin.
- **Sprechen** : enregistrement du micro via l'API `MediaRecorder` (WebM), temps de préparation, re-enregistrement possible tant que la tâche n'est pas validée, upload sécurisé vers `storage/app/private`.
- **Correction** : objectives automatique et déterministe (points, %) ; Schreiben/Sprechen évalués par IA et/ou correcteur humain (interface admin).
- **IA encapsulée** derrière des services interchangeables : `GeminiService`, `WhisperService` (transcription, auto-hébergable), `LanguageToolService`, `SpeechaceService` (optionnel). Analyses en jobs asynchrones (`ProcessWritingSubmission`, `ProcessSpeakingSubmission`), cache, quotas, gestion d'échec (« Analyse en attente » + nouvelle tentative).
- **Tableau de bord candidat** : progression par compétence (Lesen/Hören/Schreiben/Sprechen), graphiques d'évolution, exercices recommandés, activités récentes.
- **Espace préparation** par compétence (`/preparation/lesen` etc.) + **mode entraînement** (correction immédiate, sans chronomètre).
- **Résultats** (`/results/{attempt}`) et **rapport détaillé** (`/results/{attempt}/report`) : points par section, corrections expliquées, commentaires IA/correcteur, erreurs récurrentes, recommandations.
- **Administration** : dashboard (utilisateurs, tentatives, scores, IA, stockage), CRUD Modelltests (avec contrôle de complétude avant publication), CRUD exercices/questions, corrections manuelles, gestion des utilisateurs, paramètres IA.
- **Notifications** (nouveau test, correction disponible, analyse terminée) + journal d'événements d'examen (`exam_logs`).
- **Sécurité** : sessions Laravel + CSRF, rôles via middleware/policies, rate limiting par route, validation serveur systématique, mots de passe hashés, fichiers candidats en stockage privé non listable, aucune exécution de fichier uploadé.

> ⚠️ Les évaluations IA sont **indicatives, à titre d'entraînement** — elles ne constituent pas des notes officielles TestDaF. Les contenus de démo sont originaux ; seules la structure et les types de tâches sont inspirés de la référence pédagogique TestDaF.

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP ≥ 8.2, Laravel 12 |
| Base | SQLite (défaut, dev) ou PostgreSQL (local ou **Neon serverless** — `DB_ENDPOINT` + connecteur dédié) / MySQL |
| Frontend | Blade + JavaScript vanilla (`resources/js/exam/*`), build Vite |
| File de jobs | Laravel queue (`QUEUE_CONNECTION=database`) — `queue:work` pour les analyses IA |
| Tests | PHPUnit (`php artisan test`) : moteur d'examen, auth, anti-contournement, médias, admin |
| IA (optionnel) | Gemini (analyse), Whisper (transcription), LanguageTool (grammaire), Speechace (prononciation, option) |

## Structure du projet (extrait)

```
app/
├── Enums/            # Skill, Difficulty, ExerciseState, AttemptStatus
├── Http/
│   ├── Controllers/  # Auth/, Candidate/, Admin/
│   ├── Middleware/   # EnsureRole (rôles)
│   └── Requests/     # Form Requests (validation serveur)
├── Jobs/             # ProcessWritingSubmission, ProcessSpeakingSubmission
├── Models/           # 20 modèles Eloquent (ModellTest, Attempt, UserAnswer, …)
├── Policies/         # AttemptPolicy, …
└── Services/
    ├── Exam/         # ExamService, TimerService, AnswerService, ScoringService
    ├── Evaluation/   # AiEvaluationService, Writing/SpeakingEvaluationService
    ├── AI/           # GeminiService, WhisperService, LanguageToolService, SpeechaceService
    ├── Media/        # MediaService (stockage privé + streaming contrôlé)
    └── Statistics/   # StatisticsService, RecommendationService
database/
├── migrations/       # 29 tables (tests, sections, exercices, questions, tentatives, réponses, IA, …)
├── seeders/          # rôles, comptes démo, réglages, 10 Modelltests complets
└── factories/
resources/
├── js/exam/          # timer.js, questionRenderer.js, recorder.js, writing.js, speaking.js
└── views/            # candidate/ (dashboard, exam, results…), admin/…
routes/web.php        # routes web + API interne (session + CSRF)
docs/                 # INSTALLATION.md, USAGE.md, DEPLOYMENT.md
```

## Démarrage rapide

Prérequis : PHP ≥ 8.2 (extension `sqlite3`), Composer, Node ≥ 20, npm.

```bash
composer install
copy .env.example .env        # Windows — Linux/macOS : cp .env.example .env
php artisan key:generate
php artisan migrate --seed    # base SQLite + 10 Modelltests + comptes démo
npm install
npm run build                 # développement : npm run dev
php artisan serve
```

Puis ouvrir <http://127.0.0.1:8000>.

### Comptes de démonstration (créés par les seeders)

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@testdaf.local` | `TestDaF-Admin-2026!` |
| Candidat démo | `candidat@testdaf.local` | `TestDaF-Demo-2026!` |

> ⚠️ À changer impérativement en production (section *Utilisateurs* de l'admin).

## Tests

```bash
php artisan test
```

Couverts : inscription/connexion/rôles, création de tentative, démarrage d'exercice, expiration (autorité serveur), verrouillage, navigation séquentielle, **anti-contournement** (timer JS modifié, accès à une tâche future, modification de réponse expirée, usurpation d'identifiant dans l'URL), calcul du score, correction, upload audio (stockage privé + streaming contrôlé), permissions admin.

## Documentation

| Document | Contenu |
|---|---|
| [`docs/INSTALLATION.md`](docs/INSTALLATION.md) | Installation depuis zéro (Windows & Linux/macOS), base de données, clés IA, tests |
| [`docs/USAGE.md`](docs/USAGE.md) | Guide d'utilisation — candidat (parcours, modes, micro) et administrateur (contenus, corrections, IA) |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Production : HTTPS, queue, cron, stockage, sauvegardes, limites d'upload |

## Configuration IA (optionnelle)

Sans aucune clé API, la plateforme fonctionne intégralement (corrections objectives automatiques + correction manuelle admin). Pour activer l'analyse automatique :

```env
AI_ENABLED=true
AI_PROVIDER=gemini
AI_MODEL=gemini-1.5-flash
GEMINI_API_KEY=votre_cle_api
WHISPER_ENDPOINT=https://whisper.mondomaine.fr/v1   # optionnel (transcription locale)
LANGUAGETOOL_API_URL=https://api.languagetool.org/v2
```

Les secrets ne doivent jamais être placés dans Git ; un `.env.example` sans secrets est fourni.

## Licence

Projet éducatif — MIT. Les contenus des 10 Modelltests de démonstration sont originaux ; ne pas les reproduire sans adaptation.
