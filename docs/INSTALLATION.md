# Installation — TestDaF Preparation Platform

Guide d'installation complète depuis zéro. Les commandes **Windows** sont données en premier ; les équivalents Linux/macOS en note.

## 1. Prérequis (outillage)

| Outil | Version | Vérification |
|---|---|---|
| PHP | ≥ 8.2 (extensions : `sqlite3`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`) | `php -v` |
| Composer | ≥ 2.x | `composer -V` |
| Node.js | ≥ 20 | `node -v` |
| npm | ≥ 10 | `npm -v` |
| MySQL / MariaDB | 8.x (optionnel — le défaut est SQLite) | `mysql --version` |

### Windows (si nécessaire)

```powershell
# PHP 8.2+ (exemple avec Chocolatey)
choco install php --version=8.2.12 --no-opaque-versions
# Composer
choco install composer
# Node.js LTS
choco install nodejs-lts
# Vérifications
php -v; composer -V; node -v; npm -v
```

### Linux/macOS (si nécessaire)

```bash
# Debian/Ubuntu
sudo apt update && sudo apt install -y php-cli php-sqlite3 php-mbstring php-xml php-curl php-zip unzip
sudo apt install -y composer nodejs npm
# macOS
brew install php@8.2 composer node
```

## 2. Récupérer le projet

```bash
cd <répertoire de travail>
git clone <url-du-repo> testdaf
cd testdaf
composer install
```

*(Windows : `cd` au lieu de `git clone` si le projet a été copié localement, puis `composer install`.)*

## 3. Configuration `.env`

```powershell
# Windows
copy .env.example .env
# Linux/macOS
# cp .env.example .env

php artisan key:generate
```

Valeurs par défaut du `.env.example` : SQLite (`DB_CONNECTION=sqlite`), locale `fr`, IA désactivée (`AI_ENABLED=false`).

### Option : MySQL à la place de SQLite

Créer la base :

```sql
CREATE DATABASE testdaf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Puis dans `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=testdaf
DB_USERNAME=root
DB_PASSWORD=mot_de_passe_mysql
```

## 4. Migrations + données de démonstration

```bash
php artisan migrate --seed
```

Cela crée les **29 tables** puis exécute : `RoleSeeder` (rôles `candidate`/`admin`), `AdminUserSeeder` (comptes ci-dessous), `SettingSeeder` (réglages IA par défaut), `ModellTestSeeder` (**10 Modelltests complets** : Lesen, Hören, Schreiben, Sprechen, solutions et explications).

Comptes créés :

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@testdaf.local` | `TestDaF-Admin-2026!` |
| Candidat démo | `candidat@testdaf.local` | `TestDaF-Demo-2026!` |

### Créer un administrateur supplémentaire (sans seeder)

```bash
php artisan tinker --execute="\$u=App\Models\User::create(['name'=>'Admin 2','email'=>'admin2@mondomaine.fr','password'=>'motdepasse123','role_id'=>App\Models\Role::where('slug','admin')->first()->id]); echo 'ok';"
```

## 5. Frontend (Vite)

```bash
npm install
npm run build     # production locale
# ou, en développement (watch) :
npm run dev
```

## 6. Lancer l'application

```bash
php artisan serve
```

Ouvrir **http://127.0.0.1:8000** → inscription ou connexion avec un compte ci-dessus.

## 7. Stockage

- Les **productions candidats** (audio/vidéo) et les **médias de contenu** sont stockés dans `storage/app/private/` (disque `local`, non public). Aucun `storage:link` n'est nécessaire : le streaming passe par `GET /media/{id}` (route contrôlée, `MediaController`).
- Vérifier que `storage/app/private` existe et est accessible en écriture (c'est le cas par défaut).

## 8. Clés API (optionnel — IA)

À placer **uniquement** dans `.env` (jamais dans Git) :

```env
AI_ENABLED=true
AI_PROVIDER=gemini
AI_MODEL=gemini-1.5-flash
GEMINI_API_KEY=xxx...
WHISPER_ENDPOINT=https://whisper.mondomaine.fr/v1
LANGUAGETOOL_API_URL=https://api.languagetool.org/v2
SPEECHACE_API_KEY=           # optionnel (prononciation avancée)
MAX_AI_REQUESTS=100
AI_AUTO_EVALUATION=true
AI_MANUAL_REVIEW=true
```

Recommandation : lancer un worker pour les analyses (asynchrone) :

```bash
php artisan queue:work database --tries=3
```

Sans ces clés, la plateforme reste entièrement fonctionnelle (corrections objectives automatiques + correction manuelle dans l'admin).

## 9. Tester

```bash
php artisan test
```

Attaque de référence attendue : `21 passed (48 assertions)` ou davantage — moteur d'examen, auth, anti-contournement, médias, admin.

## 10. Pannes courantes (Windows)

| Symptôme | Solution |
|---|---|
| `composer install` lent / verrou | relancer avec `composer install --no-interaction` ; fermer les proces PHP résiduels (`Get-Process php | Stop-Process`) |
| `could not find driver` | activer `pdo_sqlite` / `pdo_mysql` dans `php.ini` (`extension=...`), puis relancer |
| `403` sur les uploads | vérifier les droits d'écriture sur `storage/` |
| Micro refusé (Sprechen) | le navigateur exige **HTTPS ou 127.0.0.1** pour `MediaRecorder` ; autoriser l'accès micro dans l'onglet Site info → autorisations |
| Timer décalé | le serveur est l'autorité : `php artisan config:clear` puis vérifier l'horloge du **serveur** (pas du navigateur) |
