document.getElementById('verifyIdentityForm').addEventListener('submit', function(event) {
    const icNumber = document.getElementById('ic_number').value;
    const icPhoto = document.getElementById('ic_photo').files[0];
    const profilePhoto = document.getElementById('profile_photo').files[0];
    const errorMessage = document.getElementById('error-message');
    const idType = document.getElementById('id_type').value;

    // Clear previous error message
    errorMessage.innerText = '';

    if (idType === 'NI') {
        // Validate IC number and auto-fill fields
        if (!validateAndFillIC(icNumber)) {
            event.preventDefault();
            return;
        }
    }

    // Validate IC photo is an image
    if (!icPhoto || !icPhoto.type.startsWith('image/')) {
        errorMessage.innerText = 'IC photo must be an image file.';
        event.preventDefault();
        return;
    }

    // Validate profile photo is an image
    if (!profilePhoto || !profilePhoto.type.startsWith('image/')) {
        errorMessage.innerText = 'Profile photo must be an image file.';
        event.preventDefault();
        return;
    }
});

// document.getElementById('ic_number').addEventListener('input', function() {
//     validateAndFillIC(this.value);
// });

function validateAndFillIC(icNumber) {
    const errorMessage = document.getElementById('ic-error-message');
    const dobInput = document.getElementById('dob');
    const ageInput = document.getElementById('age');
    const genderInput = document.getElementById('gender');
    errorMessage.innerText = '';
    dobInput.value = '';
    ageInput.value = '';
    genderInput.value = '';

    if (!/^\d{12}$/.test(icNumber)) {
        errorMessage.innerText = 'IC number must be a 12-digit numeric value.';
        return false;
    }

    // Parse year, month, day
    let year = parseInt(icNumber.substring(0, 2), 10);
    let month = parseInt(icNumber.substring(2, 4), 10);
    let day = parseInt(icNumber.substring(4, 6), 10);

    // Validate month
    if (month < 1 || month > 12) {
        errorMessage.innerText = 'Invalid IC number: Month is not valid.';
        return false;
    }

    // Validate day (basic check)
    if (day < 1 || day > 31) {
        errorMessage.innerText = 'Invalid IC number: Day is not valid.';
        return false;
    }

    // Determine full year
    const currentYear = new Date().getFullYear() % 100;
    const fullYear = (year > currentYear ? 1900 + year : 2000 + year);

    // Validate date
    const dobDate = new Date(Date.UTC(fullYear, month - 1, day));
    if (
        dobDate.getUTCFullYear() !== fullYear ||
        dobDate.getUTCMonth() !== month - 1 ||
        dobDate.getUTCDate() !== day
    ) {
        errorMessage.innerText = 'Invalid IC number: Date is not valid.';
        return false;
    }

    // Set Date of Birth (format: yyyy-mm-dd)
    dobInput.value = dobDate.toISOString().slice(0, 10);

    // Calculate Age
    let age = (new Date().getFullYear() % 100) - year;
    if (age < 0) age += 100;
    ageInput.value = age;

    // Gender (12th digit)
    const genderDigit = parseInt(icNumber.charAt(11), 10);
    genderInput.value = (genderDigit % 2 === 0) ? 'Female' : 'Male';

    return true;
}