<?php

/**
 * Bootstrap PHPUnit — garantit des conditions de test isolées.
 *
 * PHP copie l'environnement du processus hôte dans $_SERVER au démarrage.
 * Dans un shell pollué (IDE Cline, certaines CI), cet environnement peut
 * contenir DB_CONNECTION=pgsql, DB_DATABASE=neondb, SESSION_DRIVER=database,
 * QUEUE_CONNECTION=database, AI_ENABLED=false, etc. Laravel lit $_SERVER
 * AVANT $_ENV/putenv (ServerConstAdapter en tête des adaptateurs Dotenv),
 * ces variables d'hôte écraseraient donc silencieusement la configuration
 * de test définie dans phpunit.xml — et la suite basculerait sur une base
 * distante. On neutralise ici les variables d'hôte qui entrent en conflit
 * avec la configuration de test.
 */

require __DIR__.'/../vendor/autoload.php';

foreach ([
    'APP_ENV',
    'APP_DEBUG',
    'APP_KEY',
    'APP_MAINTENANCE_DRIVER',
    'DB_CONNECTION',
    'DB_DATABASE',
    'DB_HOST',
    'DB_PORT',
    'DB_USERNAME',
    'DB_PASSWORD',
    'DB_SSLMODE',
    'DB_ENDPOINT',
    'SESSION_DRIVER',
    'QUEUE_CONNECTION',
    'CACHE_STORE',
    'MAIL_MAILER',
    'PULSE_ENABLED',
    'TELESCOPE_ENABLED',
    'BCRYPT_ROUNDS',
    'AI_ENABLED',
    'AI_MODEL',
    'GEMINI_API_KEY',
] as $key) {
    unset($_SERVER[$key]);
}