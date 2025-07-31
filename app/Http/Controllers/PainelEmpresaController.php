<?php
require_once __DIR__ . '/../../Database/Connection.php';
require_once __DIR__ . '/../../Utils/Email.php';

class PainelEmpresaController
{
    /**
     * Mostra o dashboard principal para os utilizadores da empresa.
     */
    public function showDashboard()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            header('Location: /login');
            exit();
        }
        try {
            $pdo = Connection::getPdo();
            $companyId = $_SESSION['company_id'];

            // Lógica das Estatísticas
            $stmtVagasAbertas = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE company_id = ? AND status = 'aberta'");
            $stmtVagasAbertas->execute([$companyId]);
            $totalVagasAbertas = $stmtVagasAbertas->fetchColumn();

            $stmtLojas = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE company_id = ? AND status = 1");
            $stmtLojas->execute([$companyId]);
            $totalLojas = $stmtLojas->fetchColumn();

            $stmtVagasOcupadas = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE company_id = ? AND status = 'preenchida'");
            $stmtVagasOcupadas->execute([$companyId]);
            $totalVagasOcupadas = $stmtVagasOcupadas->fetchColumn();
            
            $stats = [
                'vagas_abertas' => $totalVagasAbertas,
                'lojas_ativas' => $totalLojas,
                'vagas_ocupadas' => $totalVagasOcupadas
            ];
            
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
            $stmtStores->execute([$companyId]);
            $stores = $stmtStores->fetchAll();
            
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

            $stmtRequests = $pdo->prepare("
                SELECT COUNT(t.id) 
                FROM training_requests t
                JOIN stores s ON t.store_id = s.id
                WHERE s.company_id = ? AND t.status IN ('solicitado', 'agendado')
            ");
            $stmtRequests->execute([$companyId]);
            $pendingTrainingRequests = $stmtRequests->fetchColumn();

        } catch (\PDOException $e) {
            $stores = [];
            $settings = [];
            $stats = ['vagas_abertas' => 0, 'lojas_ativas' => 0, 'vagas_ocupadas' => 0];
            $pendingTrainingRequests = 0;
        }
        
        require_once __DIR__ . '/../../Views/empresa/dashboard.php';
    }

    /**
     * Nível 1 da Gestão de Vagas: Mostra as LOJAS que têm vagas.
     */
    public function indexVagas()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            header('Location: /login'); 
            exit();
        }
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("
                SELECT DISTINCT s.id, s.name 
                FROM stores s
                JOIN shifts sh ON s.id = sh.store_id
                WHERE s.company_id = ? AND s.status = 1
                ORDER BY s.name ASC
            ");
            $stmt->execute([$_SESSION['company_id']]);
            $storesWithShifts = $stmt->fetchAll();
            require_once __DIR__ . '/../../Views/empresa/vagas/selecionar_loja.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as lojas com vagas: ' . $e->getMessage());
        }
    }

    /**
     * Nível 2 da Gestão de Vagas: Mostra os DIAS que têm vagas para uma loja.
     */
    public function showDaysForStore()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            header('Location: /login'); 
            exit();
        }
        $storeId = $_GET['store_id'] ?? null;
        if (!$storeId) die('ID da loja não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT id, name FROM stores WHERE id = ? AND company_id = ?");
            $stmtStore->execute([$storeId, $_SESSION['company_id']]);
            $store = $stmtStore->fetch();
            if (!$store) die('Loja não encontrada ou acesso não permitido.');

            $stmtDates = $pdo->prepare("
                SELECT DISTINCT shift_date 
                FROM shifts 
                WHERE store_id = ?
                ORDER BY shift_date ASC
            ");
            $stmtDates->execute([$storeId]);
            $dates = $stmtDates->fetchAll(PDO::FETCH_COLUMN, 0);
            require_once __DIR__ . '/../../Views/empresa/vagas/selecionar_dia.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os dias com vagas: ' . $e->getMessage());
        }
    }

    /**
     * Nível 3 da Gestão de Vagas: Mostra as VAGAS de um dia.
     */
    public function showShiftsByDay()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            header('Location: /login'); 
            exit();
        }
        $storeId = $_GET['store_id'] ?? null;
        $date = $_GET['date'] ?? null;
        if (!$storeId || !$date) die('ID da loja e data são obrigatórios.');
        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT s.name as store_name FROM stores s WHERE s.id = ? AND s.company_id = ?");
            $stmtStore->execute([$storeId, $_SESSION['company_id']]);
            $storeInfo = $stmtStore->fetch();
            if (!$storeInfo) die('Loja não encontrada ou acesso não permitido.');

            $stmtShifts = $pdo->prepare("
                SELECT s.*, (SELECT COUNT(*) FROM shift_applications WHERE shift_id = s.id AND status = 'aprovado') as approved_count
                FROM shifts s
                WHERE s.store_id = ? AND s.shift_date = ?
                GROUP BY s.id
                ORDER BY s.start_time ASC
            ");
            $stmtShifts->execute([$storeId, $date]);
            $shifts = $stmtShifts->fetchAll();
            
            setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
            $timezone = new DateTimeZone('America/Sao_Paulo');
            $dateObj = new DateTime($date, $timezone);
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, 'EEEE, d \'de\' MMMM \'de\' yyyy');
            $formattedDate = ucfirst($formatter->format($dateObj));

            require_once __DIR__ . '/../../Views/empresa/vagas/listar_por_dia.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as vagas do dia: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a lista de operadores confirmados (ou que cancelaram) para uma vaga.
     */
    public function showApplicants()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            header('Location: /login'); exit();
        }
        $shiftId = $_GET['shift_id'] ?? null;
        if (!$shiftId) die('ID da vaga não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $stmtShift = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND company_id = ?");
            $stmtShift->execute([$shiftId, $_SESSION['company_id']]);
            $shift = $stmtShift->fetch();
            if (!$shift) die('Vaga não encontrada ou acesso não permitido.');
            
            $stmtApplicants = $pdo->prepare("
                SELECT o.id, o.name, o.pontuacao, o.path_selfie, sa.status as application_status, sa.id as application_id
                FROM shift_applications sa
                JOIN operators o ON sa.operator_id = o.id
                WHERE sa.shift_id = ? AND sa.status IN ('aprovado', 'cancelado_operador', 'check_in', 'concluido', 'no_show')
                ORDER BY sa.status ASC, sa.applied_at ASC
            ");
            $stmtApplicants->execute([$shiftId]);
            $allApplicants = $stmtApplicants->fetchAll();

            $applicantsByStatus = [
                'confirmados' => [], 'em_turno' => [], 'concluidos' => [],
                'cancelados' => [], 'faltas' => []
            ];

            foreach ($allApplicants as $applicant) {
                switch ($applicant['application_status']) {
                    case 'aprovado': $applicantsByStatus['confirmados'][] = $applicant; break;
                    case 'check_in': $applicantsByStatus['em_turno'][] = $applicant; break;
                    case 'concluido': $applicantsByStatus['concluidos'][] = $applicant; break;
                    case 'cancelado_operador': $applicantsByStatus['cancelados'][] = $applicant; break;
                    case 'no_show': $applicantsByStatus['faltas'][] = $applicant; break;
                }
            }
            
            require_once __DIR__ . '/../../Views/empresa/vagas/candidatos.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os candidatos: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra a nova página de formulário para criar uma vaga.
     */
    public function showCreateVagaForm()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $pdo = Connection::getPdo();
        $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
        $stmtStores->execute([$_SESSION['company_id']]);
        $stores = $stmtStores->fetchAll();

        require_once __DIR__ . '/../../Views/empresa/vagas/criar.php';
    }

    /**
     * Guarda uma nova vaga na base de dados (Apenas Gerente).
     */
    public function storeVaga()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');
        
        $store_id = $_POST['store_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $shift_date_from_form = $_POST['shift_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $num_positions = $_POST['num_positions'] ?? 0;
        $is_holiday = isset($_POST['is_holiday']) ? 1 : 0;
        
        if (empty($store_id) || empty($title) || empty($shift_date_from_form) || empty($start_time) || empty($end_time) || $num_positions <= 0) {
            die('Erro: Todos os campos obrigatórios devem ser preenchidos.');
        }

        // --- CORREÇÃO E VALIDAÇÃO DA DATA ---
        $dateObject = DateTime::createFromFormat('d/m/Y', $shift_date_from_form);
        if (!$dateObject || $dateObject->format('d/m/Y') !== $shift_date_from_form) {
            die('Erro: Formato de data inválido ou data inexistente. Use o formato DD/MM/AAAA.');
        }
        $shift_date_for_db = $dateObject->format('Y-m-d'); // Converte para AAAA-MM-DD para o MySQL
        // --- FIM DA CORREÇÃO ---

        $submittedYear = (int)$dateObject->format('Y');
        $currentYear = (int)date('Y');
        $maxYear = $currentYear + 5;

        if ($submittedYear < $currentYear || $submittedYear > $maxYear) {
            die("Erro: O ano {$submittedYear} é inválido. Apenas anos entre {$currentYear} e {$maxYear} são permitidos.");
        }
        
        $pdo = Connection::getPdo();
        try {
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $valorBase = (float) str_replace(',', '.', $settings['valor_minimo_turno_6h'] ?? '60.00');
            $operator_payment = $valorBase;
            if ($is_holiday) {
                $operator_payment += 25.00;
            }
            $company_cost = $operator_payment + $taxa_servico;
            $description = "O operador será responsável pela operação do caixa...";
            $sql = "INSERT INTO shifts (company_id, store_id, created_by_user_id, title, description, shift_date, start_time, end_time, operator_payment, company_cost, num_positions, is_holiday) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_SESSION['company_id'], $store_id, $_SESSION['user_id'],
                $title, $description, $shift_date_for_db, $start_time, $end_time,
                $operator_payment, $company_cost, $num_positions, $is_holiday
            ]);
            header('Location: /painel/empresa?status=success');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao publicar a vaga: ' . $e->getMessage());
        }
    }

    /**
     * Guarda um lote de novas vagas na base de dados.
     */
    public function storeBatchShifts()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $store_id = $_POST['store_id'] ?? null;
        $shift_date = $_POST['shift_date'] ?? null;
        $shiftsData = $_POST['shifts'] ?? [];

        if (empty($store_id) || empty($shift_date) || empty($shiftsData)) {
            die('Erro: Dados insuficientes para criar as vagas.');
        }
        
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $valorBase = (float) str_replace(',', '.', $settings['valor_minimo_turno_6h'] ?? '60.00');

            foreach ($shiftsData as $shift) {
                $start_time = $shift['start_time'];
                $end_time = $shift['end_time'];
                $num_positions = $shift['num_positions'];

                if (empty($start_time) || empty($end_time) || empty($num_positions)) {
                    continue; // Pula linhas vazias
                }
                
                $operator_payment = $valorBase;
                $company_cost = $operator_payment + $taxa_servico;
                
                $sql = "INSERT INTO shifts (company_id, store_id, created_by_user_id, title, shift_date, start_time, end_time, operator_payment, company_cost, num_positions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $_SESSION['company_id'], $store_id, $_SESSION['user_id'],
                    'Operador de Caixa', $shift_date, $start_time, $end_time,
                    $operator_payment, $company_cost, $num_positions
                ]);
            }

            $pdo->commit();
            header('Location: /painel/empresa?status=success_batch');
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao publicar as vagas em lote: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário de edição para uma vaga específica (Apenas Gerente).
     */
    public function editVaga()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');
        
        $shiftId = $_GET['id'] ?? null;
        if (!$shiftId) die('ID da vaga não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND company_id = ?");
            $stmt->execute([$shiftId, $_SESSION['company_id']]);
            $shift = $stmt->fetch();
            if (!$shift) { http_response_code(404); die('Vaga não encontrada ou acesso não permitido.'); }
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            require_once __DIR__ . '/../../Views/empresa/vagas/editar.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar dados da vaga: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza os dados de uma vaga na base de dados (Apenas Gerente).
     */
    public function updateVaga()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');
        
        $shiftId = $_POST['id'] ?? null;
        $store_id = $_POST['store_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $shift_date_for_db = $_POST['shift_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $num_positions = $_POST['num_positions'] ?? 0;
        $is_holiday = isset($_POST['is_holiday']) ? 1 : 0;
        
        if (!$shiftId || empty($store_id) || empty($title) || empty($shift_date_for_db) || empty($start_time) || empty($end_time) || $num_positions == 0) {
            die('Erro: Todos os campos obrigatórios devem ser preenchidos.');
        }
        $pdo = Connection::getPdo();
        try {
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $valorBase = (float) str_replace(',', '.', $settings['valor_minimo_turno_6h'] ?? '60.00');
            $operator_payment = $valorBase;
            if ($is_holiday) {
                $operator_payment += 25.00;
            }
            $company_cost = $operator_payment + $taxa_servico;
            $sql = "UPDATE shifts SET store_id = ?, title = ?, shift_date = ?, start_time = ?, end_time = ?, num_positions = ?, is_holiday = ?, operator_payment = ?, company_cost = ? WHERE id = ? AND company_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $store_id, $title, $shift_date_for_db, $start_time, $end_time,
                $num_positions, $is_holiday, $operator_payment, $company_cost,
                $shiftId, $_SESSION['company_id']
            ]);
            header('Location: /painel/vagas');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao atualizar a vaga: ' . $e->getMessage());
        }
    }

    /**
     * Cancela uma vaga (Apenas Gerente).
     */
    public function cancelVaga()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $shiftId = $_GET['id'] ?? null;
        if (!$shiftId) die('ID da vaga não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM shift_applications WHERE shift_id = ? AND status = 'aprovado'");
            $stmtCheck->execute([$shiftId]);
            if ($stmtCheck->fetchColumn() > 0) {
                die('Erro: Esta vaga não pode ser cancelada porque já existem operadores aprovados.');
            }
            $stmtReject = $pdo->prepare("UPDATE shift_applications SET status = 'rejeitado' WHERE shift_id = ? AND status = 'pendente'");
            $stmtReject->execute([$shiftId]);
            $stmtUpdate = $pdo->prepare("UPDATE shifts SET status = 'cancelada' WHERE id = ? AND company_id = ?");
            $stmtUpdate->execute([$shiftId, $_SESSION['company_id']]);
            $pdo->commit();
            header('Location: /painel/vagas');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao cancelar a vaga: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra a lista de solicitações de treinamento (Apenas Gerente).
     */
    public function listTrainingRequests()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        try {
            $pdo = Connection::getPdo();
            $companyId = $_SESSION['company_id'];
            $stmt = $pdo->prepare("
                SELECT 
                    tr.id as training_id, tr.status as training_status, tr.request_date as requested_at,
                    tr.scheduled_date as training_date, tr.scheduled_slot as training_slot,
                    o.name as operator_name, o.phone as operator_phone, o.email as operator_email,
                    s.name as store_name,
                    erp.name as erp_name
                FROM training_requests tr
                JOIN operators o ON tr.operator_id = o.id
                JOIN stores s ON tr.store_id = s.id
                JOIN erp_systems erp ON tr.erp_system_id = erp.id
                WHERE s.company_id = ?
                ORDER BY tr.request_date DESC
            ");
            $stmt->execute([$companyId]);
            $requests = $stmt->fetchAll();
            require_once __DIR__ . '/../../Views/empresa/treinamentos/listar.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as solicitações de treinamento: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa a aprovação ou rejeição de um treinamento (Apenas Gerente).
     */
    public function processTrainingRequest()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $trainingId = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? '';
        $managerId = $_SESSION['user_id'];
        $companyId = $_SESSION['company_id'];
        if (!$trainingId || !in_array($action, ['approve', 'reject'])) {
            die('Ação inválida ou ID do treinamento em falta.');
        }
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT t.id, t.operator_id, t.store_id, o.name as operator_name, o.email as operator_email, s.erp_system_id
                FROM training_requests t
                JOIN stores s ON t.store_id = s.id
                JOIN operators o ON t.operator_id = o.id
                WHERE t.id = ? AND s.company_id = ? AND t.status IN ('solicitado', 'agendado')
            ");
            $stmt->execute([$trainingId, $companyId]);
            $training = $stmt->fetch();
            if (!$training) {
                die('Solicitação de treinamento não encontrada ou já processada.');
            }
            $operatorId = $training['operator_id'];
            $storeId = $training['store_id'];
            $erpSystemId = $training['erp_system_id'];
            if ($action === 'approve') {
                $stmtQualify = $pdo->prepare( "INSERT INTO operator_qualifications (operator_id, erp_system_id, approved_by_user_id, store_id) VALUES (?, ?, ?, ?)" );
                $stmtQualify->execute([$operatorId, $erpSystemId, $managerId, $storeId]);
                $stmtActivate = $pdo->prepare("UPDATE operators SET status = 'ativo' WHERE id = ?");
                $stmtActivate->execute([$operatorId]);
                $stmtTraining = $pdo->prepare("UPDATE training_requests SET status = 'concluido_aprovado' WHERE id = ?");
                $stmtTraining->execute([$trainingId]);
                $subject = "Parabéns! Você foi aprovado no treinamento!";
                $body = "<h1>Olá, ".htmlspecialchars($training['operator_name'])."!</h1><p>O seu treinamento foi aprovado. Você já está qualificado e pode começar a ver e candidatar-se a vagas na plataforma.</p><p><strong>Equipe TURNY</strong></p>";
            } else { // action === 'reject'
                $stmtTraining = $pdo->prepare("UPDATE training_requests SET status = 'concluido_reprovado' WHERE id = ?");
                $stmtTraining->execute([$trainingId]);
                $subject = "Resultado do seu treinamento na TURNY";
                $body = "<h1>Olá, ".htmlspecialchars($training['operator_name'])."!</h1><p>Após a conclusão do seu treinamento, informamos que desta vez não foi possível avançar com a sua qualificação. Você pode solicitar um novo treinamento em outra loja, se desejar.</p><p><strong>Equipe TURNY</strong></p>";
            }
            $pdo->commit();
            Email::sendEmail($training['operator_email'], $training['operator_name'], $subject, $body);
            header('Location: /painel/treinamentos');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao processar a solicitação: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza o status de uma candidatura específica (check-in, no-show, etc.).
     */
    public function updateApplicationStatus()
    {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['gerente', 'recepcionista'])) {
            die('Acesso negado.');
        }

        $applicationId = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? '';
        $validActions = ['check_in', 'no_show', 'complete'];

        if (!$applicationId || !in_array($action, $validActions)) {
            die('Ação inválida ou ID da candidatura em falta.');
        }

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            $stmtApp = $pdo->prepare("SELECT id, operator_id, shift_id FROM shift_applications WHERE id = ?");
            $stmtApp->execute([$applicationId]);
            $application = $stmtApp->fetch();
            if (!$application) die('Candidatura não encontrada.');
            
            $operatorId = $application['operator_id'];
            $newStatus = '';

            switch ($action) {
                case 'check_in':
                    $newStatus = 'check_in';
                    break;
                case 'no_show':
                    $newStatus = 'no_show';
                    $suspensionEndDate = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->modify('+15 days')->format('Y-m-d');
                    $reason = "Falta não justificada no turno #" . $application['shift_id'];
                    $stmtSuspend = $pdo->prepare("UPDATE operators SET suspended_until = ?, suspension_reason = ? WHERE id = ?");
                    $stmtSuspend->execute([$suspensionEndDate, $reason, $operatorId]);
                    break;
                case 'complete':
                    $newStatus = 'concluido';
                    break;
            }

            $stmtUpdate = $pdo->prepare("UPDATE shift_applications SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $applicationId]);

            $pdo->commit();

            header('Location: /painel/vagas/candidatos?shift_id=' . $application['shift_id']);
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao atualizar o status da candidatura: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa a finalização de um turno, incluindo avaliação e descontos.
     */
    public function processShiftCompletion()
    {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['gerente', 'recepcionista'])) {
            die('Acesso negado.');
        }

        $applicationId = $_POST['application_id'] ?? null;
        $rating = $_POST['rating'] ?? null;
        $cashDiscrepancy = $_POST['cash_discrepancy'] ?? 0;
        $comment = $_POST['comment'] ?? '';
        $completingUserId = $_SESSION['user_id'];

        if (!$applicationId || !$rating) {
            die('Dados da avaliação em falta.');
        }

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            $stmtApp = $pdo->prepare("
                SELECT sa.id, sa.operator_id, sa.shift_id, s.operator_payment 
                FROM shift_applications sa
                JOIN shifts s ON sa.shift_id = s.id
                WHERE sa.id = ?
            ");
            $stmtApp->execute([$applicationId]);
            $application = $stmtApp->fetch();
            if (!$application) die('Candidatura não encontrada.');

            $operatorId = $application['operator_id'];
            $shiftId = $application['shift_id'];

            $stmtRating = $pdo->prepare(
                "INSERT INTO shift_ratings (shift_id, application_id, rated_by_user_id, rated_operator_id, rating_for_operator, comment_for_operator) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmtRating->execute([$shiftId, $applicationId, $completingUserId, $operatorId, $rating, $comment]);

            $taxaFixa = (float)($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'taxa_servico_fixa'")->fetchColumn() ?: 5.00);
            $initialOperatorPayment = (float)$application['operator_payment'];
            $discrepancyValue = (float)str_replace(',', '.', $cashDiscrepancy);
            
            $finalOperatorPayment = $initialOperatorPayment - $discrepancyValue;
            $finalCompanyCost = $finalOperatorPayment + $taxaFixa;

            $stmtUpdateApp = $pdo->prepare(
                "UPDATE shift_applications SET status = 'concluido', cash_discrepancy = ?, final_operator_payment = ?, final_company_cost = ? WHERE id = ?"
            );
            $stmtUpdateApp->execute([$discrepancyValue, $finalOperatorPayment, $finalCompanyCost, $applicationId]);

            $stmtAvg = $pdo->prepare("SELECT AVG(rating_for_operator) FROM shift_ratings WHERE rated_operator_id = ?");
            $stmtAvg->execute([$operatorId]);
            $newAverageRating = $stmtAvg->fetchColumn();
            
            if ($newAverageRating) {
                $stmtOpUpdate = $pdo->prepare("UPDATE operators SET pontuacao = ? WHERE id = ?");
                $stmtOpUpdate->execute([$newAverageRating, $operatorId]);
            }
            
            $pdo->commit();

            header('Location: /painel/vagas/candidatos?shift_id=' . $shiftId . '&status=completion_success');
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao finalizar o turno: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a página para gerir os templates de turno.
     */
    public function showShiftTemplates()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $pdo = Connection::getPdo();
        try {
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();

            $stmtTemplates = $pdo->prepare("
                SELECT st.*, s.name as store_name 
                FROM shift_templates st
                JOIN stores s ON st.store_id = s.id
                WHERE s.company_id = ?
                ORDER BY s.name, st.start_time
            ");
            $stmtTemplates->execute([$_SESSION['company_id']]);
            $templates = $stmtTemplates->fetchAll();

            require_once __DIR__ . '/../../Views/empresa/vagas/gerir_templates.php';
        } catch (\PDOException $e) {
            die('Erro ao carregar a página de templates: ' . $e->getMessage());
        }
    }

    /**
     * Guarda um novo template de turno na base de dados.
     */
    public function storeShiftTemplate()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $store_id = $_POST['store_id'] ?? null;
        $title = $_POST['title'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        if (!$store_id || !$title || !$start_time || !$end_time) {
            die('Todos os campos são obrigatórios.');
        }

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare("INSERT INTO shift_templates (store_id, title, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$store_id, $title, $start_time, $end_time]);
            header('Location: /painel/vagas/templates');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao guardar o template: ' . $e->getMessage());
        }
    }

    /**
     * Apaga um template de turno.
     */
    public function deleteShiftTemplate()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $template_id = $_GET['id'] ?? null;
        if (!$template_id) die('ID do template não fornecido.');

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare("
                SELECT st.id FROM shift_templates st
                JOIN stores s ON st.store_id = s.id
                WHERE st.id = ? AND s.company_id = ?
            ");
            $stmt->execute([$template_id, $_SESSION['company_id']]);
            if (!$stmt->fetch()) {
                die('Template não encontrado ou acesso não permitido.');
            }

            $stmtDelete = $pdo->prepare("DELETE FROM shift_templates WHERE id = ?");
            $stmtDelete->execute([$template_id]);

            header('Location: /painel/vagas/templates');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao apagar o template: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra a nova página do Planeador Semanal.
     */
    public function showPlanner()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $pdo = Connection::getPdo();
        try {
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();

            $stmtTemplates = $pdo->prepare("
                SELECT st.*, s.name as store_name 
                FROM shift_templates st
                JOIN stores s ON st.store_id = s.id
                WHERE s.company_id = ? AND st.is_active = 1
                ORDER BY s.name, st.start_time
            ");
            $stmtTemplates->execute([$_SESSION['company_id']]);
            $templates = $stmtTemplates->fetchAll();

            require_once __DIR__ . '/../../Views/empresa/vagas/planear.php';
        } catch (\PDOException $e) {
            die('Erro ao carregar a página do planeador: ' . $e->getMessage());
        }
    }


    /**
     * Guarda um planeamento semanal de vagas na base de dados.
     */
    public function storeWeeklyPlan()
    {
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gerente') die('Acesso negado.');

        $store_id = $_POST['store_id'] ?? null;
        $plan = $_POST['plan'] ?? [];

        if (empty($store_id) || empty($plan)) {
            header('Location: /painel/vagas/planear?status=nothing_to_publish');
            exit();
        }
        
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $valorBase = (float) str_replace(',', '.', $settings['valor_minimo_turno_6h'] ?? '60.00');

            $templateIds = array_keys(array_reduce($plan, 'array_merge', []));
            if (empty($templateIds)) {
                $pdo->rollBack();
                header('Location: /painel/vagas/planear?status=nothing_to_publish');
                exit();
            }

            $placeholders = implode(',', array_fill(0, count($templateIds), '?'));
            $stmtTemplates = $pdo->prepare("SELECT * FROM shift_templates WHERE id IN ($placeholders)");
            $stmtTemplates->execute($templateIds);
            
            // --- CORREÇÃO APLICADA AQUI ---
            $templatesData = $stmtTemplates->fetchAll(PDO::FETCH_ASSOC);
            $templates = [];
            foreach ($templatesData as $template) {
                $templates[$template['id']] = $template;
            }
            // --- FIM DA CORREÇÃO ---

            foreach ($plan as $date => $shiftsOnDate) {
                foreach ($shiftsOnDate as $templateId => $num_positions) {
                    if ((int)$num_positions <= 0 || !isset($templates[$templateId])) continue;

                    $template = $templates[$templateId];
                    
                    $operator_payment = $valorBase;
                    $company_cost = $operator_payment + $taxa_servico;
                    
                    $sql = "INSERT INTO shifts (company_id, store_id, created_by_user_id, title, shift_date, start_time, end_time, operator_payment, company_cost, num_positions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_SESSION['company_id'], $store_id, $_SESSION['user_id'],
                        $template['title'], $date, $template['start_time'], $template['end_time'],
                        $operator_payment, $company_cost, $num_positions
                    ]);
                }
            }

            $pdo->commit();
            header('Location: /painel/vagas/planear?status=success_batch');
            exit();

        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao publicar o planeamento semanal: ' . $e->getMessage());
        }
    }
}