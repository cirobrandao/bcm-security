(function(){
  const byId = (id) => document.getElementById(id);

  async function post(action, nonce){
    const form = new URLSearchParams();
    form.set('action', action);
    form.set('_ajax_nonce', nonce);

    const res = await fetch(ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    });

    const json = await res.json();
    if(!json || !json.success){
      const msg = (json && json.data && json.data.message) ? json.data.message : 'Request failed.';
      throw new Error(msg);
    }
    return json.data;
  }

  function setStatus(el, type, text){
    el.className = 'securitywp-status securitywp-status--' + type;
    el.textContent = text;
  }

  function runTool(btnId, statusId, action, nonce, runningText, doneText){
    const btn = byId(btnId);
    const status = byId(statusId);
    if(!btn || !status) return;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      btn.disabled = true;
      setStatus(status, 'running', runningText);

      const started = Date.now();
      try {
        const data = await post(action, nonce);
        const seconds = ((Date.now() - started)/1000).toFixed(1);
        setStatus(status, 'ok', doneText.replace('%s', seconds));
        if(data && data.summary){
          status.textContent += ' ' + data.summary;
        }
      } catch (err){
        setStatus(status, 'error', err.message || 'Error');
      } finally {
        btn.disabled = false;
      }
    });
  }

  function setupDependencies(){
    const inputs = Array.from(document.querySelectorAll('[data-securitywp-depends-on]'));
    if(!inputs.length) return;

    const update = () => {
      inputs.forEach((el) => {
        const depKey = el.getAttribute('data-securitywp-depends-on');
        if(!depKey) return;
        const dep = document.querySelector('input[name="securitywp_settings[' + depKey + ']"]');
        const enabled = dep ? dep.checked : true;
        el.disabled = !enabled;

        // When disabled, clear browser validation UI by removing "required" if any.
        if(!enabled && el.tagName === 'INPUT' && el.type === 'number'){
          // Keep value as-is, just disable.
        }
      });
    };

    document.addEventListener('change', (e) => {
      const t = e.target;
      if(t && t.name && t.name.startsWith('securitywp_settings[')){
        update();
      }
    });

    update();
  }

  document.addEventListener('DOMContentLoaded', function(){
    setupDependencies();

    if(window.SecurityWPAdmin){
      runTool('securitywp-baseline-btn', 'securitywp-baseline-status', 'securitywp_baseline_ajax', SecurityWPAdmin.nonce, SecurityWPAdmin.i18n.baseline_running, SecurityWPAdmin.i18n.baseline_done);
      runTool('securitywp-scan-btn', 'securitywp-scan-status', 'securitywp_scan_ajax', SecurityWPAdmin.nonce, SecurityWPAdmin.i18n.scan_running, SecurityWPAdmin.i18n.scan_done);
    }
  });
})();
