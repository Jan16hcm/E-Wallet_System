    function switchTab(tabId) {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.admin-list').forEach(l => l.classList.remove('active'));
        
        event.target.classList.add('active');
        document.getElementById('list-' + tabId).classList.add('active');
    }

    const themeToggleBtns = document.querySelectorAll('.theme-toggle');
    const body = document.body;

    themeToggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            body.classList.toggle('light-theme');
            themeToggleBtns.forEach(b => {
                const icon = b.querySelector('i');
                if (icon) {
                    if (body.classList.contains('light-theme')) {
                        icon.classList.replace('fa-moon', 'fa-sun');
                    } else {
                        icon.classList.replace('fa-sun', 'fa-moon');
                    }
                }
            });
        });
    });