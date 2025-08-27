<?php
// 1. ADICIONA O "ENDEREÇO" CORRETO PARA ESTA CLASSE
namespace App\Database;

// 2. IMPORTA AS CLASSES GLOBAIS DO PHP PARA DENTRO DESTE NAMESPACE
use PDO;
use PDOException;

// Ficheiro: app/Database/Connection.php
// Responsável por criar e retornar uma conexão com a base de dados.
class Connection
{
    private static $pdo;

    public static function getPdo()
    {
        if (!isset(self::$pdo)) {
            // Carrega as configurações do nosso ficheiro config/database.php
            $config = require __DIR__ . '/../../config/database.php';

            // Monta a "string de conexão" (DSN) - LINHA CORRIGIDA
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";

            try {
                // Tenta criar a conexão PDO - LINHA CORRIGIDA
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                // Se a conexão falhar, termina o script e mostra o erro.
                die('Erro de conexão com a base de dados: ' . $e->getMessage());
            }
        }

        // Retorna a conexão estabelecida
        return self::$pdo;
    }
}