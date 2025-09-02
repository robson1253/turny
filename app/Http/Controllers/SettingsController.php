<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDO;
use Exception;
use Throwable;

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
            
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Busca todas as colunas necessárias da tabela de funções
            $jobFunctions = $pdo->query("SELECT id, name, hourly_rate, max_hours FROM job_functions ORDER BY name ASC")->fetchAll();
            
            $this->view('admin/settings/index', [
                'settings' => $settings,
                'jobFunctions' => $jobFunctions
            ]);

        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * Atualiza as configurações nas tabelas settings e job_functions.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        \verify_csrf_token();

        $settings = $_POST['settings'] ?? [];
        $functions = $_POST['functions'] ?? [];

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            // 1. Atualiza as configurações gerais (taxa, bónus) na tabela 'settings'
            $sqlSettings = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmtSettings = $pdo->prepare($sqlSettings);
            foreach ($settings as $key => $value) {
                // Sanitiza o valor monetário (aceita vírgula e ponto como decimal)
                $cleanValue = preg_replace('/[^\d,.]/', '', $value);
                $cleanValue = str_replace('.', '', $cleanValue);
                $cleanValue = str_replace(',', '.', $cleanValue);
                $stmtSettings->execute([$cleanValue, $key]);
            }

            // 2. Atualiza os valores por hora e máx. de horas na tabela 'job_functions'
            $sqlFunctions = "UPDATE job_functions SET hourly_rate = ?, max_hours = ? WHERE id = ?";
            $stmtFunctions = $pdo->prepare($sqlFunctions);
            
            foreach ($functions as $id => $values) {
                // Sanitiza os valores antes de salvar
                $cleanRate = preg_replace('/[^\d,.]/', '', $values['hourly_rate'] ?? '0');
                $cleanRate = str_replace('.', '', $cleanRate);
                $cleanRate = str_replace(',', '.', $cleanRate);
                
                $cleanMaxHours = (float) ($values['max_hours'] ?? 7.00);

                $stmtFunctions->execute([(float)$cleanRate, $cleanMaxHours, $id]);
            }

            $pdo->commit();

            \flash('Configurações guardadas com sucesso!', 'success');
            header('Location: /admin/settings');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}