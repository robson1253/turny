<?php
require_once __DIR__ . '/../../Database/Connection.php';

class PainelAdminEmpresaController
{
    /**
     * Mostra o dashboard principal do Administrador da Empresa com as métricas.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
            header('Location: /login');
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            $companyId = $_SESSION['company_id'];

            // 1. Contar o total de vagas já criadas por esta empresa
            $stmtVagas = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE company_id = ?");
            $stmtVagas->execute([$companyId]);
            $totalVagas = $stmtVagas->fetchColumn();

            // 2. Contar o total de lojas ativas desta empresa
            $stmtLojas = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE company_id = ? AND status = 1");
            $stmtLojas->execute([$companyId]);
            $totalLojas = $stmtLojas->fetchColumn();

            // 3. Somar o valor de todos os turnos concluídos (simulação de custo)
            $stmtValor = $pdo->prepare("SELECT SUM(company_cost) FROM shifts WHERE company_id = ? AND status = 'concluida'");
            $stmtValor->execute([$companyId]);
            $valorGasto = $stmtValor->fetchColumn();

            // Guarda tudo num array para enviar para a View
            $stats = [
                'total_vagas' => $totalVagas,
                'lojas_ativas' => $totalLojas,
                'valor_gasto' => $valorGasto ?? 0 // Garante 0 se for nulo
            ];

        } catch (\PDOException $e) {
            // Em caso de erro, define valores padrão
            $stats = [
                'total_vagas' => 0,
                'lojas_ativas' => 0,
                'valor_gasto' => 0
            ];
        }
        
        require_once __DIR__ . '/../../Views/empresa_admin/dashboard.php';
    }
}