<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDO;
use PDOException;
use Exception;

class SettingsController extends BaseController
{
    /**
     * Mostra a página de configurações com os valores atuais.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }

        try {
            $pdo = Connection::getPdo();
            
            // 1. Busca as configurações gerais
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // 2. Busca todas as funções de trabalho para editar os seus valores
            $jobFunctions = $pdo->query("SELECT id, name, hourly_rate FROM job_functions ORDER BY name ASC")->fetchAll();
            
            // 3. Passa ambos os conjuntos de dados para a view
            $this->view('admin/settings/index', [
                'settings' => $settings,
                'jobFunctions' => $jobFunctions
            ]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza as configurações na base de dados.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();

        $settings = $_POST['settings'] ?? [];
        $functions = $_POST['functions'] ?? [];

        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();

            // 1. Atualiza as configurações gerais (taxa, bónus)
            $sqlSettings = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmtSettings = $pdo->prepare($sqlSettings);
            foreach ($settings as $key => $value) {
                // Sanitiza o valor monetário antes de salvar
                $cleanValue = str_replace(['.', ','], ['', '.'], $value);
                $stmtSettings->execute([$cleanValue, $key]);
            }

            // 2. Atualiza os valores por hora de cada função
            $sqlFunctions = "UPDATE job_functions SET hourly_rate = ? WHERE id = ?";
            $stmtFunctions = $pdo->prepare($sqlFunctions);
            foreach ($functions as $id => $rate) {
                // Sanitiza o valor monetário antes de salvar
                $cleanRate = str_replace(['.', ','], ['', '.'], $rate);
                $stmtFunctions->execute([$cleanRate, $id]);
            }

            $pdo->commit();

            flash('Configurações guardadas com sucesso!');
            header('Location: /admin/settings');
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}