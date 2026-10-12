<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * PostgresConnector adapté aux endpoints pooler de Neon (PostgreSQL serverless).
 *
 * Les builds PHP qui embarquent un libpq récent (< 15.3) ne font pas le SNI
 * automatiquement pour les endpoints pooler : l'identifiant de l'endpoint
 * (première partie du hostname) doit être passé dans le DSN sous la forme
 * "options=endpoint=<endpoint-id>" (voir https://neon.tech/sni).
 *
 * Ce connecteur ajoute ce paramètre quand la clé de configuration "endpoint"
 * est définie (env DB_ENDPOINT), sans modifier le comportement pour les
 * autres serveurs PostgreSQL.
 */
class NeonPgsqlConnector extends PostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        // L'endpoint SNI = premier segment du hostname, EXACTEMENT tel que Neon
        // l'infère (ex. "ep-broad-queen-b5lqx7x0-pooler" pour un pooler). Le passer
        // différemment (ex. sans le suffixe "-pooler") produit l'erreur Neon
        // "Inconsistent project name inferred from SNI ...". On dérive donc
        // l'endpoint du hostname pour être toujours cohérent avec le SNI.
        $host = (string) ($config['host'] ?? '');
        $endpoint = trim(strtok($host, '.') ?: '');
        if ($endpoint === '') {
            $endpoint = (string) ($config['endpoint'] ?? '');
        }

        if ($endpoint !== '') {
            $dsn .= ';options=endpoint=' . $endpoint;
        }

        return $dsn;
    }
}