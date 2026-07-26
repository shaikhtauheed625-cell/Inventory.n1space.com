document.addEventListener('DOMContentLoaded', function() {
    const badge = document.getElementById('global-notification-badge');
    
    function checkGlobalReminders() {
        fetch('api/tasks.php?action=reminders')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.reminders.length > 0) {
                        badge.classList.remove('d-none');
                        // If we are on a page that has the showNotification function (like tasks.php), we can call it.
                        // Otherwise, we just show the badge.
                        if (typeof showNotification === 'function') {
                            data.reminders.forEach(reminder => {
                                showNotification(`Reminder: ${reminder.title} is due soon!`, 'important');
                            });
                        }
                    } else {
                        // Also check if there are any pending tasks to show the badge
                        fetch('api/tasks.php?action=list&filter=pending')
                            .then(r => r.json())
                            .then(d => {
                                if (d.success && d.data.length > 0) {
                                    badge.classList.remove('d-none');
                                } else {
                                    badge.classList.add('d-none');
                                }
                            });
                    }
                }
            });
    }

    // Check every 2 minutes for the global badge
    setInterval(checkGlobalReminders, 120000);
    checkGlobalReminders();
});
