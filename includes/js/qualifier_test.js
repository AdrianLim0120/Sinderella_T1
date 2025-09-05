document.addEventListener('DOMContentLoaded', function() {
    const startTestButton = document.getElementById('startTestButton');

    startTestButton.addEventListener('click', function() {
        window.location.href = 'start_test.php';
    });
});