<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
// Garante que a variável $stores existe para o filtro
if (!isset($stores)) {
    $stores = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calendário de Vagas - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <style>
        #calendar-container {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid var(--cor-borda);
        }
        .fc-event {
            cursor: pointer;
            font-weight: bold;
            font-size: 0.85em;
        }
        .fc-event:hover {
            opacity: 0.8;
        }
        .calendar-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .calendar-filters label {
            font-weight: bold;
            color: var(--cor-destaque);
        }
        .calendar-filters select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid var(--cor-borda);
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
            <h1>Calendário de Vagas</h1>
            <div>
                <a href="/painel/empresa" style="margin-right: 15px;">&larr; Voltar para o Painel</a>
                <a href="/painel/empresa" class="btn-login">Publicar Nova Vaga</a>
            </div>
        </div>

        <div id="calendar-container">
            <div class="calendar-filters">
                <label for="store-filter">Filtrar por Loja:</label>
                <select id="store-filter">
                    <option value="">Todas as Lojas</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id='calendar'></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const storeFilter = document.getElementById('store-filter');
            let calendar;

            /**
             * Função para renderizar ou recarregar o calendário
             * @param {string} storeId - O ID da loja para filtrar (opcional)
             */
            function renderCalendar(storeId = '') {
                // Constrói a URL da nossa API, adicionando o filtro de loja se existir
                let eventsUrl = '/api/company-shifts';
                if (storeId) {
                    eventsUrl += `?store_id=${storeId}`;
                }

                // Se um calendário já existe, destrói-o para renderizar um novo com os dados atualizados
                if (calendar) {
                    calendar.destroy();
                }

                // Cria uma nova instância do FullCalendar
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth', // Visão inicial mensal
                    locale: 'pt-br', // Define o idioma para português do Brasil
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    buttonText: {
                        today: 'Hoje',
                        month: 'Mês',
                        week: 'Semana',
                        day: 'Dia'
                    },
                    allDaySlot: false, // Remove a secção "o dia todo"
                    slotMinTime: '06:00:00', // Hora mínima a ser exibida
                    slotMaxTime: '23:00:00', // Hora máxima a ser exibida
                    height: 'auto', // Ajusta a altura automaticamente
                    
                    events: eventsUrl, // Define a nossa API como fonte de eventos

                    eventClick: function(info) {
                        // Ao clicar num evento, redireciona para a página de edição
                        // Impede o comportamento padrão do clique
                        info.jsEvent.preventDefault(); 
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },

                    // (Opcional) Adiciona um URL a cada evento para o eventClick funcionar
                    eventDidMount: function(info) {
                        if (info.event.id) {
                            info.el.href = `/painel/vagas/editar?id=${info.event.id}`;
                        }
                    }
                });

                // Renderiza o calendário no ecrã
                calendar.render();
            }

            // 1. Renderiza o calendário pela primeira vez (com todas as lojas)
            renderCalendar();

            // 2. Adiciona o evento para recarregar o calendário quando o filtro mudar
            storeFilter.addEventListener('change', function() {
                renderCalendar(this.value);
            });
        });
    </script>
</body>
</html>