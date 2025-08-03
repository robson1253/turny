<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDO;
use PDOException;
use Exception;

// A classe agora herda do nosso BaseController
class ApiController extends BaseController
{
    /**
     * Helper method to send a standardized JSON response and exit.
     * @param mixed $data The data to encode.
     * @param int $statusCode The HTTP status code.
     */
    private function jsonResponse($data, int $statusCode = 200)
    {
        // Limpa qualquer saída de buffer anterior para garantir uma resposta JSON limpa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit();
    }

    /**
     * Busca os dados de um CEP usando a API do ViaCEP no lado do servidor.
     */
    public function lookupCep()
    {
        $cep = preg_replace('/[^0-9]/', '', $_GET['cep'] ?? '');

        if (strlen($cep) !== 8) {
            $this->jsonResponse(['error' => 'CEP inválido.'], 400); // Bad Request
        }

        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $response = @file_get_contents($url);

        if ($response === false) {
            $this->jsonResponse(['error' => 'Não foi possível contactar o serviço de CEP.'], 503); // Service Unavailable
        }
        
        // A resposta do ViaCEP já é JSON, então apenas a retransmitimos.
        header('Content-Type: application/json; charset=utf-8');
        echo $response;
        exit();
    }

    /**
     * Busca as vagas de uma empresa para alimentar o calendário (FullCalendar).
     */
    public function getShifts()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            $this->jsonResponse(['error' => 'Acesso não autorizado'], 403); // Forbidden
        }

        try {
            $pdo = Connection::getPdo();
            
            $sql = "SELECT s.*, st.name as store_name 
                    FROM shifts s
                    JOIN stores st ON s.store_id = st.id
                    WHERE s.company_id = ?";
            $params = [$_SESSION['company_id']];

            if (!empty($_GET['store_id'])) {
                $sql .= " AND s.store_id = ?";
                $params[] = $_GET['store_id'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $shifts = $stmt->fetchAll();

            $events = [];
            foreach ($shifts as $shift) {
                $events[] = [
                    'id'    => $shift['id'],
                    'title'   => $shift['title'] . ' (' . htmlspecialchars($shift['store_name']) . ')',
                    'start'   => $shift['shift_date'] . 'T' . $shift['start_time'],
                    'end'     => $shift['shift_date'] . 'T' . $shift['end_time'],
                    'color'   => $shift['status'] === 'cancelada' ? '#dc3545' : '#016e57',
                    'extendedProps' => [
                        'status' => $shift['status'],
                        'num_positions' => $shift['num_positions']
                    ]
                ];
            }

            $this->jsonResponse($events);

        } catch (PDOException $e) {
            // Lança a exceção para ser capturada pelo manipulador global.
            throw $e;
        }
    }

    /**
     * Verifica e retorna os horários de treinamento disponíveis.
     */
    public function getAvailableTrainingSlots()
    {
        if (!isset($_SESSION['operator_id'])) {
            $this->jsonResponse(['error' => 'Acesso não autorizado'], 403);
        }

        $storeId = $_GET['store_id'] ?? null;
        $date = $_GET['date'] ?? null;

        if (!$storeId || !$date) {
            $this->jsonResponse(['error' => 'ID da loja e data são obrigatórios.'], 400);
        }

        try {
            $pdo = Connection::getPdo();
            
            $stmt = $pdo->prepare("SELECT scheduled_slot FROM training_requests WHERE store_id = ? AND scheduled_date = ? AND status IN ('solicitado', 'agendado')");
            $stmt->execute([$storeId, $date]);
            $takenSlots = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $availability = [
                'manha' => !in_array('manha', $takenSlots),
                'tarde' => !in_array('tarde', $takenSlots)
            ];

            $this->jsonResponse($availability);

        } catch (PDOException $e) {
            throw $e;
        }
    }
    
    /**
     * Busca e retorna as lojas que utilizam um sistema ERP específico.
     */
    public function getStoresByErp()
    {
        if (!isset($_SESSION['operator_id'])) {
            $this->jsonResponse(['error' => 'Acesso não autorizado'], 403);
        }

        $erpId = $_GET['erp_id'] ?? null;
        if (!$erpId) {
            $this->jsonResponse(['error' => 'ID do sistema ERP é obrigatório.'], 400);
        }

        try {
            $pdo = Connection::getPdo();
            
            $stmt = $pdo->prepare("
                SELECT id, name, endereco, numero, bairro, cidade, estado 
                FROM stores 
                WHERE erp_system_id = ? AND status = 1
            ");
            $stmt->execute([$erpId]);
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse($stores);

        } catch (PDOException $e) {
            throw $e;
        }
    }
}