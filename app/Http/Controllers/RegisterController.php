<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Validators;
use App\Utils\Email;
use PDOException;
use Exception;

class RegisterController extends BaseController
{
    /**
     * Mostra o formulário de registo para novos operadores.
     */
    public function showOperatorForm()
    {
        $this->view('registro/operador');
    }

    /**
     * Processa e guarda o registo de um novo operador.
     */
    public function registerOperator()
    {
        verify_csrf_token();

        // 1. Recolha e Limpeza dos Dados
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $pix_key_type = $_POST['pix_key_type'] ?? '';
        $pix_key = trim($_POST['pix_key'] ?? '');
        $terms = $_POST['terms'] ?? '';

        // 2. Validação Robusta no Lado do Servidor
        if (empty($name) || empty($username) || empty($cpf) || empty($phone) || empty($email) || empty($password) || empty($cep) || empty($endereco) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado) || empty($pix_key_type) || empty($pix_key) || empty($terms)) {
            throw new Exception('Todos os campos são obrigatórios e os termos devem ser aceites.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('O e-mail fornecido é inválido.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('O @username só pode conter letras (sem acentos), números e underscore (_).');
        }
        if (!Validators::validateCpf($cpf)) {
            throw new Exception('O CPF fornecido é inválido.');
        }
        if (!preg_match('/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).{8,}$/', $password)) {
            throw new Exception('A senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.');
        }
        if (empty($_FILES['doc_frente']) || $_FILES['doc_frente']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('A foto da frente do documento é obrigatória.');
        }
        if (empty($_FILES['selfie']) || $_FILES['selfie']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('A selfie é obrigatória.');
        }

        // 3. Lógica de Upload de Ficheiros Segura
        $uploadDir = __DIR__ . '/../../../public/uploads/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            throw new Exception('Falha ao criar o diretório de uploads. Verifique as permissões.');
        }

        $uploadedFilePaths = [];
        $filesToUpload = [
            'doc_frente' => $_FILES['doc_frente'],
            'doc_verso' => $_FILES['doc_verso'] ?? null,
            'selfie' => $_FILES['selfie'],
        ];
        
        $maxFileSize = 5 * 1024 * 1024; // 5 MB
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

        foreach ($filesToUpload as $key => $file) {
            if ($file && $file['error'] === UPLOAD_ERR_OK) {
                if ($file['size'] > $maxFileSize) throw new Exception("O ficheiro '{$key}' é demasiado grande. Máximo de 5MB.");
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mimeType, $allowedMimeTypes)) throw new Exception("O tipo de ficheiro '{$mimeType}' para '{$key}' não é permitido.");

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $uploadedFilePaths[$key] = '/uploads/' . $newFileName;

                    // --- CRIAÇÃO DE THUMBNAIL PARA A SELFIE ---
                    if ($key === 'selfie') {
                        $thumbnailPath = $this->createThumbnail($targetPath, $uploadDir, 200);
                        if ($thumbnailPath) {
                            $uploadedFilePaths['selfie_thumb'] = '/uploads/' . basename($thumbnailPath);
                        }
                    }
                } else {
                    throw new Exception("Erro ao mover o ficheiro de upload: {$key}");
                }
            }
        }
        
    // 4. Inserção na Base de Dados (com transação)
    $pdo = Connection::getPdo(); // Mova a conexão para o topo do bloco
    try {
        $pdo->beginTransaction(); // Inicia a transação
        
        // Verifica se os dados já existem
        $stmtCheck = $pdo->prepare("SELECT id FROM operators WHERE username = ? OR email = ? OR cpf = ?");
        $stmtCheck->execute([$username, $email, $cpf]);
        if ($stmtCheck->fetch()) {
            $pdo->rollBack(); // Cancela a transação
            flash('O @username, E-mail ou CPF fornecido já está registado na plataforma.', 'error');
            header('Location: /registro/operador');
            exit();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO operators 
                    (name, username, cpf, phone, email, password, cep, endereco, numero, bairro, cidade, estado, 
                     pix_key_type, pix_key, 
                     path_documento_frente, path_documento_verso, path_selfie, path_selfie_thumb,
                     status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente_verificacao')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $username, $cpf, $phone, $email, $passwordHash,
            $cep, $endereco, $numero, $bairro, $cidade, $estado,
            $pix_key_type, $pix_key,
            $uploadedFilePaths['doc_frente'] ?? null,
            $uploadedFilePaths['doc_verso'] ?? null,
            $uploadedFilePaths['selfie'] ?? null,
            $uploadedFilePaths['selfie_thumb'] ?? null
        ]);

        // --- NOVA LÓGICA: CRIAR A CARTEIRA ---
        $newOperatorId = $pdo->lastInsertId();
        $stmtWallet = $pdo->prepare("INSERT INTO operator_wallets (operator_id) VALUES (?)");
        $stmtWallet->execute([$newOperatorId]);
        // --- FIM DA NOVA LÓGICA ---

            // 5. Envio de E-mail e Redirecionamento
            $emailSubject = "Bem-vindo à TURNY! O seu registo foi recebido.";
            $emailBody = "<h1>Olá, " . htmlspecialchars($name) . "!</h1><p>Recebemos o seu registo na plataforma TURNY. A nossa equipa irá analisar os seus documentos e em breve receberá um novo e-mail sobre o status da sua conta.</p><p>Obrigado por se juntar a nós!</p><p><strong>Equipe TURNY</strong></p>";
            Email::sendEmail($email, $name, $emailSubject, $emailBody);

            flash('Registo concluído com sucesso! A sua conta está em análise. Será notificado por e-mail assim que for aprovada.', 'success');
            header('Location: /login');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Cria uma miniatura (thumbnail) de uma imagem.
     *
     * @param string $sourcePath Caminho para a imagem original.
     * @param string $destinationDir Diretório onde a miniatura será guardada.
     * @param int $maxWidth Largura máxima da miniatura.
     * @return string|false O caminho para a miniatura criada ou false em caso de erro.
     */
    private function createThumbnail(string $sourcePath, string $destinationDir, int $maxWidth = 200)
    {
        try {
            if (!function_exists('imagecreatefromjpeg')) {
                error_log('A extensão GD para PHP não está habilitada.');
                return false;
            }

            list($width, $height, $type) = getimagesize($sourcePath);
            if (!$width || !$height) return false;

            $sourceImage = null;
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return false;
            }

            if ($sourceImage === false) return false;

            $ratio = $width / $height;
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;

            $thumb = imagecreatetruecolor($newWidth, $newHeight);

            if ($type == IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }

            imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            $thumbFilename = 'thumb-' . basename($sourcePath);
            $thumbPath = $destinationDir . $thumbFilename;

            switch ($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($thumb, $thumbPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($thumb, $thumbPath, 7);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($thumb, $thumbPath);
                    break;
            }

            imagedestroy($sourceImage);
            imagedestroy($thumb);

            return $thumbPath;
        } catch (Exception $e) {
            error_log('Erro ao criar thumbnail: ' . $e->getMessage());
            return false;
        }
    }
}
