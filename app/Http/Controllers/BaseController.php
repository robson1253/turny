<?php

namespace App\Http\Controllers;

/**
 * BaseController
 * Todos os outros controllers devem herdar (extends) desta classe.
 * Contém a lógica comum a todos os controllers.
 */
abstract class BaseController
{
    /**
     * Renderiza um arquivo de view e passa dados para ele.
     *
     * @param string $path O caminho para o arquivo da view a partir da pasta 'Views'.
     * @param array $data Um array associativo de dados para serem extraídos como variáveis na view.
     */
    protected function view(string $path, array $data = [])
    {
        // Garante que as variáveis passadas não sobrescrevam variáveis existentes importantes.
        extract($data, EXTR_SKIP);

        // Constrói o caminho completo para o arquivo da view.
        $fullPath = __DIR__ . "/../../Views/{$path}.php";

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            // Em vez de um 'die' simples, poderíamos lançar uma exceção.
            // Por agora, um erro claro é suficiente.
            die("Erro: View não encontrada em '{$fullPath}'");
        }
    }
}