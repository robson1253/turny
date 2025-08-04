<?php

namespace App\Http\Controllers; // <-- CORREÇÃO AQUI

use App\Database\Connection;
use PDOException;
use Exception;

class PainelAdminEmpresaController extends BaseController
{
    /**
     * Mostra o dashboard principal do Administrador da Empresa com as métricas.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
            throw new Exception('Acesso negado.', 403);
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

            // 3. Somar o valor de todos os turnos concluídos
            $stmtValor = $pdo->prepare("SELECT SUM(final_company_cost) FROM shift_applications WHERE shift_id IN (SELECT id FROM shifts WHERE company_id = ?) AND status = 'concluido'");
            $stmtValor->execute([$companyId]);
            $valorGasto = $stmtValor->fetchColumn();

            // Guarda tudo num array para enviar para a View
            $stats = [
                'total_vagas' => $totalVagas,
                'lojas_ativas' => $totalLojas,
                'valor_gasto' => $valorGasto ?? 0
            ];

            $this->view('empresa_admin/dashboard', ['stats' => $stats]);

        } catch (PDOException $e) {
            throw $e;
        }
    }
}