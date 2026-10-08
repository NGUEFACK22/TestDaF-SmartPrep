# SYNPHONIE

Plateforme web d'entraînement à l'allemand : **100 % QCM écrit, par niveau (A1 → C2)**.
Construite avec **Laravel 12** (PHP ≥ 8.2) + frontend Blade/JavaScript (build Vite).

Le candidat suit le parcours complet :

```
Accueil → inscription → connexion → dashboard
→ préparation (niveau A1…C2 : banque QCM ou session IA inédite)
→ examen chronométré → correction → résultats → recommandations → progression
```

## Fonctionnalités

- **Parcours QCM par niveau** : banques A1–B2 (tirage sans remise, questions différentes
  à chaque session, calibrées sur le cadre du niveau) ; C1 au **format TestDaF**
  (QCM + Lückentext + vrai/faux) ; C1–C2 en **sessions IA inédites** (min 20 QCM,
  calibrés sur les faiblesses, minuteur par question selon la difficulté).
- **Moteur d'examen à autorité serveur** : chaque tâche possède sa durée en base
  (`duration_seconds`) ; le serveur calcule `expires_at = started_at + duration_seconds`
  et refuse toute réponse hors délai. Fenêtre de confirmation avant chaque avance
  (temps restant + état des réponses). Navigation séquentielle stricte, pas de retour.
- **Questions numérotées** (`Frage 1 / N`) avec pastille `· En attente` / `✓ Répondu`,
  aide discrète par type, positions de réponses **brassées à chaque tentative**
  (stable pendant la tentative, correction par valeurs).
- **Correction** : objective automatique et déterministe (points, %, **points partiels**
  sur les types à réponses multiples) + conversion TDN /20 indicative.
- **Espace Élite** : 2 scores parfaits consécutifs → défis IA sur mesure + notifications.
- **Tableau de bord** : progression, évolution (graphiques animés), exercices recommandés.
- **Résultats** (`/results/{attempt}`), **rapport** (`/report`) et **export PDF** (`/pdf`).
- **Administration** : dashboard (utilisateurs, niveaux, défis IA), CRUD exercices QCM,
  utilisateurs, paramètres IA (quota journalier).
- **Compte RGPD** : export JSON + suppression définitive ; `synphonie:purge` (vieux logs),
  `synphonie:seed-content`, `synphonie:make-admin`, `synphonie:abandon-stale`.
- **Sécurité** : sessions + CSRF, rôles, rate limiting, validation serveur systématique,
  journal d'événements (`exam_logs`), endpoint `/health`.

> ⚠️ Plateforme d'**entraînement** : scores et niveaux TDN **indicatifs**, jamais des
> notes officielles. Contenus originaux rédigés pour la plateforme.

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | PHP ≥ 8.2, Laravel 12 |
| Base | SQLite (dev) ou PostgreSQL (local / **Neon serverless**) / MySQL |
| Frontend | Blade + JavaScript vanilla (`resources/js/exam/*`), build Vite, responsive + animations |
| File de jobs | Laravel queue (`QUEUE_CONNECTION=database`) — cron `queue:work --once` + `schedule:run` |
| Tests | PHPUnit (`php artisan test`) : moteur d'examen, anti-contournement, niveaux, IA (mockée) |
| IA (optionnel) | Gemini ou Mistral (génération QCM C1/C2, `AI_PROVIDER`) |

## Démarrage rapide

Prérequis : PHP ≥ 8.2 (extension `sqlite3`), Composer, Node ≥ 20, npm.

```bash
composer install
copy .env.example .env        # Windows — Linux/macOS : cp .env.example .env
php artisan key:generate
php artisan migrate --seed    # base SQLite + parcours A1–C1 + comptes démo
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

> ⚠️ À changer impérativement en production (commande `synphonie:make-admin`).

## Tests

```bash
php artisan test
```

Couverts : auth/rôles, moteur d'examen (timers serveur, verrouillage, navigation),
**anti-contournement**, brassage des réponses, barème partiel, parcours par niveau
(formats, anti-répétition, calibration C1), Défi IA (mocké), quotas, reset password.

## Documentation

| Document | Contenu |
|---|---|
| [`docs/USAGE.md`](docs/USAGE.md) | Guide candidat + administrateur |
| [`docs/INSTALLATION.md`](docs/INSTALLATION.md) | Installation (Windows & Linux/macOS), base, clés IA |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Production classique (HTTPS, queue, cron) |
| [`docs/DEPLOYMENT_GRATUIT.md`](docs/DEPLOYMENT_GRATUIT.md) | Déploiement 0 € (Render + Neon) |

## Configuration IA (optionnelle — C1/C2 uniquement)

Sans clé API, les banques A1–C1 fonctionnent intégralement. Pour les sessions IA :

```env
AI_ENABLED=true
GEMINI_API_KEY=votre_cle_api
MAX_AI_REQUESTS=100
```

Les secrets ne doivent jamais être placés dans Git ; un `.env.example` sans secrets est fourni.

## Licence

Projet éducatif — MIT.
