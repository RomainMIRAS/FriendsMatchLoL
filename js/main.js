/**
 * FriendsMatchLoL - Main JavaScript file
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Fonction pour formater la durée du match
    window.formatGameTime = function(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
    }
    
    // Fonction pour afficher les notifications
    window.showNotification = function(message, type = 'info') {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) return;
        
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.role = 'alert';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 150);
        }, 5000);
    }

    // Vérifier les notifications de match
    function checkMatchNotifications() {
        $.get('api/check_notifications.php', function(data) {
            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    showNotification(`${notification.friend_name} est maintenant en partie ${notification.game_mode} avec ${notification.champion}!`, 'success');
                });
            }
        });
    }

    // Vérifier les notifications toutes les 90 secondes
    setInterval(checkMatchNotifications, 90000);
});
