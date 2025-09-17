// /includes/js/forgot_pwd.js
(function () {
  const btn   = document.getElementById('getCodeButton');
  const phone = document.getElementById('phone');
  const errEl = document.getElementById('error-message');

  function setError(msg) {
    if (errEl) errEl.textContent = msg || '';
  }

  async function postForm(url, data) {
    const body = new URLSearchParams(data);
    const res  = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body
    });
    return res.json();
  }

  function startCooldown(seconds) {
    let left = seconds;
    btn.disabled = true;
    const orig = btn.textContent;
    const timer = setInterval(() => {
      btn.textContent = `Get Code (${left}s)`;
      left -= 1;
      if (left <= 0) {
        clearInterval(timer);
        btn.disabled = false;
        btn.textContent = orig;
      }
    }, 1000);
  }

  btn?.addEventListener('click', async () => {
    setError('');
    const ph = (phone?.value || '').trim();
    if (!ph) { setError('Please enter your phone number first.'); return; }

    try {
      const res = await postForm('../request_otp.php', { phone: ph });
      if (res.ok) {
        alert('OTP has been sent to your WhatsApp. It expires in ' + (res.ttl_min || 5) + ' minutes.');
        startCooldown(60); // must match OTP_RATE_LIMIT_SECONDS
      } else {
        setError(res.error || 'Failed to send OTP.');
        if (res.details && res.details.response && res.details.response.error && res.details.response.error.message) {
          // helpful API error from Meta (optional to display)
          console.warn('WhatsApp API:', res.details.response.error.message);
        }
      }
    } catch (e) {
      setError('Network error. Please try again.');
      console.error(e);
    }
  });
})();

function toggleAllPasswords() {
    var show = document.getElementById('showPasswordAll').checked;
    var fields = ['password', 'confirm_password', 'old_password', 'new_password'];
    fields.forEach(function(id) {
        var field = document.getElementById(id);
        if (field) field.type = show ? 'text' : 'password';
    });
}
