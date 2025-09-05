document.getElementById('postcode').addEventListener('input', function() {
    const postcode = this.value;
    const errorMessage = document.getElementById('error-message');

    // Clear previous error message
    errorMessage.innerText = '';

    // Validate postcode
    if (!/^\d{5}$/.test(postcode)) {
        errorMessage.innerText = 'Postcode must be a 5-digit number.';
        return;
    }

    // Fetch area and state from postcode.json
    fetch('../data/postcode.json')
        .then(response => response.json())
        .then(data => {
            let found = false;
            data.state.forEach(state => {
                state.city.forEach(city => {
                    if (city.postcode && city.postcode.includes(postcode)) {
                        document.getElementById('area').value = city.name;
                        document.getElementById('state').value = state.name;
                        found = true;
                    }
                });
            });

            if (!found) {
                errorMessage.innerText = 'Invalid postcode. Area and state not found.';
                document.getElementById('area').value = '';
                document.getElementById('state').value = '';
            }
        })
        .catch(error => {
            errorMessage.innerText = 'Error fetching postcode data.';
        });
});

// Function to validate a single postcode field
function validatePostcode(postcodeField) {
    const postcode = postcodeField.value;
    const errorMessage = document.getElementById('error-message');

    // Clear previous error message
    errorMessage.innerText = '';

    // Validate postcode
    if (!/^\d{5}$/.test(postcode)) {
        errorMessage.innerText = 'Postcode must be a 5-digit number.';
        return;
    }

    // Fetch area and state from postcode.json
    fetch('../data/postcode.json')
        .then(response => response.json())
        .then(data => {
            let found = false;
            data.state.forEach(state => {
                state.city.forEach(city => {
                    if (city.postcode && city.postcode.includes(postcode)) {
                        const addressId = postcodeField.id.split('_')[1];
                        document.getElementById(`area_${addressId}`).value = city.name;
                        document.getElementById(`state_${addressId}`).value = state.name;
                        found = true;
                    }
                });
            });

            if (!found) {
                errorMessage.innerText = 'Invalid postcode. Area and state not found.';
                const addressId = postcodeField.id.split('_')[1];
                document.getElementById(`area_${addressId}`).value = '';
                document.getElementById(`state_${addressId}`).value = '';
            }
        })
        .catch(error => {
            errorMessage.innerText = 'Error fetching postcode data.';
        });
}

// Attach event listeners to all postcode fields dynamically
document.addEventListener('DOMContentLoaded', function () {
    const postcodeFields = document.querySelectorAll('[id^="postcode_"]');
    postcodeFields.forEach(postcodeField => {
        postcodeField.addEventListener('input', function () {
            validatePostcode(postcodeField);
        });
    });
});