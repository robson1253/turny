<?php

// Ficheiro: app/Database/Connection.php
// Responsável por criar e retornar uma conexão com a base de dados.

class Connection
{
    private static $pdo;

    public static function getPdo()
    {
        if (!isset(self::$pdo)) {
            // 1. Carrega as configurações do nosso ficheiro config/database.php
            $config = require __DIR__ . '/../../config/database.php';

            // 2. Monta a "string de conexão" (DSN)
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";

            try {
                // 3. Tenta criar a conexão PDO
                self::$pdo = new PDO($dsn, $config['user'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                // Se a conexão falhar, termina o script e mostra o erro.
                die('Erro de conexão com a base de dados: ' . $e->getMessage());
            }
        }

        // 4. Retorna a conexão estabelecida
        return self::$pdo;
    }
}