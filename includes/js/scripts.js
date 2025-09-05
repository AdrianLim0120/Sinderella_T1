function toggleMenu() {
    const menu = document.getElementById('menu');
    menu.classList.toggle('show-menu');
}

document.getElementById('loginForm').addEventListener('submit', function(event) {
    let phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;
    const errorMessage = document.getElementById('error-message');

    // Clear previous error message
    errorMessage.innerText = '';

    // Sanitize phone number: remove spaces and symbols
    phone = phone.replace(/[\s-]/g, '');

    // Validate phone number
    if (!/^\d+$/.test(phone)) {
        errorMessage.innerText = 'Phone number must be numeric only.';
        event.preventDefault();
        return;
    }

    // Validate password
    if (password.trim() === '') {
        errorMessage.innerText = 'Password is required.';
        event.preventDefault();
        return;
    }

    // Update the sanitized phone number back to the input field
    document.getElementById('phone').value = phone;
});