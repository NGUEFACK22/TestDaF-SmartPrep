<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * PostgresConnector adapté à Neon (PostgreSQL serverless).
 *
 * Deux adaptations, uniquement pour les hôtes Neon (aucun impact sur un
 * PostgreSQL classique) :
 *
 *  1. Endpoint direct : le "transaction pooler" (hostname en "-pooler")
 *     réinitialise l'état de session entre les requêtes, ce qui casse les
 *     transactions multi-requêtes de Laravel (cache / sessions :
 *     SELECT ... FOR UPDATE puis UPDATE) avec SQLSTATE[25P02]. On réécrit donc
 *     le host pour utiliser l'endpoint DIRECT (sans "-pooler").
 *
 *  2. SNI : les builds PHP embarquant un libpq ancien (< 15.3) ne font pas le
 *     SNI automatiquement ; l'identifiant de l'endpoint (première partie du
 *     hostname) doit être passé dans le DSN sous la forme
 *     "options=endpoint=<endpoint-id>" (voir https://neon.tech/sni).
 */
class NeonPgsqlConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $host = (string) ($config['host'] ?? '');

        // Le "transaction pooler" de Neon (hostname en "-pooler") réinitialise
        // l'état de session entre les requêtes : les transactions multi-requêtes
        // de Laravel (cache / sessions : SELECT ... FOR UPDATE puis UPDATE)
        // échouent alors avec SQLSTATE[25P02] (« current transaction is
        // aborted »). On se connecte donc toujours à l'endpoint DIRECT, identique
        // mais sans le suffixe "-pooler" (même projet Neon, même compute).
        if (preg_match('/^(ep-[^.]+)-pooler\.(.+)$/', $host, $m)) {
            $host = $m[1] . '.' . $m[2];
            $config['host'] = $host;
        }

        $dsn = parent::getDsn($config);

        // L'endpoint SNI = premier segment du hostname, EXACTEMENT tel que Neon
        // l'infère (ex. "ep-broad-queen-b5lqx7x0"). Le passer différemment
        // produit l'erreur Neon "Inconsistent project name inferred from SNI ...".
        // On ne l'ajoute que pour les hôtes Neon (ou si l'endpoint est explicite),
        // pour ne rien changer sur un PostgreSQL classique.
        $endpoint = '';
        if (str_contains($host, 'neon.tech')) {
            $endpoint = trim(strtok($host, '.') ?: '');
        }
        if ($endpoint === '') {
            $endpoint = (string) ($config['endpoint'] ?? '');
        }

        if ($endpoint !== '') {
            $dsn .= ';options=endpoint=' . $endpoint;
        }

        return $dsn;
    }
}