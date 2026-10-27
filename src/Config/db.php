<?php
// /var/www/html/laboratorio/src/Config/db.php

use DI\Container;

require_once __DIR__ . '/env.php';

return function (Container $container) {
    // 1. RECURSO SQL SERVER (CLÍNICO / SIGH / LABORATORIO)
    $container->set('db', function () {
        $serverName = getenv('DB_SQL_HOST');
        $connectionInfo = [
            "Database"               => getenv('DB_SQL_DATABASE'),
            "UID"                    => getenv('DB_SQL_USER'),
            "PWD"                    => getenv('DB_SQL_PASS'),
            "CharacterSet"           => "UTF-8",
            "TrustServerCertificate" => (bool)(getenv('DB_SQL_TRUST_CERT') ?: true),
            "Encrypt"                => (bool)(getenv('DB_SQL_ENCRYPT') ?: false)
        ];

        if (!$serverName || !$connectionInfo["Database"]) {
            throw new \Exception("Configuración de SQL Server incompleta en variables de entorno.");
        }

        $conn_sql = sqlsrv_connect($serverName, $connectionInfo);

        if (!$conn_sql) {
            error_log("Error conexión SQL Server SIGH: " . print_r(sqlsrv_errors(), true));
            throw new \Exception("Error interno: No se pudo conectar con el servidor SQL Server clínico.");
        }

        return $conn_sql;
    });

    // 2. RECURSO POSTGRESQL (ACL / SESIONES / INSUMOS)
    $container->set('db_auth', function () {
        $host   = getenv('DB_PG_HOST') ?: 'localhost';
        $port   = getenv('DB_PG_PORT') ?: '5432';
        $dbname = getenv('DB_PG_DATABASE');
        $user   = getenv('DB_PG_USER');
        $pass   = getenv('DB_PG_PASS');

        if (!$dbname || !$user) {
            throw new \Exception("Configuración de PostgreSQL incompleta en variables de entorno.");
        }

        $conn_pg = @pg_connect("host={$host} port={$port} dbname={$dbname} user={$user} password={$pass}");

        if (!$conn_pg) {
            error_log("Error conexión PostgreSQL ACL: " . pg_last_error());
            throw new \Exception("Error interno: No se pudo conectar con el servidor PostgreSQL.");
        }

        return $conn_pg;
    });
};