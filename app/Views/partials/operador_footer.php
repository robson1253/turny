<footer class="operador-footer">
    <a href="/painel/operador" class="footer-icon <?= ($activePage ?? '') === 'vagas' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
        <span>Vagas</span>
    </a>
    <a href="/painel/operador/meus-turnos" class="footer-icon <?= ($activePage ?? '') === 'turnos' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
        <span>Meus Turnos</span>
    </a>
    <a href="/painel/operador/ofertas" class="footer-icon <?= ($activePage ?? '') === 'ofertas' ? 'active' : '' ?>" style="position: relative;">
        <?php if (($pendingOffers ?? 0) > 0): ?>
            <span class="notification-badge"><?= htmlspecialchars($pendingOffers) ?></span>
        <?php endif; ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
        <span>Ofertas</span>
    </a>
    <a href="/painel/operador/carteira" class="footer-icon <?= ($activePage ?? '') === 'carteira' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,18V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12A2,2 0 0,0 10,8V16A2,2 0 0,0 12,18H21M12,16H22V8H12V16M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z" /></svg>
        <span>Carteira</span>
    </a>
    <a href="/painel/operador/perfil" class="footer-icon <?= ($activePage ?? '') === 'perfil' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
        <span>Perfil</span>
    </a>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function checkNotifications() {
        fetch('/api/operador/notifications')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.notification) {
                    // Simplesmente mostra um alerta, mas poderia ser um "toast" mais elegante
                    if(confirm('Nova Notificação: ' + data.notification.message + '\n\nDeseja atualizar a página para ver seu novo saldo e o comprovante?')) {
                        // Se o usuário clicar OK, recarrega a página da carteira para ver o extrato atualizado
                        window.location.href = '/painel/operador/carteira';
                    }
                }
            })
            .catch(err => console.error('Erro ao buscar notificações:', err));
    }

    // Inicia a verificação a cada 5 segundos
    setInterval(checkNotifications, 5000);
});
</script>