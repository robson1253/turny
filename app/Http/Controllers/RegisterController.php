<?php

require_once __DIR__ . '/../../Database/Connection.php';
require_once __DIR__ . '/../../Utils/Validators.php';
require_once __DIR__ . '/../../Utils/Email.php';

class RegisterController
{
    /**
     * Mostra o formulário de registo para novos operadores.
     */
    public function showOperatorForm()
    {
        require_once __DIR__ . '/../../Views/registro/operador.php';
    }

    /**
     * Processa e guarda o registo de um novo operador.
     */
    public function registerOperator()
    {
        // 1. Recolha dos Dados
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $cep = $_POST['cep'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $pix_key_type = $_POST['pix_key_type'] ?? '';
        $pix_key = $_POST['pix_key'] ?? '';
        $terms = $_POST['terms'] ?? '';

        // 2. Validação no Lado do Servidor
        $requiredFields = [$name, $username, $cpf, $phone, $email, $password, $cep, $endereco, $numero, $bairro, $cidade, $estado, $pix_key_type, $pix_key, $terms];
        foreach ($requiredFields as $field) {
            if (empty($field)) {
                die('Erro: Todos os campos são obrigatórios e os termos devem ser aceites.');
            }
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            die('Erro: O @username só pode conter letras (sem acentos), números e underscore (_).');
        }
        if (!Validators::validateCpf($cpf)) {
            die('Erro: O CPF fornecido é inválido.');
        }
        if (empty($_FILES['doc_frente']) || $_FILES['doc_frente']['error'] !== UPLOAD_ERR_OK) {
            die('Erro: A foto da frente do documento é obrigatória.');
        }
        if (empty($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
            die('Erro: A selfie é obrigatória.');
        }

        // 3. Lógica de Upload de Ficheiros
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                die('Erro: Falha ao criar o diretório de uploads. Verifique as permissões.');
            }
        }
        $uploadedFilePaths = [];
        $filesToUpload = [
            'doc_frente' => $_FILES['doc_frente'],
            'doc_verso' => $_FILES['doc_verso'] ?? null,
            'selfie' => $_FILES['selfie'],
        ];
        foreach ($filesToUpload as $key => $file) {
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                $safeFilename = preg_replace('/[^A-Za-z0-9\.\-]/', '', basename($file['name']));
                $fileName = uniqid() . '-' . $safeFilename;
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $uploadedFilePaths[$key] = '/uploads/' . $fileName;
                } else {
                    die("Erro ao mover o ficheiro de upload: {$key}");
                }
            }
        }
        
        // 4. Inserção na Base de Dados
        try {
            $pdo = Connection::getPdo();
            $cpf_cleaned = preg_replace('/[^0-9]/', '', $cpf);

            // Validação extra: verifica se o username, e-mail ou CPF já existem
            $stmtCheck = $pdo->prepare("SELECT id FROM operators WHERE username = ? OR email = ? OR cpf = ?");
            $stmtCheck->execute([$username, $email, $cpf_cleaned]);
            if ($stmtCheck->fetch()) {
                die('Erro: O @username, E-mail ou CPF fornecido já está registado na plataforma.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO operators 
                        (name, username, cpf, phone, email, password, cep, endereco, numero, bairro, cidade, estado, 
                         pix_key_type, pix_key, 
                         path_documento_frente, path_documento_verso, path_selfie,
                         status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente_verificacao')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $name, $username, $cpf_cleaned, $phone, $email, $passwordHash,
                $cep, $endereco, $numero, $bairro, $cidade, $estado,
                $pix_key_type, $pix_key,
                $uploadedFilePaths['doc_frente'] ?? null,
                $uploadedFilePaths['doc_verso'] ?? null,
                $uploadedFilePaths['selfie'] ?? null
            ]);

            // 5. Envio de E-mail de Boas-Vindas
            $emailSubject = "Bem-vindo à TURNY! O seu registo foi recebido.";
            $emailBody = "<h1>Olá, " . htmlspecialchars($name) . "!</h1>
                         <p>Recebemos o seu registo na plataforma TURNY. A nossa equipa irá analisar os seus documentos e em breve receberá um novo e-mail sobre o status da sua conta.</p>
                         <p>Obrigado por se juntar a nós!</p>
                         <p><strong>Equipe TURNY</strong></p>";

            Email::sendEmail($email, $name, $emailSubject, $emailBody);

            // 6. Redirecionamento Final
            header('Location: /login?status=register_success');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao guardar o operador: ' . $e->getMessage());
        }
    }
}