<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) die("DB connection failed");

// Fetch pending emails
$sql = "SELECT id, po_number, supplier_email, subject, created_at 
        FROM purchase_order_email_queue 
        WHERE status='pending'
        ORDER BY created_at DESC";
$result = $db->query($sql);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-primary">Pending PO Emails</h3>
    <button class="btn btn-outline-secondary" onclick="openMenuGranted('dashboard')">
        <i class="fas fa-arrow-left me-1"></i> Back
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small">
                <tr>
                    <th>PO Number</th>
                    <th>Supplier Email</th>
                    <th>Subject</th>
                    <th>Created At</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($row['po_number']) ?></td>
                    <td><?= htmlspecialchars($row['supplier_email']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-info btn-sm me-2" onclick="viewPO('<?= $row['po_number'] ?>')">
                            <i class="fas fa-eye me-1"></i> View
                        </button>
                        <button class="btn btn-success btn-sm" onclick="sendEmail(<?= $row['id'] ?>, this)">
                            <i class="fas fa-paper-plane me-1"></i> Send
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-3 text-muted">No pending emails</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<script>


function viewPO(po_number) {
    
	$.post("./Modules/Purchasing_System/apps/view_po.php", { po_number: po_number },
	function(data) {		
		$('#formmodal_page').html(data);
		$('#formmodal').show();
	});
}




function sendEmail(emailId, btn) {
    swal({
        title: "Send PO Email?",
        text: "Are you sure you want to send this PO email to the supplier?",
        icon: "warning",
        buttons: true,
        dangerMode: true,
    })
    .then((willSend) => {
        if (!willSend) return;

        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Sending...';

        let fd = new FormData();
        fd.append('id', emailId);

        fetch('./Modules/Purchasing_System/actions/send_po_emails.php', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(resp => {
            console.log('Server response:', resp); // <-- important
            if(resp.status === 'success'){
                swal('Success', resp.message || 'PO email sent successfully!', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                swal('Error', resp.message || 'Failed to send email.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHTML;
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            swal('Error', 'Something went wrong.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    });
}

</script>