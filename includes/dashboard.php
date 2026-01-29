<!-- Dashboard Notifications -->
<div class="row">

    <!-- Approved POs -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-file-invoice fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">Approved POs</h5>
                    <h3 id="po-count">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved PRs -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-clipboard-list fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">Approved PRs</h5>
                    <h3 id="pr-count">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Canvassing -->
    <div class="col-md-4 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body d-flex align-items-center">
                <i class="fas fa-shopping-cart fa-3x me-3"></i>
                <div>
                    <h5 class="card-title">Approved Canvassing</h5>
                    <h3 id="pc-count">0</h3>
                </div>
            </div>
        </div>
    </div>

</div>


<script>

function fetchDashboardCounts() {
    fetch('./Modules/Purchasing_System/actions/dashboard_notify.php')
        .then(res => res.json())
        .then(data => {
            const po = document.getElementById('po-count');
            const pr = document.getElementById('pr-count');
            const pc = document.getElementById('pc-count');

            if (po) po.innerText = data.po ?? 0;
            if (pr) pr.innerText = data.pr ?? 0;
            if (pc) pc.innerText = data.pc ?? 0;
        })
        .catch(err => console.error('Error fetching dashboard counts:', err));
}

// Initial fetch
fetchDashboardCounts();

// Auto-refresh every 1 min
setInterval(fetchDashboardCounts, 60000);


</script>
