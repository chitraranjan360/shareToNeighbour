(function () {
    const form = document.getElementById('browseFiltersForm');
    if (!form) return;

    const clearBtn = document.getElementById('clearFiltersBtn');
    const slider = document.getElementById('radius_km');
    const label = document.getElementById('radiusLabel');
    const radiusMap = [0, 1, 5, 10, 15, 20];

    function getLabel(km) {
        km = Number(km);
        if (km === 0) return 'All items';
        return 'Within ' + km + ' km';
    }

    function updateSliderState() {
        if (!slider || !label) return;

        const idx = Number(slider.value || 0);
        const km = radiusMap[idx] ?? 0;
        label.textContent = getLabel(km);

        // Submit the mapped km value instead of the slider index.
        slider.name = '';
        let hidden = document.getElementById('radius_km_real');
        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.id = 'radius_km_real';
            hidden.name = 'radius_km';
            form.appendChild(hidden);
        }
        hidden.value = String(km);
    }

    function currentRadiusKm() {
        const realRadius = document.getElementById('radius_km_real');
        if (realRadius) {
            return Number((realRadius.value || '0').trim()) || 0;
        }

        const radiusInput = form.querySelector('input[name="radius_km"]');
        if (radiusInput) {
            return Number((radiusInput.value || '0').trim()) || 0;
        }

        if (slider) {
            const idx = Number(slider.value || 0);
            return radiusMap[idx] ?? 0;
        }

        return 0;
    }

    function allFiltersEmpty() {
        const searchInput = form.querySelector('input[name="q"]');
        const categoryInput = form.querySelector('select[name="category"]');
        const conditionInput = form.querySelector('select[name="condition"]');

        const searchValue = (searchInput?.value || '').trim();
        const categoryValue = (categoryInput?.value || '').trim();
        const conditionValue = (conditionInput?.value || '').trim();
        const radiusValue = currentRadiusKm();

        return searchValue === '' && categoryValue === '' && conditionValue === '' && radiusValue === 0;
    }

    if (slider && label) {
        updateSliderState();
        slider.addEventListener('input', updateSliderState);
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
