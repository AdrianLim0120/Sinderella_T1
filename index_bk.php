<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinderella - A Helping Hand In Cleaning (Home Cleaning Service)</title>
    <link rel="icon" href="img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="includes/css/styles.css">
    <?php include('includes/header_bk.php')?>
</head>

<body>
    <div class="main_content">
        <h1>Mission: Locals for Locals</h1>
        <h1>All Sinderella are Malaysian, and real-named registered with Malaysia IC.</h1>
        <p>There is always a common misunderstanding that home cleaning service is considered as super simple job, but actually not. This is a combination of labour force and brains. Employed cleaners have to race against the clock and face all kinds of unexpected situations.</p>
        <p>In fact, the most difficult task about household cleaning is that it is impossible to standardize the quality of service, due to the different internal layout of every house. Also, every users own different characteristics and preferences, so that the standard of quality will be different from person to person based on user satisfaction. This means, all service personnel have to possess various skills to take responsibility in this kind of job.</p>
        <p>In the past, the income of service personnel would not increase because of the satisfaction of a certain user, nor would it decrease because of the dissatisfaction. So this means that the traditional income system cannot improve the satisfaction of users, even if more tools or training are added does not help.</p>

        <h1>Values & Belief</h1>
        <p>A company is merely providing a job when the staff's contribution is not proportional to the income.</p>
        <p>However, it is said that a company is providing a platform for the staff to transform once the contribution is directly proportional to the income.</p>
        <p>In order to improve users' satisfaction, we formulate and launch a system that attract endless income to intense Sinderella's autonomous and spontaneous to achieve users' satisfaction.</p>

        <img src="img/sinderella_v&b.png" alt="Values and Belief">

        <h1>Shoot for the moon</h1>
        <p>This new income system able to stimulate Sinderella creating the most appropriate arrangements for your household chores as the amount of their profit based on your level of satisfaction.</p>
        
        <h1>Increase the income level of Malaysia, and move towards to advanced countries. Malaysia boleh!</h1>
        <p>There are many problems with foreign workers, and our government had made drastic reforms to solve these problems.</p>
        <p>As Malaysian, we strike our best to help country by ours own strength by providing the best solution for locals users on household chores which is reducing the dependence on foreign workers and thus create employment opportunities for Malaysian</p>
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
    <?php include('includes/footer.php')?>
    <script src="includes\js\scripts.js"></script>
</body>
</html>