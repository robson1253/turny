<?php
require_once __DIR__ . '/../../Database/Connection.php';

class ApiController
{
    /**
     * Busca os dados de um CEP usando a API do ViaCEP no lado do servidor.
     */
    public function lookupCep()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $cep = $_GET['cep'] ?? '';
        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) !== 8) {
            echo json_encode(['erro' => true, 'mensagem' => 'CEP inválido.']);
            exit();
        }

        $url = "https://viacep.com.br/ws/{$cep}/json/";
        $response = @file_get_contents($url);

        if ($response === false) {
            echo json_encode(['erro' => true, 'mensagem' => 'Não foi possível contactar o serviço de CEP.']);
        } else {
            echo $response;
        }
        
        exit();
    }

    /**
     * Busca as vagas de uma empresa para alimentar o calendário (FullCalendar).
     */
    public function getShifts()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não autorizado']);
            exit();
        }

        header('Content-Type: application/json');

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
                    'id'      => $shift['id'],
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

            echo json_encode($events);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na base de dados: ' . $e->getMessage()]);
        }
        exit();
    }

    /**
     * Verifica e retorna os horários de treinamento disponíveis para uma loja numa data específica.
     */
    public function getAvailableTrainingSlots()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['operator_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso não autorizado']);
            exit();
        }

        header('Content-Type: application/json');

        $storeId = $_GET['store_id'] ?? null;
        $date = $_GET['date'] ?? null;

        if (!$storeId || !$date) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'ID da loja e data são obrigatórios.']);
            exit();
        }

        try {
            $pdo = Connection::getPdo();

            // Busca os agendamentos já existentes para esta loja e data com status relevante
            $stmt = $pdo->prepare("SELECT scheduled_slot FROM training_requests WHERE store_id = ? AND scheduled_date = ? AND status IN ('solicitado', 'agendado')");
            $stmt->execute([$storeId, $date]);
            $takenSlots = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Verifica a disponibilidade (máximo de 1 por período)
            $availability = [
                'manha' => !in_array('manha', $takenSlots),
                'tarde' => !in_array('tarde', $takenSlots)
            ];

            echo json_encode($availability);

        } catch (\PDOException $e) {
            http_response_code(500);
            // Em vez de die(), enviamos uma resposta JSON com o erro
            echo json_encode(['error' => 'Erro ao consultar a base de dados.']);
        }
        exit();
    }
    
    /**
     * Busca e retorna as lojas que utilizam um sistema ERP específico, com endereço completo.
     */
    public function getStoresByErp()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!isset($_SESSION['operator_id'])) {
            http_response_code(403); echo json_encode(['error' => 'Acesso não autorizado']); exit();
        }

        header('Content-Type: application/json');

        $erpId = $_GET['erp_id'] ?? null;
        if (!$erpId) {
            echo json_encode(['error' => 'ID do sistema ERP é obrigatório.']);
            exit();
        }

        try {
            $pdo = Connection::getPdo();

            // QUERY ATUALIZADA para buscar o endereço completo
            $stmt = $pdo->prepare("
                SELECT id, name, endereco, numero, bairro, cidade, estado 
                FROM stores 
                WHERE erp_system_id = ? AND status = 1
            ");
            $stmt->execute([$erpId]);
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($stores);

        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erro na base de dados.']);
        }
        exit();
    }
}