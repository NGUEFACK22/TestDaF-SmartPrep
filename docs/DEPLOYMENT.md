# Déploiement en production

Deux environnements sont prévus : **development** (cet état par défaut) et **production**. La plateforme n'a aucun verrou environnal ; seuls les `.env` et la configuration serveur diffèrent.

## 1. Prérequis serveur (recommandé : VPS Linux)

| Élément | Version |
|---|---|
| PHP | ≥ 8.2 + extensions `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl` |
| Web server | Nginx (ou Apache + mod_php) + **certificat TLS (HTTPS obligatoire)** |
| Base | MySQL 8 / MariaDB 10.6+ |
| File | Worker Laravel (daemon) — ou Redis + Horizon |
| Cron | Planificateur Laravel |
| Disque | Stockage privé des médias (min. 50 Go) + sauvegardes |

## 2. `.env` de production

À partir de `.env.example` :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://testdaf.example.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=testdaf
DB_USERNAME=testdaf_app
DB_PASSWORD=<mot-de-passe-fort>
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=smtp        # ou log en local
# IA
AI_ENABLED=true
GEMINI_API_KEY=<ne-jamais-mettre-dans-git>
WHISPER_ENDPOINT=<serveur-dedie>
MAX_AI_REQUESTS=200
```

`php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache`

## 3. HTTPS (obligatoire)

- Let's Encrypt : `sudo certbot --nginx -d testdaf.example.com`.
- Forcer le redirect 301 http → https ; ajouter `Strict-Transport-Security`.
- **Indispensable pour Sprechen** : `MediaRecorder` (micro) n'est actif que sur contexte sécurisé (HTTPS ou `127.0.0.1`).

## 4. Configuration Nginx (extrait)

```nginx
server {
    listen 443 ssl;
    server_name testdaf.example.com;
    root /var/www/testdaf/public;
    index index.php;

    client_max_body_size 110m;   # vidéo max 100 Mo + overhead

    location / {
        try_files $uri /index.php?$query_string;
    }
    location = /index.php {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    }
    location ~ \.php$ { return 404; }
    location ~ /\. { deny all; }   # bloquer .env, .git, etc.
}
```

Apache équivalent : `php_value upload_max_filesize 110M`, `php_value post_max_size 110M`, `php_value max_execution_time 300`.

## 5. File de jobs (analyses IA asynchrones)

Les gros fichiers audio/vidéo **ne bloquent jamais la requête HTTP** : les jobs `ProcessWritingSubmission`, `ProcessSpeakingSubmission` tournent dans un worker.

```bash
# systemd : /etc/systemd/system/testdaf-queue.service
[Unit]
Description=Laravel queue worker (TestDaF)
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/testdaf
ExecStart=/usr/bin/php /var/www/testdaf/artisan queue:work database --tries=3 --max-time=3600 --memory=512
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now testdaf-queue
```

## 6. Cron Laravel (une minute)

```bash
sudo crontab -e
* * * * * cd /var/www/testdaf && php artisan schedule:run >> /dev/null 2>&1
```

(Événements planifiés : purge des sessions expirées, rappels d'entraînement, nettoyage logs anciens.)

## 7. Stockage et sauvegardes

- **Médias** : `storage/app/private/` (candidate_audio, candidate_video, content_media…) — permissions `www-data:www-data 755`, **hors `public/`**. Optionnel : S3/MinIO (`TESTDAF_MEDIA_DISK=s3`) pour des volumes importants.
- **Base** : `mysqldump` quotidien chiffré + rétention 30 jours ; les uploads candidats sont des données personnelles → PSE/registre RGPD recommandé.
- **Logs** : `storage/logs/laravel.log` (rotation) + `exam_logs` en base pour l'audit des sessions d'examen.

## 8. Durcissement

| Mesure | Détail |
|---|---|
| Accès `.env` | `location ~ /\. { deny all; }` + droits `640` |
| Base | user SQL minimal (DML seulement), SSL vers la base, pas d'`root` distant |
| Uploads | validation MIME + extension côté serveur (`MediaService`), noms régénérés, jamais d'exécution de PHP uploadé |
| Clés IA | uniquement dans `.env` ; jamais dans le DOM/JS ; quotas `MAX_AI_REQUESTS` + cache des analyses |
| Sessions | cookie `secure`, `httponly`, `samesite=Lax` (Laravel par défaut avec HTTPS) |
| Monitoring | alerte si `exam_logs` détecte des tentatives d'accès interdit répétées ; surveillance `ai_evaluations.status='error'` |

## 9. Check-list de mise en ligne

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. Migrations : `php artisan migrate --force` (fenêtre de maintenance : `php artisan down` → up)
4. Seeders de contenu si nécessaire (`db:seed --class=ModellTestSeeder`)
5. Cache : `config:cache route:cache view:cache event:cache`
6. Certificat TLS + redirect ; tester le micro (Sprechen) sur la page d'examen
7. Worker + cron démarrés ; `php artisan optimize`
8. Smoke test : inscription → Modelltest 1 (1 tâche au moins) → correction objectives → page résultats
9. Sauvegarde initiale de la base + des médias
10. Changer les mots de passe démo et supprimer les comptes démo

## 10. Pannes de production fréquentes

| Symptôme | Cause / remède |
|---|---|
| « Analyse en attente » définitif | worker arrêté (`systemctl status testdaf-queue`) ou quota IA atteint (`MAX_AI_REQUESTS`) |
| Upload audio refusé | `client_max_body_size` trop bas ; vérifier `upload_max_filesize`/`post_max_size` PHP |
| Timer différent côté client | l'horloge du **serveur** fait foi : NTP sur le serveur, `php artisan config:clear` |
| 500 après déploiement | cache non régénéré ; relancer les `*:cache` et vérifier le canal `LOG_CHANNEL` (not `debug` en prod) |
| Session perdue au rechargement | `SESSION_DRIVER=database` exige la table `sessions` migrée ; vérifier les droits de la base |
