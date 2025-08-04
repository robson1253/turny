<?php

require_once __DIR__ . '/../../Database/Connection.php';

class SettingsController
{
    /**
     * Mostra a página de configurações com os valores atuais.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            require_once __DIR__ . '/../../Views/admin/settings/index.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as configurações: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza as configurações na base de dados (VERSÃO DE DEPURAÇÃO).
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $settings = $_POST['settings'] ?? [];

        // --- INÍCIO DO CÓDIGO DE DEPURAÇÃO ---
        echo "<pre style='background: #1d1d1d; color: #f1f1f1; padding: 20px; border-radius: 5px; font-family: monospace;'>";
        echo "<strong>--- INFORMAÇÕES DE DEPURAÇÃO DO UPDATE ---</strong><br><br>";
        
        echo "<strong>1. Dados recebidos do formulário (\$_POST):</strong><br>";
        var_dump($_POST);
        echo "<hr>";

        if (empty($settings)) {
            echo "<strong>ERRO: Nenhum dado de configuração foi recebido.</strong>";
            echo "</pre>";
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = $pdo->prepare($sql);

            echo "<strong>2. A executar os seguintes updates:</strong><br>";
            foreach ($settings as $key => $value) {
                echo " - A tentar atualizar a chave '{$key}' com o valor '{$value}'... ";
                $stmt->execute([$value, $key]);
                // rowCount() diz-nos quantas linhas foram realmente alteradas
                $affectedRows = $stmt->rowCount(); 
                echo "<strong>{$affectedRows} linha(s) afetada(s).</strong><br>";
            }

            echo "<hr>";
            echo "<strong>Processo terminado.</strong><br>";
            echo "Se a contagem de linhas afetadas for 0, significa que a chave não foi encontrada na base de dados.";
            echo "<br><br><a href='/admin/settings' style='color: #87ceeb;'>Voltar para as Configurações</a>";

        } catch (\PDOException $e) {
            echo "<strong>ERRO DE BASE DE DADOS:</strong> " . $e->getMessage();
        }

        echo "</pre>";
        exit(); // Impede o redirecionamento para podermos ver a mensagem
        // --- FIM DO CÓDIGO DE DEPURAÇÃO ---
    }
}