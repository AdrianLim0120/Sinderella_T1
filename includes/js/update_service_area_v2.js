document.addEventListener('DOMContentLoaded', function () {
    const serviceAreasContainer = document.getElementById('serviceAreasContainer');
    const addServiceAreaButton = document.getElementById('addServiceAreaButton');
    const deletedServiceAreasInput = document.getElementById('deletedServiceAreas');
    let serviceAreaCount = document.querySelectorAll('.service-area-block').length;
    let deletedServiceAreas = [];

    // Load states and areas from JSON
    let statesAndAreas = {};
    fetch('../data/postcode.json')
        .then(response => response.json())
        .then(data => {
            statesAndAreas = data.state;
            populateStateOptions();
            populateExistingServiceAreas();
        })
        .catch(error => console.error('Error loading postcode.json:', error));

    function populateStateOptions() {
        const stateSelects = document.querySelectorAll('.state-select');
        stateSelects.forEach(select => {
            const currentState = select.getAttribute('data-state') || '';
            select.innerHTML = '<option value="">Select State</option>';
            statesAndAreas.forEach(state => {
                const option = document.createElement('option');
                option.value = state.name;
                option.textContent = state.name;
                if (state.name === currentState) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        });
    }

    function populateAreaCheckboxes(stateSelect) {
        const areaCheckboxesContainer = stateSelect.closest('.service-area-block').querySelector('.area-checkboxes');
        const currentAreas = areaCheckboxesContainer.getAttribute('data-area') ? 
                            JSON.parse(areaCheckboxesContainer.getAttribute('data-area')) : [];
        areaCheckboxesContainer.innerHTML = '';

        const selectedState = stateSelect.value;
        if (selectedState) {
            const state = statesAndAreas.find(s => s.name === selectedState);
            if (state && state.city) {
                state.city.forEach(city => {
                    const checkbox = document.createElement('input');
                    const serviceAreaId = stateSelect.closest('.service-area-block').getAttribute('data-service-area-id');
                    checkbox.type = 'checkbox';
                    checkbox.name = `service_areas[${serviceAreaId}][areas][]`;
                    checkbox.value = city.name;
                    checkbox.id = `area-${city.name}-${serviceAreaId}`;
                    
                    // Check if this city is in the current areas
                    if (currentAreas.includes(city.name)) {
                        checkbox.checked = true;
                    }

                    const label = document.createElement('label');
                    label.htmlFor = `area-${city.name}-${serviceAreaId}`;
                    label.textContent = city.name;

                    const div = document.createElement('div');
                    div.appendChild(checkbox);
                    div.appendChild(label);

                    areaCheckboxesContainer.appendChild(div);
                });
            }
        }
    }

    function populateExistingServiceAreas() {
        const serviceAreaBlocks = document.querySelectorAll('.service-area-block');
        serviceAreaBlocks.forEach(block => {
            const stateSelect = block.querySelector('.state-select');
            populateAreaCheckboxes(stateSelect);
        });
    }

    addServiceAreaButton.addEventListener('click', function () {
        serviceAreaCount++;
        const serviceAreaBlock = document.createElement('div');
        serviceAreaBlock.classList.add('service-area-block');
        serviceAreaBlock.setAttribute('data-service-area-id', `new_${serviceAreaCount}`);
        serviceAreaBlock.innerHTML = `
            <button type="button" class="delete-button"><b>X</b></button>
            <label>State:</label>
            <select name="new_service_areas[${serviceAreaCount}][state]" class="state-select" required>
                <option value="">Select State</option>
            </select>
            <label>Areas:</label>
            <div class="area-checkboxes" data-area="[]"></div>
        `;
        serviceAreasContainer.appendChild(serviceAreaBlock);
        populateStateOptions();
        addDeleteButtonListener(serviceAreaBlock.querySelector('.delete-button'));
        addStateSelectListener(serviceAreaBlock.querySelector('.state-select'));
    });

    function addDeleteButtonListener(button) {
        button.addEventListener('click', function () {
            if (confirm('Are you sure you want to delete this service area?')) {
                const serviceAreaBlock = button.parentElement;
                const serviceAreaId = serviceAreaBlock.getAttribute('data-service-area-id');
                if (serviceAreaId && !serviceAreaId.startsWith('new_')) {
                    deletedServiceAreas.push(serviceAreaId);
                    deletedServiceAreasInput.value = deletedServiceAreas.join(',');
                }
                serviceAreaBlock.remove();
            }
        });
    }

    function addStateSelectListener(select) {
        select.addEventListener('change', function () {
            populateAreaCheckboxes(select);
        });
    }

    document.querySelectorAll('.delete-button').forEach(addDeleteButtonListener);
    document.querySelectorAll('.state-select').forEach(addStateSelectListener);
});