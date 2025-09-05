// document.addEventListener('DOMContentLoaded', function() {
//     const serviceAreasContainer = document.getElementById('serviceAreasContainer');
//     const addServiceAreaButton = document.getElementById('addServiceAreaButton');
//     const deletedServiceAreasInput = document.getElementById('deletedServiceAreas');
//     let serviceAreaCount = document.querySelectorAll('.service-area-block').length;
//     let deletedServiceAreas = [];

//     // Load states and areas from JSON
//     let statesAndAreas = {};
//     fetch('../data/postcode.json')
//         .then(response => response.json())
//         .then(data => {
//             statesAndAreas = data.state;
//             populateStateOptions();
//             populateExistingServiceAreas();
//         });

//     function populateStateOptions() {
//         const stateSelects = document.querySelectorAll('.state-select');
//         stateSelects.forEach(select => {
//             const currentState = select.getAttribute('data-state');
//             select.innerHTML = '<option value="">Select State</option>'; // Ensure fresh list
//             statesAndAreas.forEach(state => {
//                 const option = document.createElement('option');
//                 option.value = state.name;
//                 option.textContent = state.name;
//                 if (state.name === currentState) {
//                     option.selected = true;
//                 }
//                 select.appendChild(option);
//             });
//         });
//     }

//     function populateAreaOptions(stateSelect) {
//         const areaSelect = stateSelect.closest('.service-area-block').querySelector('.area-select');
//         const currentArea = areaSelect.getAttribute('data-area');
//         areaSelect.innerHTML = '<option value="">Select Area</option>'; // Reset area dropdown

//         const selectedState = stateSelect.value;
//         if (selectedState) {
//             const state = statesAndAreas.find(s => s.name === selectedState);
//             if (state) {
//                 state.city.forEach(city => {
//                     const option = document.createElement('option');
//                     option.value = city.name;
//                     option.textContent = city.name;
//                     if (city.name === currentArea) {
//                         option.selected = true;
//                     }
//                     areaSelect.appendChild(option);
//                 });
//             }
//         }
//     }

//     function populateExistingServiceAreas() {
//         const serviceAreaBlocks = document.querySelectorAll('.service-area-block');
//         serviceAreaBlocks.forEach(block => {
//             const stateSelect = block.querySelector('.state-select');
//             const areaSelect = block.querySelector('.area-select');
//             const state = stateSelect.getAttribute('data-state');
//             const area = areaSelect.getAttribute('data-area');
//             stateSelect.value = state;
//             populateAreaOptions(stateSelect);
//             areaSelect.value = area;
//         });
//     }

//     addServiceAreaButton.addEventListener('click', function() {
//         serviceAreaCount++;
//         const serviceAreaBlock = document.createElement('div');
//         serviceAreaBlock.classList.add('service-area-block');
//         serviceAreaBlock.innerHTML = `
//             <button type="button" class="delete-button" style="
//                                             position: absolute;
//                                             top: 1px;
//                                             right: 2%;
//                                             background-color: red;
//                                             color: white;
//                                             border: none;
//                                             border-radius: 5px;
//                                             width: 25px;
//                                             height: 25px;
//                                             cursor: pointer;
//                                             padding: unset;"><b>X</b></button>
//             <label>State:</label>
//             <select name="new_service_areas[${serviceAreaCount}][state]" class="state-select" required>
//                 <option value="">Select State</option>
//             </select>
//             <label>Area:</label>
//             <select name="new_service_areas[${serviceAreaCount}][area]" class="area-select" required>
//                 <option value="">Select Area</option>
//             </select>
//         `;
//         serviceAreasContainer.appendChild(serviceAreaBlock);
//         populateStateOptions();
//         addDeleteButtonListener(serviceAreaBlock.querySelector('.delete-button'));
//         addStateSelectListener(serviceAreaBlock.querySelector('.state-select'));
//     });

//     function addDeleteButtonListener(button) {
//         button.addEventListener('click', function() {
//             if (confirm('Are you sure you want to delete this service area?')) {
//                 const serviceAreaBlock = button.parentElement;
//                 const serviceAreaId = serviceAreaBlock.getAttribute('data-service-area-id');
//                 if (serviceAreaId) {
//                     deletedServiceAreas.push(serviceAreaId);
//                     deletedServiceAreasInput.value = deletedServiceAreas.join(',');
//                 }
//                 serviceAreaBlock.remove();
//                 formChanged = true;
//             }
//         });
//     }

//     function addStateSelectListener(select) {
//         select.addEventListener('change', function() {
//             populateAreaOptions(select);
//         });
//     }

//     document.querySelectorAll('.delete-button').forEach(addDeleteButtonListener);
//     document.querySelectorAll('.state-select').forEach(addStateSelectListener);

//     let formChanged = false;
//     const updateServiceAreaForm = document.getElementById('updateServiceAreaForm');

//     updateServiceAreaForm.addEventListener('input', function() {
//         formChanged = true;
//     });

//     updateServiceAreaForm.addEventListener('submit', function() {
//         window.removeEventListener('beforeunload', beforeUnloadHandler);
//     });

//     function beforeUnloadHandler(e) {
//         if (formChanged) {
//             const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave without saving?';
//             e.returnValue = confirmationMessage;
//             return confirmationMessage;
//         }
//     }

//     window.addEventListener('beforeunload', beforeUnloadHandler);
// });
document.addEventListener('DOMContentLoaded', function() {
    let statesAndAreas = [];
    let selectedAreas = window.selectedAreas || {}; // from PHP

    fetch('../data/postcode.json')
        .then(response => response.json())
        .then(data => {
            statesAndAreas = data.state;
            renderAllServiceAreas();
        });

    function renderAllServiceAreas() {
        const container = document.getElementById('serviceAreasContainer');
        container.innerHTML = '';
        Object.keys(selectedAreas).forEach(state => {
            addServiceAreaBlock(state, selectedAreas[state]);
        });
    }

    function addServiceAreaBlock(state = '', areas = []) {
        const container = document.getElementById('serviceAreasContainer');
        const block = document.createElement('div');
        block.className = 'service-area-block';

        // State dropdown
        let stateOptions = '<option value="">Select State</option>';
        statesAndAreas.forEach(s => {
            stateOptions += `<option value="${s.name}" ${s.name === state ? 'selected' : ''}>${s.name}</option>`;
        });

        // Area checkboxes
        let areaCheckboxes = '';
        if (state) {
            const stateObj = statesAndAreas.find(s => s.name === state);
            if (stateObj) {
                areaCheckboxes += `<input type="hidden" name="states[]" value="${state}">`;
                areaCheckboxes += '<label><strong>Areas:</strong></label><div style="display:flex;flex-wrap:wrap;">';
                stateObj.city.forEach(city => {
                    const checked = areas.includes(city.name) ? 'checked' : '';
                    areaCheckboxes += `<div style="width:200px;">
                        <label>
                            <input type="checkbox" name="areas[${state}][]" value="${city.name}" ${checked}>
                            ${city.name}
                        </label>
                    </div>`;
                });
                areaCheckboxes += '</div>';
            }
        }

        block.innerHTML = `
            <button type="button" class="delete-button" style="position:absolute;top:10px;right:10px;">X</button>
            <label><strong>State:</strong></label>
            <select class="state-select" required>${stateOptions}</select>
            <div class="areas-container">${areaCheckboxes}</div>
        `;

        // Delete button event
        block.querySelector('.delete-button').onclick = function() {
            if (confirm('Are you sure you want to remove this state and all its areas?')) {
                block.remove();
            }
        };

        // State select event
        block.querySelector('.state-select').onchange = function() {
            const selectedState = this.value;
            // Re-render areas for this state
            const stateObj = statesAndAreas.find(s => s.name === selectedState);
            let areaCheckboxes = '';
            if (stateObj) {
                areaCheckboxes += `<input type="hidden" name="states[]" value="${selectedState}">`;
                areaCheckboxes += '<label><strong>Areas:</strong></label><div style="display:flex;flex-wrap:wrap;">';
                stateObj.city.forEach(city => {
                    areaCheckboxes += `<div style="width:200px;">
                        <label>
                            <input type="checkbox" name="areas[${selectedState}][]" value="${city.name}">
                            ${city.name}
                        </label>
                    </div>`;
                });
                areaCheckboxes += '</div>';
            }
            block.querySelector('.areas-container').innerHTML = areaCheckboxes;
        };

        container.appendChild(block);
    }

    document.getElementById('addServiceAreaButton').onclick = function() {
        addServiceAreaBlock();
    };
});