<!-- Dashboard Notifications -->
<div class="row g-3">

    <!-- Pending PO Emails -->
    <div class="col-md-4">
        <div class="card shadow-sm hover-shadow cursor-pointer" id="pending-emails-card">
            <div class="card-body d-flex align-items-center">
                <div class="icon-wrapper me-3">
                    <i class="fas fa-envelope fa-3x text-danger"></i>
                </div>
                <div>
                    <h6 class="text-uppercase text-muted mb-1">Pending PO Emails</h6>
                    <h2 id="pending-emails-count" class="fw-bold mb-0">0</h2>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
/* Card hover effect */
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.15);
}
.cursor-pointer {
    cursor: pointer;
}
.icon-wrapper i {
    transition: transform 0.3s ease;
}
.hover-shadow:hover .icon-wrapper i {
    transform: rotate(15deg) scale(1.1);
}
</style>

<script>
document.getElementById('pending-emails-card').addEventListener('click', () => {
    $.post("./Modules/Purchasing_System/includes/pending_po_emails.php", {}, function(data) {
        $('#contents').html(data);
    });
});

function fetchDashboardCounts() {
    fetch('./Modules/Purchasing_System/actions/dashboard_notify.php')
        .then(res => res.json())
        .then(data => {
            const pendingEmails = document.getElementById('pending-emails-count');
            if (pendingEmails) pendingEmails.innerText = data.pending_emails ?? 0;
        })
        .catch(err => console.error('Error fetching dashboard counts:', err));
}

// Initial fetch
fetchDashboardCounts();

// Auto-refresh every 1 min
setInterval(fetchDashboardCounts, 60000);
</script>