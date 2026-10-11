    // JS Toggle for Sidebar Submenus
document.addEventListener("DOMContentLoaded", function() {
    // Select all menu items that have submenus
    const menuItems = document.querySelectorAll('.menu-item');

    menuItems.forEach(item => {
        const menuLink = item.querySelector('a');
        const submenu = item.querySelector('.submenu-container');

        if (submenu) {
            // add a toggle icon if not already present
            let toggleIcon = document.createElement('span');
            toggleIcon.classList.add('toggle-icon');
            toggleIcon.textContent = '▼';
            menuLink.appendChild(toggleIcon);

            // hide submenu by default
            submenu.style.display = 'none';

            // click event to toggle submenu
            menuLink.addEventListener('click', function(e) {
                e.preventDefault(); // prevent default if menu has submenu

                if(submenu.style.display === 'none') {
                    submenu.style.display = 'block';
                    toggleIcon.textContent = '▲';
                } else {
                    submenu.style.display = 'none';
                    toggleIcon.textContent = '▼';
                }
            });
        }
    });
});