(function () {
    const form = document.getElementById('adminManageUsersSearchForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        const searchInput = form.querySelector('input[name="q"]');
        const searchValue = (searchInput?.value || '').trim();

        if (searchValue === '') {
            e.preventDefault();
        }
    });
})();
