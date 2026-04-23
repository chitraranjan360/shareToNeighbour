(function () {
    const form = document.getElementById('adminManageItemsFiltersForm');
    if (!form) return;

    const clearBtn = document.getElementById('adminManageItemsClearFiltersBtn');

    function allFiltersEmpty() {
        const searchInput = form.querySelector('input[name="q"]');
        const categoryInput = form.querySelector('select[name="category"]');

        const searchValue = (searchInput?.value || '').trim();
        const categoryValue = (categoryInput?.value || '').trim();

        return searchValue === '' && categoryValue === '';
    }

    form.addEventListener('submit', function (e) {
        if (allFiltersEmpty()) {
            e.preventDefault();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
            if (allFiltersEmpty()) {
                e.preventDefault();
            }
        });
    }
})();
