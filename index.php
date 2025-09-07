<?php
require __DIR__ . '/includes/pseudocron.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinderella - A Helping Hand In Cleaning (Home Cleaning Service)</title>
    <link rel="icon" href="img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="includes/css/styles.css">
    <?php include('includes/header.php')?>
</head>

<body>
    <!-- // Tentative internal testing password prompt
    <script>
    (function() {
        var allowed = false;
        while (!allowed) {
            var input = prompt("Internal Testing Only\n\nPlease enter the access password:");
            if (input === "8148") {
                allowed = true;
            } else {
                alert("Incorrect password. Please try again.");
            }
        }
    })();
    </script>
    // Tentative internal testing password prompt --- END -->






    <br><br>
        <div id="login-buttons" style="display: flex; justify-content: center; gap: 20px; margin-bottom: 40px;">
            <a href="login_cust.php">
                <button type="button" style="padding: 12px 28px; font-size: 1rem; cursor: pointer;">Customer Login</button>
            </a>
            <a href="login_sind.php">
                <button type="button" style="padding: 12px 28px; font-size: 1rem; cursor: pointer;">Sinderella Login</button>
            </a>
            <a href="login_adm.php">
                <button type="button" style="padding: 12px 28px; font-size: 1rem; cursor: pointer;">Admin Login</button>
            </a>
        </div>

        <div class="search-sinderella" style="margin: 40px auto;">
            <h2>Search Your Sinderella</h2>
            <form id="searchSinderellaForm" method="post" autocomplete="off">
                <label for="search_postcode">Enter your postcode:</label>
                <input type="text" id="search_postcode" name="search_postcode" maxlength="5" required pattern="\d{5}">
                <span id="search-error-message" style="color: red;"></span>
                <br><br>
                <label for="search_area">Area:</label>
                <input type="text" id="search_area" name="search_area" readonly>
                <label for="search_state" style="margin-left:20px;">State:</label>
                <input type="text" id="search_state" name="search_state" readonly>
                <br><br>
                <div class="search-btn-center">
                    <button type="submit" id="searchBtn" disabled>Check Availability</button>
                </div>
            </form>
            <div id="sinderella-result" style="margin-top:15px;"></div>
        </div>

        <script>
        // --- Postcode autofill logic (adapted from address_info.js) ---
        document.getElementById('search_postcode').addEventListener('input', function() {
            const postcode = this.value;
            const errorMessage = document.getElementById('search-error-message');
            const areaField = document.getElementById('search_area');
            const stateField = document.getElementById('search_state');
            const searchBtn = document.getElementById('searchBtn');
            errorMessage.innerText = '';
            areaField.value = '';
            stateField.value = '';
            searchBtn.disabled = true;

            if (!/^\d{5}$/.test(postcode)) {
                if (postcode.length === 5) errorMessage.innerText = 'Postcode must be a valid 5-digit number.';
                return;
            }

            fetch('data/postcode.json')
                .then(response => response.json())
                .then(data => {
                    let found = false;
                    data.state.forEach(state => {
                        state.city.forEach(city => {
                            if (city.postcode && city.postcode.includes(postcode)) {
                                areaField.value = city.name;
                                stateField.value = state.name;
                                found = true;
                            }
                        });
                    });
                    if (!found) {
                        errorMessage.innerText = 'Invalid postcode. Area and state not found.';
                    } else {
                        searchBtn.disabled = false;
                    }
                })
                .catch(() => {
                    errorMessage.innerText = 'Error fetching postcode data.';
                });
        });

        // --- AJAX form submit to check sinderella availability ---
        document.getElementById('searchSinderellaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const area = document.getElementById('search_area').value;
            const resultDiv = document.getElementById('sinderella-result');
            resultDiv.innerHTML = '';
            if (!area) return;

            fetch('search_sinderella.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'area=' + encodeURIComponent(area)
            })
            .then(response => response.text())
            .then(html => {
                resultDiv.innerHTML = html;
            })
            .catch(() => {
                resultDiv.innerHTML = '<span style="color:red;">Error searching for Sinderella in your area.</span>';
            });
        });
        </script>
    <script src="includes\js\scripts.js"></script>
</body>
</html>