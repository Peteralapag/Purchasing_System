<?php
$reports_selections = ['PURCHASE REQUEST','PURCHASE CANVASSING','PURCHASE ORDERS'];
?>

<style>
.smnav-header {
    padding: 10px 15px;
    border-bottom: 1px solid #ccc;
}

.smnav-header .sidebar-title {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

.report-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.report-select, .report-date, #statusSelect {
    padding: 5px 8px;
    border-radius: 4px;
    border: 1px solid #ccc;
    font-size: 13px;
}
</style>

<div class="smnav-header">
    <h5 class="sidebar-title">Reports</h5>
    
    <div class="report-filters">
        <!-- Report Type -->
        <select id="reportType" class="form-control form-control-sm">
            <option value="">-- Select Reports --</option>
            <?php foreach($reports_selections as $report): ?>
                <option value="<?= htmlspecialchars($report) ?>"><?= ucwords(strtolower($report)) ?></option>
            <?php endforeach; ?>
        </select>
		
		<!-- Status Select -->
        <select id="statusSelect" class="form-control form-control-sm">
            <option value="">-- All Statuses --</option>
        </select>
        
        <!-- Date Range -->
        <input type="date" id="dateFrom" class="report-date" placeholder="From">
        <input type="date" id="dateTo" class="report-date" placeholder="To">

        <!-- Load Button -->
        <button id="loadBtn" class="btn btn-primary btn-sm" onclick="loadReport()">
            <i class="fa fa-sync"></i> Load
        </button>
        
        <button id="exportBtn" class="btn btn-success btn-sm" onclick="exportExcel()">
		    <i class="fa fa-file-excel"></i> Export to Excel
		</button>
		
    </div>
</div>

<div class="tableFixHead" id="smnavdata"></div>

<script>
window.reportStatusOptions = {
    "PURCHASE REQUEST": ['returned','pending','approved','rejected','for_canvassing','canvassing_reviewed','canvassing_approved','partial_conversion','converted','convert_rejected'],
    "PURCHASE CANVASSING": ['OPEN','FOR_APPROVAL','APPROVED','REJECTED','PARTIAL_PO_CREATED','PO_CREATED'],
    "PURCHASE ORDERS": ['PENDING','APPROVED','PARTIAL_RECEIVED','RECEIVED','CANCELLED']
};

// Update status select when report type changes
$('#reportType').on('change', function() {
    const reportType = $(this).val();
    const statuses = window.reportStatusOptions[reportType] || [];
    const $statusSelect = $('#statusSelect');

    $statusSelect.empty(); // clear previous options
    $statusSelect.append('<option value="">-- All Statuses --</option>'); // default = all

    statuses.forEach(status => {
        const displayText = status.replace(/_/g, ' ').toUpperCase(); // convert to ALL CAPS, replace underscore
        $statusSelect.append(`<option value="${status}">${displayText}</option>`);
    });
});

// Trigger once on page load to populate default report
$('#reportType').trigger('change');

function loadReport() {
    const reportType = $('#reportType').val();
    const status = $('#statusSelect').val(); // empty = all
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();

    if(!reportType){
        swal("Oops!", "Please select a report type.", "warning");
        return;
    }

    if(!dateFrom || !dateTo) {
        swal("Oops!", "Please select a date range.", "warning");
        return;
    }
    
    const fromDate = new Date(dateFrom);
    const toDate = new Date(dateTo);
    const diffTime = toDate - fromDate;
    const diffDays = diffTime / (1000 * 60 * 60 * 24) + 1;

    if(diffDays > 31) {
        swal("Oops!", "Maximum date range is 31 days.", "warning");
        return;
    }

    const reportFiles = {
        "PURCHASE REQUEST": "purchase_request_report",
        "PURCHASE CANVASSING": "purchase_canvassing_report",
        "PURCHASE ORDERS": "purchase_orders_report"
    };

    const reportFile = reportFiles[reportType];
    if(!reportFile){
        swal("Oops!", "Invalid report type selected.", "error");
        return;
    }

    // Send selected status too (empty = all)
    $.post("./Modules/Purchasing_System/includes/" + reportFile + ".php", 
        { from: dateFrom, to: dateTo, status: status },
        function(data){
            $('#smnavdata').html(data);
        }
    );
}

function exportExcel() {
    const reportType = $('#reportType').val();
    const status = $('#statusSelect').val();
    const dateFrom = $('#dateFrom').val();
    const dateTo = $('#dateTo').val();

    if(!reportType){
        swal("Oops!", "Please select a report type.", "warning");
        return;
    }

    if(!dateFrom || !dateTo) {
        swal("Oops!", "Please select a date range.", "warning");
        return;
    }

    const reportFiles = {
        "PURCHASE REQUEST": "purchase_request_export",
        "PURCHASE CANVASSING": "purchase_canvassing_export",
        "PURCHASE ORDERS": "purchase_orders_export"
    };

    const exportFile = reportFiles[reportType];
    if(!exportFile){
        swal("Oops!", "Invalid report type selected.", "error");
        return;
    }

    // open download in new tab
    const params = new URLSearchParams({
        from: dateFrom,
        to: dateTo,
        status: status
    });

    window.open(
        "./Modules/Purchasing_System/includes/" + exportFile + ".php?" + params.toString(),
        "_blank"
    );
}

</script>
