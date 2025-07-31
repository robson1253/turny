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
     * Atualiza as configurações na base de dados. (NOVA FUNÇÃO)
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        // Os dados do formulário vêm num array chamado 'settings'
        $settings = $_POST['settings'] ?? [];

        try {
            $pdo = Connection::getPdo();
            
            // Prepara a query de UPDATE uma vez
            $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = $pdo->prepare($sql);

            // Percorre cada configuração enviada e executa o update
            foreach ($settings as $key => $value) {
                $stmt->execute([$value, $key]);
            }

            // Redireciona de volta para a página de configurações após o sucesso
            header('Location: /admin/settings');
            exit();

        } catch (\PDOException $e) {
            die('Erro ao guardar as configurações: ' . $e->getMessage());
        }
    }
}