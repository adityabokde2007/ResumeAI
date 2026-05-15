(function() {
    // Prevent flash of unstyled content
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }

    document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.createElement('button');
        toggleBtn.id = 'theme-toggle';
        toggleBtn.className = 'theme-toggle-btn';
        toggleBtn.setAttribute('aria-label', 'Toggle Dark Mode');
        
        // Mature SVG Icons (Sun for Light Mode, Moon for Dark Mode)
        const moonIcon = `<svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 22px; height: 22px;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
        const sunIcon = `<svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 22px; height: 22px; display: none;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
        
        toggleBtn.innerHTML = moonIcon + sunIcon;
        document.body.appendChild(toggleBtn);

        const isDarkInit = document.documentElement.classList.contains('dark-mode');
        document.querySelector('.moon-icon').style.display = isDarkInit ? 'none' : 'block';
        document.querySelector('.sun-icon').style.display = isDarkInit ? 'block' : 'none';

        toggleBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark-mode');
            const isDark = document.documentElement.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            document.querySelector('.moon-icon').style.display = isDark ? 'none' : 'block';
            document.querySelector('.sun-icon').style.display = isDark ? 'block' : 'none';
        });
    });
})();
