<?php

namespace App\Http\Controllers;

use Exception;

/**
 * Controller base do qual todos os outros controllers irão herdar.
 * Contém a lógica centralizada para renderizar views e verificar o acesso.
 */
abstract class BaseController
{
    /**
     * Renderiza um arquivo de view, passando dados para ele.
     * @param string $path O caminho para o arquivo de view (ex: 'admin/empresas/listar_empresas').
     * @param array $data Os dados a serem extraídos para a view.
     */
    protected function view(string $path, array $data = [])
    {
        // A função extract() transforma as chaves do array em variáveis.
        // Ex: ['companies' => $allCompanies] torna a variável $companies disponível na view.
        extract($data);
        
        require_once __DIR__ . '/../../Views/' . $path . '.php';
    }

    /**
     * Verifica se o utilizador está logado e tem a permissão necessária.
     * Redireciona para o login se não estiver autenticado.
     * Lança uma exceção se não tiver a permissão correta.
     *
     * @param array $allowedRoles Os perfis que têm permissão (ex: ['admin', 'gerente']).
     * Se o array estiver vazio, apenas verifica se o utilizador está logado.
     */
    protected function checkAccess(array $allowedRoles = [])
    {
        // Verifica se existe uma sessão de utilizador (admin/gerente) OU de operador
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['operator_id'])) {
            header('Location: /login');
            exit();
        }

        // Se a rota exige perfis específicos (como 'admin'), verifica-os
        if (!empty($allowedRoles)) {
            $userRole = $_SESSION['user_role'] ?? null;
            if (!$userRole || !in_array($userRole, $allowedRoles)) {
                // O utilizador está logado, mas não tem permissão.
                throw new Exception('Você não tem permissão para aceder a esta página.', 403);
            }
        }
    }
}