# Déployer SYNPHONIE gratuitement (Render + Neon + R2)

Coût : **0 €** — ~20 minutes, sans serveur à administrer.
Fichiers déjà prêts : `Dockerfile`, `render.yaml`, `docker/`.

## 1. Base de données gratuite — Neon (~3 min)

1. Créer un compte sur <https://neon.tech> (offre gratuite).
2. New Project → région proche (ex. EU Central) → Postgres 17.
3. Noter : **host** (endpoint `-pooler`), **database**, **user**, **password**.
4. `DB_ENDPOINT` = premier segment du host (ex. `ep-cool-name-123456`).

## 2. Stockage fichiers gratuit — Cloudflare R2 (~5 min)

Sans cela, les enregistrements audio/vidéo **disparaissent** à chaque redéploiement
(Render gratuit = disque éphémère).

1. Compte Cloudflare → R2 → Create bucket (ex. `synphonie-media`).
2. R2 → Manage R2 API Tokens → Create API Token (Object Read & Write) → noter
   **Access Key ID**, **Secret Access Key** et l'**endpoint**
   (`https://<account-id>.r2.cloudflarestorage.com`).
3. Pas de domaine public nécessaire : l'appli lit via `/media/{id}` (accès contrôlé).

## 3. Déploiement — Render Blueprint (~5 min + build auto)

1. Compte sur <https://render.com> (connexion via GitHub).
2. New → **Blueprint** → choisir le dépôt `TestDaF-SmartPrep` (branche `main`).
   Render détecte `render.yaml` et crée 2 services : `synphonie` (web) + `synphonie-worker` (cron).
3. Renseigner les variables `sync: false` :
   - `APP_KEY` : en local, `php artisan key:generate --show` → coller la valeur.
   - `APP_URL` : laisser vide au 1er déploiement, puis mettre l'URL Render
     (`https://synphonie.onrender.com`) et **redeploy**.
   - `DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD/DB_ENDPOINT` (Neon).
   - `AWS_*` (R2). `GEMINI_API_KEY` si analyse IA (optionnel).
4. Deploy. La migration tourne automatiquement (`preDeployCommand`).

## 4. Contenu + admin (~3 min, Render Shell)

Dans Render → service `synphonie` → **Shell** :

```bash
php artisan synphonie:seed-content   # rôles, réglages, 10 Modelltests, audios, démos, C1
php artisan synphonie:make-admin vous@example.com "Votre Nom"
```

Ne **jamais** lancer `db:seed` complet en prod (comptes démo à mot de passe connu).

## 5. Vérifier

- `https://…/up` → 200 (health Laravel).
- `https://…/health` → `{"ok":true,…}` (DB + file + queue).
- Inscription → Modelltest 1 → 1 tâche → résultats.
- Enregistrer un audio Sprechen → réécoute OK (fichier bien sur R2).

## Limites honnêtes du gratuit

| Point | Conséquence |
|---|---|
| Render free s'endort après inactivité | 1er chargement ~30–60 s à froid |
| Cron 1×/min, 1 job à la fois | analyse IA en différé (quelques minutes) |
| R2 10 Go / Neon ~3 Go | largement suffisant pour démarrer |
| Pas de domaine perso en free | URL `*.onrender.com` (+ TLS inclus) |

## Quand grandir (plus tard, payant)

- Render Starter (~7 $/mois) : plus de mise en veille.
- Redis (Upstash gratuit pour débuter) : `QUEUE_CONNECTION`/`CACHE_STORE=redis`.
- Domaine perso : Cloudflare (gratuit) devant Render.
