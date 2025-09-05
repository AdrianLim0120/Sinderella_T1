document.getElementById('getCodeButton').addEventListener('click', function() {
    let phone = document.getElementById('phone').value;
    const errorMessage = document.getElementById('error-message');

    // Clear previous error message
    errorMessage.innerText = '';

    // Sanitize phone number: remove spaces and symbols
    phone = phone.replace(/[\s-]/g, '');

    // Validate phone number
    if (!/^\d+$/.test(phone)) {
        errorMessage.innerText = 'Phone number must be numeric only.';
        return;
    }

    // Send AJAX request to check if user exists and get verification code
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../request_otp.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            if (xhr.responseText.startsWith('Failed') || xhr.responseText.startsWith('User does not exist')) {
                errorMessage.innerText = xhr.responseText;
            } else {
                alert('Verification code: ' + xhr.responseText); // Alert the OTP for testing purposes
            }
        } else {
            errorMessage.innerText = 'Failed to send verification code. Please try again.';
        }
    };
    xhr.onerror = function() {
        errorMessage.innerText = 'Request error. Please try again.';
    };
    xhr.send('phone=' + phone);
});

function toggleAllPasswords() {
    var show = document.getElementById('showPasswordAll').checked;
    var fields = ['password', 'confirm_password', 'old_password', 'new_password'];
    fields.forEach(function(id) {
        var field = document.getElementById(id);
        if (field) field.type = show ? 'text' : 'password';
    });
}
