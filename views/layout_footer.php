            </main>
        </div>
    </div>

    <div id="toast-container" class="nb-toast-container"></div>

    <script src="<?= BASE_URL ?>/js/app.js"></script>
    <script>
    function showToast(msg, type) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = 'nb-toast nb-toast-' + (type || 'success');
        const icon = type === 'error' ? 'fa-exclamation-circle' : type === 'info' ? 'fa-info-circle' : 'fa-check-circle';
        toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + msg;
        container.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(100%)'; toast.style.transition = 'all 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
    }
    function copyLink(code) {
        const url = '<?= BASE_URL ?>/' + code;
        navigator.clipboard.writeText(url).then(() => showToast('<?= $t['links_copied'] ?? "Copied!" ?>', 'success'));
    }
    function deleteConfirm(e) {
        if (!confirm('<?= $t['links_delete_confirm'] ?? "Delete permanently?" ?>')) { e.preventDefault(); return false; }
        return true;
    }
    </script>
</body>
</html>
