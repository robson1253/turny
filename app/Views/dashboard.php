<?php
// Protege a página, garantindo que apenas o admin master pode aceder
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login'); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .container { padding: 40px 20px; }
        .dashboard-actions { 
            margin-top: 30px; 
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--cor-borda);
        }
        .dashboard-actions h3 { 
            margin-top: 0;
            margin-bottom: 20px; 
            color: var(--cor-destaque);
            border-bottom: 1px solid var(--cor-borda);
            padding-bottom: 10px;
        }
        /* A classe .btn é genérica para botões e links com aparência de botão */
        .dashboard-actions .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            background-color: var(--cor-primaria);
            color: var(--cor-branco) !important; /* !important para sobrepor a cor padrão de 'a' */
            border-radius: 5px; 
            text-decoration: none;
            font-weight: bold; 
            margin-right: 10px; 
            margin-bottom: 10px;
            cursor: pointer;
            border: none;
        }
        .dashboard-actions .btn:hover { 
            opacity: 0.9;
            transform: translateY(-2px);
            transition: all 0.2s;
        }
        .dashboard-actions .btn-secondary {
            background-color: var(--cor-branco);
            color: var(--cor-primaria) !important;
            border: 1px solid var(--cor-primaria);
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
            <h1>Painel de Controlo da TURNY</h1>
            <a href="/logout" style="color: var(--cor-perigo); font-weight: bold;">Sair (Logout)</a>
        </div>
        
        <h2>Bem-vindo de volta, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
        <p>Este é o seu centro de comando para gerir toda a plataforma.</p>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">

        <div class="dashboard-actions">
            <h3>Gestão de Empresas</h3>
            <a href="/admin/empresas/criar" class="btn">Adicionar Nova Empresa</a>
            <a href="/admin/empresas" class="btn btn-secondary">Listar e Gerir Empresas</a>
        </div>
        
        <div class="dashboard-actions">
            <h3>Gestão de Operadores</h3>
            <a href="/registro/operador" target="_blank" class="btn">Página de Registo Público</a>
            <a href="/admin/operadores" class="btn btn-secondary">Listar e Gerir Operadores</a>
        </div>

        <div class="dashboard-actions">
            <h3>Configurações da Plataforma</h3>
            <a href="/admin/erps" class="btn btn-secondary">Gerir Sistemas ERP</a>
            <a href="/admin/settings" class="btn btn-secondary">Gerir Configurações Gerais</a>
        </div>
    </div>
</body>
</html>