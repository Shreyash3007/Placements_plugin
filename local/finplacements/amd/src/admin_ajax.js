/**
 * Intercepts status change button clicks in the admin table and fires an asynchronous fetch
 * to the hidden ajax.php endpoint to avoid full page reloads.
 */
export const init = () => {
    document.addEventListener('click', (e) => {
        let btn = e.target.closest('[data-action="status-change"]');
        if (!btn) return;
        
        e.preventDefault();
        
        if (!confirm("Are you sure you want to change the status to " + btn.dataset.newstatus + "?")) {
            return;
        }
        
        let appid = btn.dataset.appid;
        let newstatus = btn.dataset.newstatus;
        let sesskey = M.cfg.sesskey;
        let wrapper = document.getElementById('fp-status-cell-' + appid);
        
        // Visual loading feedback
        let originalText = btn.innerHTML;
        btn.innerHTML = '↻...';
        btn.disabled = true;

        let formData = new URLSearchParams();
        formData.append('sesskey', sesskey);
        formData.append('action', 'updatestatus');
        formData.append('appid', appid);
        formData.append('newstatus', newstatus);

        fetch(M.cfg.wwwroot + '/local/finplacements/ajax.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error("HTTP error " + res.status);
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                // If the backend processed it, visually overwrite the entire cell with the new badge
                // and strip out the action buttons since the status has progressed.
                if (wrapper) {
                    wrapper.innerHTML = '<span class="fp-badge fp-status-' + newstatus + '">' + newstatus.toUpperCase() + '</span>'
                                      + '<div style="margin-top:4px;font-size:0.75rem;color:green;">✓ Updated instantly</div>';
                }
            } else {
                alert("FinTree Background Error: " + data.message);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);
            alert("Connection error communicating with the Moodle server.");
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
};
