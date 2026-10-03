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

        if (! empty($config['endpoint'])) {
            $dsn .= ';options=endpoint=' . $config['endpoint'];
        }

        return $dsn;
    }
}