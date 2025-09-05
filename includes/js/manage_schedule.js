document.addEventListener('DOMContentLoaded', function () {
    const calendarContainer = document.getElementById('calendarContainer');
    const monthNames = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    let selectedDate = null;
    let selectedDay = null;
    let availableTimes = {}; // Date-based times
    let dayBasedTimes = {}; // Day-based times

    function renderCalendar(month, year) {
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalize to midnight

        const firstDay = new Date(year, month).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const calendar = document.createElement('table');
        calendar.className = 'calendar';
    
        const headerRow = document.createElement('tr');
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        daysOfWeek.forEach(day => {
            const th = document.createElement('th');
            th.textContent = day;
    
            // // Add time input fields for the day header
            // const timeInputs = document.createElement('div');
            // timeInputs.className = 'time-inputs';
    
            // const input1 = document.createElement('input');
            // input1.type = 'time';
            // input1.className = 'time-input';
            // input1.value = dayBasedTimes[day]?.available_from1 === '00:00:00' ? '' : dayBasedTimes[day]?.available_from1 || '';
    
            // const input2 = document.createElement('input');
            // input2.type = 'time';
            // input2.className = 'time-input';
            // input2.value = dayBasedTimes[day]?.available_from2 === '00:00:00' ? '' : dayBasedTimes[day]?.available_from2 || '';
        
            // // Create remove icon for input1
            // const removeDayBtn1 = document.createElement('span');
            // removeDayBtn1.textContent = '❌';
            // removeDayBtn1.title = 'Clear this time slot';
            // removeDayBtn1.style.cursor = 'pointer';
            // removeDayBtn1.style.marginLeft = '5px';
            // removeDayBtn1.style.color = '#e53935';
            // removeDayBtn1.onclick = function(e) {
            //     e.stopPropagation();
            //     input1.value = '';
            //     handleDayTimeChange(day, input1, input2);
            // };

            // // Create remove icon for input2
            // const removeDayBtn2 = document.createElement('span');
            // removeDayBtn2.textContent = '❌';
            // removeDayBtn2.title = 'Clear this time slot';
            // removeDayBtn2.style.cursor = 'pointer';
            // removeDayBtn2.style.marginLeft = '5px';
            // removeDayBtn2.style.color = '#e53935';
            // removeDayBtn2.onclick = function(e) {
            //     e.stopPropagation();
            //     input2.value = '';
            //     handleDayTimeChange(day, input1, input2);
            // };
    
            // // Add event listeners for autosave
            // input1.addEventListener('change', () => handleDayTimeChange(day, input1, input2));
            // input2.addEventListener('change', () => handleDayTimeChange(day, input1, input2));
    
            // const inputGroup1 = document.createElement('div');
            // inputGroup1.style.display = 'flex';
            // inputGroup1.style.alignItems = 'center';
            // inputGroup1.appendChild(input1);
            // inputGroup1.appendChild(removeDayBtn1);

            // const inputGroup2 = document.createElement('div');
            // inputGroup2.style.display = 'flex';
            // inputGroup2.style.alignItems = 'center';
            // inputGroup2.appendChild(input2);
            // inputGroup2.appendChild(removeDayBtn2);

            // timeInputs.appendChild(inputGroup1);
            // timeInputs.appendChild(inputGroup2);
            // th.appendChild(timeInputs);
    
            headerRow.appendChild(th);
        });
        calendar.appendChild(headerRow);
    
        let date = 1;
        for (let i = 0; i < 6; i++) {
            const row = document.createElement('tr');
            for (let j = 0; j < 7; j++) {
                const cell = document.createElement('td');
                if (i === 0 && j < firstDay) {
                    cell.textContent = '';
                } else if (date > daysInMonth) {
                    break;
                } else {
                    const cellDate = new Date(year, month, date);
                    cell.textContent = date;

                    cell.dataset.date = cellDate.getFullYear() + '-' +
                        String(cellDate.getMonth() + 1).padStart(2, '0') + '-' +
                        String(cellDate.getDate()).padStart(2, '0');

                    const dayOfWeek = cellDate.toLocaleString('en-us', { weekday: 'long' });
    
                    // --- NEW: Date logic for past, today, and future ---
                    cell.style.pointerEvents = 'auto'; // default allow
                    cell.style.opacity = '1'; // default

                    /***** MOVED TO BOTTOM TO MAKE SURE THE VIEW BOOKING BUTTON @ BOTTOM *****/
                    
                    // if (window.bookingsByDate && window.bookingsByDate[cell.dataset.date]) {
                    //     // If there is a confirmed booking for this date, set background to red
                    //     cell.style.backgroundColor = '#ffcccc';

                    //     // --- Add View Bookings button ---
                    //     const viewBtn = document.createElement('button');
                    //     viewBtn.textContent = 'View Bookings';
                    //     viewBtn.className = 'view-booking-btn';
                    //     viewBtn.style.marginTop = '8px';
                    //     viewBtn.onclick = function(e) {
                    //         e.stopPropagation();
                    //         // For admin (ra/), pass sinderella name as well
                    //         // Use a global variable set in your HTML/PHP template to determine user type and sindName
                    //         if (window.isAdmin) {
                    //             const sindName = window.sindName || '';
                    //             window.location.href = '../ra/view_bookings.php?search_date=' + encodeURIComponent(cell.dataset.date) + '&search_sinderella=' + encodeURIComponent(sindName);
                    //         } else {
                    //             // For sinderella (rs/)
                    //             window.location.href = '../rs/view_bookings.php?search_date=' + encodeURIComponent(cell.dataset.date);
                    //         }
                    //     };
                    //     cell.appendChild(viewBtn);
                    // }

                    // Compare cellDate to today
                    cellDate.setHours(0, 0, 0, 0);
                    if (cellDate < today) {
                        // Past date: grey background, disable editing
                        cell.style.backgroundColor = '#858585';
                        // cell.style.pointerEvents = 'none';
                        // cell.style.opacity = '0.7';
                    } else if (cellDate.getTime() === today.getTime()) {
                        // Today: add red border
                        cell.style.border = '3px solid #e53935';
                    }

                    // Add input fields for start times in the date cell
                    const timeInputs = document.createElement('div');
                    timeInputs.className = 'time-inputs';
                    const input1 = document.createElement('input');
                    input1.type = 'time';
                    input1.className = 'time-input';
                    const input2 = document.createElement('input');
                    input2.type = 'time';
                    input2.className = 'time-input';
    
                    // Create remove icon for input1
                    const removeBtn1 = document.createElement('span');
                    removeBtn1.textContent = '❌';
                    removeBtn1.title = 'Clear this time slot';
                    removeBtn1.style.cursor = 'pointer';
                    removeBtn1.style.marginLeft = '5px';
                    removeBtn1.style.color = '#e53935';
                    removeBtn1.onclick = function(e) {
                        e.stopPropagation();
                        input1.value = '';
                        // Trigger save logic
                        if (cellDate > today) {
                            handleTimeChange(cell.dataset.date, input1, input2);
                        }
                    };

                    // Create remove icon for input2
                    const removeBtn2 = document.createElement('span');
                    removeBtn2.textContent = '❌';
                    removeBtn2.title = 'Clear this time slot';
                    removeBtn2.style.cursor = 'pointer';
                    removeBtn2.style.marginLeft = '5px';
                    removeBtn2.style.color = '#e53935';
                    removeBtn2.onclick = function(e) {
                        e.stopPropagation();
                        input2.value = '';
                        // Trigger save logic
                        if (cellDate > today) {
                            handleTimeChange(cell.dataset.date, input1, input2);
                        }
                    };
                    // Disable inputs for today and earlier
                    if (cellDate <= today) {
                        input1.disabled = true;
                        input2.disabled = true;
                    }

                    // Check if available times exist and handle "00:00:00" case
                    if (availableTimes[cell.dataset.date]) {
                        const from1 = availableTimes[cell.dataset.date].available_from1;
                        const from2 = availableTimes[cell.dataset.date].available_from2;
    
                        input1.value = from1 === '00:00:00' ? '' : from1;
                        input2.value = from2 === '00:00:00' ? '' : from2;
    
                        // Only add the "available" class if at least one time is not NULL
                        if (from1 !== '00:00:00' && from1 !== null || from2 !== '00:00:00' && from2 !== null) {
                            cell.classList.add('available');
                        } else {
                            // cell.style.backgroundColor = '#ffffff'; // Set background color to black
                        }

                    } else if (dayBasedTimes[dayOfWeek]) {
                        const from1 = dayBasedTimes[dayOfWeek].available_from1;
                        const from2 = dayBasedTimes[dayOfWeek].available_from2;
    
                        input1.value = from1 === '00:00:00' ? '' : from1;
                        input2.value = from2 === '00:00:00' ? '' : from2;
    
                        // Only add the "available" class if at least one time is not NULL
                        if (from1 !== '00:00:00' && from1 !== null || from2 !== '00:00:00' && from2 !== null) {
                            cell.classList.add('available');
                        } else {
                            // cell.style.backgroundColor = '#ffffff'; // Set background color to black
                        }
                    }

                    // Only add event listeners if cell is in the future    
                    // Add event listeners for autosave
                    if (cellDate > today) {
                        input1.addEventListener('change', () => handleTimeChange(cell.dataset.date, input1, input2));
                        input2.addEventListener('change', () => handleTimeChange(cell.dataset.date, input1, input2));
                    }

                    // timeInputs.appendChild(input1);
                    // timeInputs.appendChild(removeBtn1);
                    // timeInputs.appendChild(input2);
                    // timeInputs.appendChild(removeBtn2);

                    const inputGroup1 = document.createElement('div');
                    inputGroup1.style.display = 'flex';
                    inputGroup1.style.alignItems = 'center';
                    inputGroup1.appendChild(input1);
                    inputGroup1.appendChild(removeBtn1);

                    const inputGroup2 = document.createElement('div');
                    inputGroup2.style.display = 'flex';
                    inputGroup2.style.alignItems = 'center';
                    inputGroup2.appendChild(input2);
                    inputGroup2.appendChild(removeBtn2);

                    timeInputs.appendChild(inputGroup1);
                    timeInputs.appendChild(inputGroup2);

                    cell.appendChild(timeInputs);
    
                    date++;
                }

                if (window.bookingsByDate && window.bookingsByDate[cell.dataset.date]) {
                    // If there is a confirmed booking for this date, set background to red
                    cell.style.backgroundColor = '#ffcccc';

                    // --- Add View Bookings button ---
                    const viewBtn = document.createElement('button');
                    viewBtn.textContent = 'View Bookings';
                    viewBtn.className = 'view-booking-btn';
                    viewBtn.style.marginTop = '8px';
                    viewBtn.onclick = function(e) {
                        e.stopPropagation();
                        // For admin (ra/), pass sinderella name as well
                        // Use a global variable set in your HTML/PHP template to determine user type and sindName
                        if (window.isAdmin) {
                            const sindName = window.sindName || '';
                            window.location.href = '../ra/view_bookings.php?search_date=' + encodeURIComponent(cell.dataset.date) + '&search_sinderella=' + encodeURIComponent(sindName);
                        } else {
                            // For sinderella (rs/)
                            window.location.href = '../rs/view_bookings.php?search_date=' + encodeURIComponent(cell.dataset.date);
                        }
                    };
                    cell.appendChild(viewBtn);
                }

                row.appendChild(cell);
            }
            calendar.appendChild(row);
        }
    
        const monthYearRow = document.createElement('tr');
        const monthYearCell = document.createElement('td');
        monthYearCell.colSpan = 7;
        monthYearCell.textContent = `${monthNames[month]} ${year}`;
        monthYearCell.className = 'month-year';
        monthYearRow.appendChild(monthYearCell);
        calendar.insertBefore(monthYearRow, calendar.firstChild);

        return calendar;
    }

    function renderDayBasedTable() {
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Normalize to midnight

        // const firstDay = new Date(year, month).getDay();
        // const daysInMonth = new Date(year, month + 1, 0).getDate();
        const calendar = document.createElement('table');
        calendar.className = 'calendar';
    
        const headerRow = document.createElement('tr');
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        daysOfWeek.forEach(day => {
            const th = document.createElement('th');
            th.textContent = day;
    
            // Add time input fields for the day header
            const timeInputs = document.createElement('div');
            timeInputs.className = 'time-inputs';
    
            const input1 = document.createElement('input');
            input1.type = 'time';
            input1.className = 'time-input';
            input1.value = dayBasedTimes[day]?.available_from1 === '00:00:00' ? '' : dayBasedTimes[day]?.available_from1 || '';
    
            const input2 = document.createElement('input');
            input2.type = 'time';
            input2.className = 'time-input';
            input2.value = dayBasedTimes[day]?.available_from2 === '00:00:00' ? '' : dayBasedTimes[day]?.available_from2 || '';
        
            // Create remove icon for input1
            const removeDayBtn1 = document.createElement('span');
            removeDayBtn1.textContent = '❌';
            removeDayBtn1.title = 'Clear this time slot';
            removeDayBtn1.style.cursor = 'pointer';
            removeDayBtn1.style.marginLeft = '5px';
            removeDayBtn1.style.color = '#e53935';
            removeDayBtn1.onclick = function(e) {
                e.stopPropagation();
                input1.value = '';
                handleDayTimeChange(day, input1, input2);
            };

            // Create remove icon for input2
            const removeDayBtn2 = document.createElement('span');
            removeDayBtn2.textContent = '❌';
            removeDayBtn2.title = 'Clear this time slot';
            removeDayBtn2.style.cursor = 'pointer';
            removeDayBtn2.style.marginLeft = '5px';
            removeDayBtn2.style.color = '#e53935';
            removeDayBtn2.onclick = function(e) {
                e.stopPropagation();
                input2.value = '';
                handleDayTimeChange(day, input1, input2);
            };
    
            // Add event listeners for autosave
            input1.addEventListener('change', () => handleDayTimeChange(day, input1, input2));
            input2.addEventListener('change', () => handleDayTimeChange(day, input1, input2));
    
            const inputGroup1 = document.createElement('div');
            inputGroup1.style.display = 'flex';
            inputGroup1.style.alignItems = 'center';
            inputGroup1.appendChild(input1);
            inputGroup1.appendChild(removeDayBtn1);

            const inputGroup2 = document.createElement('div');
            inputGroup2.style.display = 'flex';
            inputGroup2.style.alignItems = 'center';
            inputGroup2.appendChild(input2);
            inputGroup2.appendChild(removeDayBtn2);

            timeInputs.appendChild(inputGroup1);
            timeInputs.appendChild(inputGroup2);
            th.appendChild(timeInputs);
    
            headerRow.appendChild(th);
        });
        calendar.appendChild(headerRow);
    
        const monthYearRow = document.createElement('tr');
        const monthYearCell = document.createElement('td');
        monthYearCell.colSpan = 7;
        monthYearCell.textContent = `Day-Based Weekly Schedule`;
        monthYearCell.className = 'month-year';
        monthYearRow.appendChild(monthYearCell);
        calendar.insertBefore(monthYearRow, calendar.firstChild);

        return calendar;
    }

    // function handleTimeChange(date, input1, input2) {
    //     if (!validateTimes(input1, input2)) return;
    //     const availableFrom1 = input1.value ? roundToNearest30(input1.value) : null;
    //     const availableFrom2 = input2.value ? roundToNearest30(input2.value) : null;
    //     saveTime(date, availableFrom1, availableFrom2);
    // }

    function handleTimeChange(date, input1, input2) {
        if (!validateTimes(input1, input2)) return;

        fetch(`../rs/get_confirmed_bookings_for_date.php?sind_id=${encodeURIComponent(sind_id)}&date=${encodeURIComponent(date)}`)
            .then(response => response.json())
            .then(bookings => {
                const bookedTimes = bookings.map(b => b.booking_from_time);

                function normalizeTime(t) {
                    if (!t) return null;
                    if (t.length === 5) return t + ':00';
                    return t;
                }
                const t1 = normalizeTime(input1.value ? roundToNearest30(input1.value) : null);
                const t2 = normalizeTime(input2.value ? roundToNearest30(input2.value) : null);

                let blocked = false;

                // Prevent changing or clearing a booked slot
                if (bookedTimes.includes(availableTimes[date]?.available_from1)) {
                    if (t1 !== availableTimes[date]?.available_from1) {
                        alert('You cannot change or remove the first time slot because it is already booked by a customer.');
                        input1.value = availableTimes[date]?.available_from1?.slice(0,5) || '';
                        blocked = true;
                    }
                }
                if (bookedTimes.includes(availableTimes[date]?.available_from2)) {
                    if (t2 !== availableTimes[date]?.available_from2) {
                        alert('You cannot change or remove the second time slot because it is already booked by a customer.');
                        input2.value = availableTimes[date]?.available_from2?.slice(0,5) || '';
                        blocked = true;
                    }
                }

                if (!blocked) {
                    saveTime(date, t1, t2);
                }
            })
            .catch(error => {
                console.error('Error checking bookings:', error);
                const t1 = input1.value ? roundToNearest30(input1.value) : null;
                const t2 = input2.value ? roundToNearest30(input2.value) : null;
                saveTime(date, t1, t2);
            });
    }

    function roundToNearest30(time) {
        if (!time) return null;
        if (time === null) return null;
        const [hours, minutes] = time.split(':').map(Number);
        const roundedMinutes = minutes < 15 ? '00' : minutes < 45 ? '30' : '00';
        const adjustedHours = minutes >= 45 && roundedMinutes === '00' ? (hours + 1) % 24 : hours;
        return `${adjustedHours.toString().padStart(2, '0')}:${roundedMinutes}`;
    }    

    function handleDayClick(day) {
        selectedDay = day;
        selectedDate = null;
    
        // Create a form for batch updating the day
        const dayFormContainer = document.createElement('div');
        dayFormContainer.className = 'day-form-container';
    
        const title = document.createElement('h3');
        title.textContent = `Update Schedule for: ${day}`;
        dayFormContainer.appendChild(title);
    
        const input1 = document.createElement('input');
        input1.type = 'time';
        input1.className = 'time-input';
        input1.placeholder = 'Start Time 1';
        input1.value = dayBasedTimes[day]?.available_from1 || '';
    
        const input2 = document.createElement('input');
        input2.type = 'time';
        input2.className = 'time-input';
        input2.placeholder = 'Start Time 2';
        input2.value = dayBasedTimes[day]?.available_from2 || '';
    
        const saveButton = document.createElement('button');
        saveButton.textContent = 'Save';
        saveButton.addEventListener('click', () => {
            const availableFrom1 = roundToNearest30(input1.value);
            const availableFrom2 = roundToNearest30(input2.value);
    
            // Save the batch update for the selected day
            const formData = new FormData();
            formData.append('sind_id', sind_id);
            formData.append('day', day);
            formData.append('available_from1', availableFrom1 || null);
            formData.append('available_from2', availableFrom2 || null);
    
            fetch('../rs/save_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())  // Get the response as text first
            .then(data => {
                try {
                    const jsonData = JSON.parse(data);  // Try to parse it as JSON
                    if (jsonData.success) {
                        loadAvailableTimes();
                    } else {
                        console.error('Failed to update schedule:', jsonData.message);
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    console.error('Received data:', data);  // Log the raw response to identify HTML or error message
                }
            })
            .catch(error => {
                console.error('Error updating schedule:', error);
            });                     
        });
    
        const cancelButton = document.createElement('button');
        cancelButton.textContent = 'Cancel';
        cancelButton.addEventListener('click', () => {
            dayFormContainer.remove();
        });
    
        dayFormContainer.appendChild(input1);
        dayFormContainer.appendChild(input2);
        dayFormContainer.appendChild(saveButton);
        dayFormContainer.appendChild(cancelButton);
    
        // Append the form to the calendar container
        calendarContainer.appendChild(dayFormContainer);
    }

    function handleDayTimeChange(day, input1, input2) {
        if (!validateTimes(input1, input2)) return;
        const availableFrom1 = input1.value ? roundToNearest30(input1.value) : null;
        const availableFrom2 = input2.value ? roundToNearest30(input2.value) : null;

        const formData = new FormData();
        formData.append('sind_id', sind_id);
        formData.append('day', day);
        formData.append('available_from1', availableFrom1);
        formData.append('available_from2', availableFrom2);

        fetch('../rs/save_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            try {
                const jsonData = JSON.parse(data);
                if (jsonData.success) {
                    loadAvailableTimes();
                } else {
                    console.error('Failed to update schedule:', jsonData.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.error('Received data:', data);
            }
        })
        .catch(error => {
            console.error('Error updating schedule:', error);
        });
    }

    function saveTime(date, availableFrom1, availableFrom2) {
        const formData = new FormData();
        formData.append('sind_id', sind_id);
        formData.append('date', date);
        // formData.append('available_from1', availableFrom1);
        // formData.append('available_from2', availableFrom2);
        if (availableFrom1 !== null) formData.append('available_from1', availableFrom1);
        if (availableFrom2 !== null) formData.append('available_from2', availableFrom2);

        // if (availableFrom1) {
        //     formData.append('available_from1', availableFrom1);
        // } else {
        //     formData.append('available_from1', ''); // Send an empty string if no time is set
        // }
    
        // if (availableFrom2) {
        //     formData.append('available_from2', availableFrom2);
        // } else {
        //     formData.append('available_from2', ''); // Send an empty string if no time is set
        // }
    
        fetch('../rs/save_schedule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text()) // Get the response as text first
        .then(data => {
            console.log('Raw response:', data); // Log the raw response
            try {
                const jsonData = JSON.parse(data); // Try to parse it as JSON
                if (jsonData.success) {
                    loadAvailableTimes();
                } else {
                    console.error('Failed to update schedule:', jsonData.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                console.error('Received data:', data); // Log the raw response to identify HTML or error message
            }
        })
        .catch(error => {
            console.error('Error updating schedule:', error);
        });
    }

    function loadAvailableTimes() {
        // fetch('../rs/get_available_times.php', {
        //     method: 'GET'
        // })
        fetch('../rs/get_available_times.php?sind_id=' + encodeURIComponent(sind_id), {
            method: 'GET'
        })
        .then(response => response.json()).then(data => {
            availableTimes = data.dateBased;
            dayBasedTimes = data.dayBased;
            // loadCalendars();
            loadBookingsForCalendar();
        }).catch(error => {
            console.error('Error loading available times:', error);
        });
    }

    function loadCalendars() {
        calendarContainer.innerHTML = '';
        // Render and append the day-based table at the top
        const dayBasedTable = renderDayBasedTable();
        const dayBasedTableContainer = document.getElementById('dayBasedTableContainer');
        dayBasedTableContainer.innerHTML = '';
        dayBasedTableContainer.appendChild(dayBasedTable);

        const today = new Date();
        const currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

        const currentMonthCalendar = renderCalendar(currentMonth.getMonth(), currentMonth.getFullYear());
        const nextMonthCalendar = renderCalendar(nextMonth.getMonth(), nextMonth.getFullYear());
        calendarContainer.appendChild(currentMonthCalendar);
        const breakElem = document.createElement('br');
        calendarContainer.appendChild(breakElem);
        calendarContainer.appendChild(nextMonthCalendar);
    }

    loadAvailableTimes();

    function isTimeInRange(time) {
        if (!time) return true;
        // Accepts "HH:MM" or "HH:MM:SS"
        const [h, m] = time.split(':').map(Number);
        const minutes = h * 60 + m;
        const min = 8 * 60;   // 08:00
        const max = 14 * 60;  // 14:00
        return minutes >= min && minutes <= max;
    }

    function getMinutes(time) {
        if (!time) return null;
        const [h, m] = time.split(':').map(Number);
        return h * 60 + m;
    }

    function isAtLeast4HoursApart(time1, time2) {
        if (!time1 || !time2) return true;
        const diff = Math.abs(getMinutes(time1) - getMinutes(time2));
        return diff >= 240; // 4 hours = 240 minutes
    }

    function validateTimes(input1, input2) {
        const t1 = input1.value;
        const t2 = input2.value;

        // Check time range
        if (t1 && !isTimeInRange(t1)) {
            alert('Time entered exceeds working hours. Please enter a time between 8:00am and 2:00pm only.');
            input1.value = '';
            return false;
        }
        if (t2 && !isTimeInRange(t2)) {
            alert('Time entered exceeds working hours. Please enter a time between 8:00am and 2:00pm only.');
            input2.value = '';
            return false;
        }

        // Check 4 hours apart
        if (t1 && t2 && !isAtLeast4HoursApart(t1, t2)) {
            alert('The two times must be at least 4 hours apart.');
            input2.value = '';
            return false;
        }
        return true;
    }

    // --- NEW: Fetch bookings for the current and next month ---
    function loadBookingsForCalendar() {
        fetch('../rs/get_bookings_for_calendar.php')
            .then(response => response.json())
            .then(data => {
                window.bookingsByDate = data; // { 'YYYY-MM-DD': true, ... }
                loadCalendars(); // re-render calendar with booking info
            })
            .catch(error => {
                window.bookingsByDate = {};
                loadCalendars();
            });
    }


});