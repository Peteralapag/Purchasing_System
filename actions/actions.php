<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
$db->set_charset("utf8mb4");
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/Class.functions.php";
require $_SERVER['DOCUMENT_ROOT']."/Modules/Purchasing_System/class/PurchasingController.php";
$function = new WMSFunctions;
$purchController = new PurchasingController($db);

if(isset($_POST['mode']) && $_POST['mode'] != '')
{
	$mode = $_POST['mode'];
} else {
	print_r('
		<script>
			app_alert("Warning"," The Mode you are trying to pass does not exist","warning","Ok","","no");
		</script>
	');
	exit();
}
if(isset($_SESSION['purch_appnameuser']))
{
	$app_user = strtolower($_SESSION['purch_appnameuser']);
	$app_user = ucwords($app_user);
}
$date = date("Y-m-d");
$date_time = date("Y-m-d H:i:s");
$year_now = date("Y");




if ($mode == 'returntopr') {

    $prnumber = $_POST['prnumber'] ?? '';
    $remarks  = trim($_POST['remarks'] ?? '');

    if(empty($prnumber)){
        echo json_encode([
            'status' => 'error',
            'message' => 'PR Number is missing.'
        ]);
        exit;
    }

    // Fetch current PR info
    $stmt = $db->prepare("
        SELECT status, approved_by, approved_at 
        FROM purchase_request 
        WHERE pr_number = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $pr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if(!$pr){
        echo json_encode([
            'status' => 'error',
            'message' => 'Purchase Request not found.'
        ]);
        exit;
    }

    if($pr['approved_by'] == 'approved'){
        echo json_encode([
            'status' => 'error',
            'message' => 'Cannot return PR: already push to canvassing.'
        ]);
        exit;
    }

    // Safe to return: reset prepared_by and prepared_date
    $stmt = $db->prepare("
        UPDATE purchase_request 
        SET reviewed_by = NULL, reviewed_at = NULL, approved_by = NULL, approved_at = NULL, status = 'returned',  remarks = ?
        WHERE pr_number = ?
    ");
    $stmt->bind_param("ss", $remarks, $prnumber);
    $updated = $stmt->execute();
    $stmt->close();

    if($updated){
        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase Request returned to prepared stage successfully.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to return Purchase Request. Please try again.'
        ]);
    }

    exit;
}



if ($mode == 'savecanvassing') {

	$pr_no = $_POST['prnumber'] ?? '';
	$user = $_SESSION['purch_appnameuser']?? '';

	if(!$pr_no){
	    echo json_encode(['status'=>'error','message'=>'Invalid PR']);
	    exit;
	}
	
	// check if already pushed
	$chk = $db->query("SELECT id, status FROM purchase_request WHERE pr_number='$pr_no'");
	$row = $chk->fetch_assoc();
	
	$status = $row['status'];
	$rowid = $row['id'];
	
	if($status === 'for_canvassing' || $status === 'for_canvassing_approved' || $status === 'for_canvassing_rejected' || $status === 'converted' || $status === 'converted_rejected'){
	    echo json_encode(['status'=>'error','message'=>'Already in Canvassing']);
	    exit;
	}
	
	$db->begin_transaction();
	
	try{
	
	    // generate canvass no
	    $canvass_no = 'CV-' . date('Ymd-His');
	
	    // insert canvassing head
	    $db->query("
	        INSERT INTO purchase_canvassing 
	        (canvass_no, pr_no, requested_by, status, source, remarks, created_at)
	        SELECT '$canvass_no', pr_number, '$user', 'OPEN', source, remarks, NOW()
	        FROM purchase_request
	        WHERE id='$rowid'
	    ");
	
	    // insert items
	    $db->query("
	        INSERT INTO purchase_canvassing_items
	        (canvass_no,item_code,item_description,quantity,unit,estimated_cost)
	        SELECT '$canvass_no', item_code, item_description, quantity, unit, estimated_cost
	        FROM purchase_request_items
	        WHERE pr_id='$rowid' 
	    ");
	
	    // update PR status
	    $db->query("
	        UPDATE purchase_request
	        SET status='for_canvassing'
	        WHERE id='$rowid'
	    ");
	
	    $db->commit();
	
	    echo json_encode(['status'=>'success']);
	
	}catch(Exception $e){
	    $db->rollback();
	    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
	}



}

if ($mode == 'editpo') {

    $po_id             = $_POST['po_id'] ?? 0;
    $pr_id             = $_POST['pr_id'] ?? 0;
    $supplier_id       = $_POST['supplier_id'] ?? 0;
    $order_date        = $_POST['order_date'] ?? date('Y-m-d');
    $expected_delivery = $_POST['expected_delivery'] ?? null;

    if (!$po_id || !$pr_id || !$supplier_id) {
        echo 'Error: PO ID, PR, and Supplier are required.';
        exit;
    }

    // Fetch current PO status
    $stmt = $db->prepare("SELECT status FROM purchase_orders WHERE id=?");
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $po = $result->fetch_assoc();
    $stmt->close();

    if (!$po) {
        echo 'Error: PO not found.';
        exit;
    }

    // Only allow edit if status is Pending
    if ($po['status'] != 'PENDING') {
        echo 'Error: Only Pending PO can be edited.';
        exit;
    }

    // SYSTEM CONTROLLED FIELDS
    $updated_by  = $_SESSION['userid'] ?? 0;
    $updated_at  = date('Y-m-d H:i:s');

    // Update PO
    $stmt = $db->prepare("
        UPDATE purchase_orders
        SET pr_number=?, supplier_id=?, order_date=?, expected_delivery=?, updated_by=?, updated_at=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "sisssii",
        $pr_id,
        $supplier_id,
        $order_date,
        $expected_delivery,
        $updated_by,
        $updated_at,
        $po_id
    );

    if ($stmt->execute()) {
        // Log the edit
        $purchController->savePurchasingLog('PO', $po_id, 'EDITED', $_SESSION['purch_appnameuser'] ?? 'system');
        echo 'PO updated successfully';
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}


if ($mode == 'savepo') {

    $pr_id             = $_POST['pr_id'] ?? 0;
    $supplier_id       = $_POST['supplier_id'] ?? 0;
    $branch            = $_POST['branch'] ?? 0;
    $order_date        = $_POST['po_date'] ?? date('Y-m-d');
    $expected_delivery = $_POST['expected_date'] ?? null;
    $remarks           = $_POST['remarks'] ?? null;

    if (!$pr_id || !$supplier_id) {
        exit('Error: PR and Supplier are required.');
    }

    $status     = 'Pending';
    $created_by = $app_user ?? '';
    $created_at = date('Y-m-d H:i:s');

    $subtotal = $vat = $total_amount = 0;

    // ==============================
    // GET SOURCE & CANVASS_NO
    // ==============================
    $stmt = $db->prepare("
        SELECT source, canvass_no
        FROM purchase_canvassing
        WHERE pr_no = ? AND status IN ('APPROVED','PARTIAL_PO_CREATED')
        LIMIT 1
    ");
    $stmt->bind_param("s", $pr_id);
    $stmt->execute();
    $canvass = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$canvass) exit('Error: Approved or Partial PO canvassing not found.');

    $source      = $canvass['source'];
    $canvass_no  = $canvass['canvass_no'];

    // ==============================
    // GENERATE PO NUMBER
    // ==============================
    $res = $db->query("SELECT COUNT(*) total FROM purchase_orders");
    $row = $res->fetch_assoc();
    $po_number = 'PO-' . date('Y') . '-' . str_pad($row['total'] + 1, 6, '0', STR_PAD_LEFT);

    // ==============================
    // INSERT PO
    // ==============================
    $stmt = $db->prepare("
        INSERT INTO purchase_orders
        (po_number, pr_number, supplier_id, source, order_date, expected_delivery, branch, remarks, status,
         subtotal, vat, total_amount, created_by, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "ssissssssdddss",
        $po_number, $pr_id, $supplier_id, $source, $order_date, $expected_delivery,
        $branch, $remarks, $status, $subtotal, $vat, $total_amount, $created_by, $created_at
    );

    if (!$stmt->execute()) {
        exit($stmt->error);
    }

    $poId = $stmt->insert_id;
    $stmt->close();

    $purchController->savePurchasingLog('PO', $pr_id, 'CREATED', $_SESSION['purch_appnameuser'] ?? 'system');

    // ==============================
    // INSERT ITEMS FROM CANVASSING
    // ==============================
    $stmt = $db->prepare("
        SELECT pci.id AS pr_item_id, pci.item_code, pci.item_description, pci.quantity, pci.unit, pcs.price
        FROM purchase_canvassing_items pci
        JOIN purchase_canvassing_suppliers pcs ON pcs.canvass_item_id = pci.id
        WHERE pci.canvass_no = ? AND pcs.supplier_id = ? AND pcs.status = 1
    ");
    $stmt->bind_param("si", $canvass_no, $supplier_id);
    $stmt->execute();
    $items = $stmt->get_result();
    $stmt->close();

    $subtotal = 0;
    while ($row = $items->fetch_assoc()) {
        $lineTotal = $row['quantity'] * $row['price'];
        $subtotal += $lineTotal;

        $stmtItem = $db->prepare("
            INSERT INTO purchase_order_items
            (po_id, pr_item_id, item_code, description, qty, uom, unit_price, total_price)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmtItem->bind_param(
            "iissssdd",
            $poId, $row['pr_item_id'], $row['item_code'], $row['item_description'],
            $row['quantity'], $row['unit'], $row['price'], $lineTotal
        );
        $stmtItem->execute();
        $stmtItem->close();
    }

    // ==============================
    // UPDATE PO TOTALS
    // ==============================
    $vat = 0; // example VAT
    $total_amount = $subtotal + $vat;

    $updPO = $db->prepare("
        UPDATE purchase_orders
        SET subtotal=?, vat=?, total_amount=?, updated_at=NOW()
        WHERE id=?
    ");
    $updPO->bind_param("dddi", $subtotal, $vat, $total_amount, $poId);
    $updPO->execute();
    $updPO->close();

    // ==============================
    // UPDATE PR STATUS
    // ==============================
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT supplier_id) AS total_suppliers
        FROM purchase_canvassing_suppliers
        WHERE canvass_no = ? AND status = 1
    ");
    $stmt->bind_param("s", $canvass_no);
    $stmt->execute();
    $totalSuppliers = $stmt->get_result()->fetch_assoc()['total_suppliers'] ?? 0;
    $stmt->close();

    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT supplier_id) AS po_suppliers
        FROM purchase_orders
        WHERE pr_number = ?
    ");
    $stmt->bind_param("s", $pr_id);
    $stmt->execute();
    $poSuppliers = $stmt->get_result()->fetch_assoc()['po_suppliers'] ?? 0;
    $stmt->close();

    $prStatus = ($poSuppliers < $totalSuppliers) ? 'partial_conversion' : 'converted';
    $updPR = $db->prepare("UPDATE purchase_request SET status = ? WHERE pr_number = ?");
    $updPR->bind_param("ss", $prStatus, $pr_id);
    $updPR->execute();
    $updPR->close();

    // ==============================
    // UPDATE CANVASSING STATUS
    // ==============================
    $canvassStatus = ($poSuppliers < $totalSuppliers) ? 'PARTIAL_PO_CREATED' : 'PO_CREATED';
    $updCanvass = $db->prepare("
        UPDATE purchase_canvassing
        SET status = ?
        WHERE pr_no = ? AND status IN ('APPROVED','PARTIAL_PO_CREATED')
    ");
    $updCanvass->bind_param("ss", $canvassStatus, $pr_id);
    $updCanvass->execute();
    $updCanvass->close();

    // ==============================
    // MARK SUPPLIER AS PO CREATED
    // ==============================
    $updSupplier = $db->prepare("
        UPDATE purchase_canvassing_suppliers
        SET created_po = 1
        WHERE canvass_no = ? AND supplier_id = ?
    ");
    $updSupplier->bind_param("si", $canvass_no, $supplier_id);
    $updSupplier->execute();
    $updSupplier->close();

    echo 'PO created successfully|' . $poId;
}





if($mode === 'editsupplier'){

    $id = (int)($_POST['id'] ?? 0);
    if($id <= 0){
        echo 'Invalid supplier ID';
        exit;
    }

    $supplier_code = trim($_POST['supplier_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $address = $_POST['address'] ?? '';
    $tin = $_POST['tin'] ?? '';
    $payment_terms = $_POST['payment_terms'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $person_contact = $_POST['person_contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $status = (int)($_POST['status'] ?? 1);

    $gl_account_code     = $_POST['gl_account_code'] ?? '';
    $tax_type            = $_POST['tax_type'] ?? '';
    $payment_method      = $_POST['payment_method'] ?? '';
    $bank_name           = $_POST['bank_name'] ?? '';
    $bank_account_number = $_POST['bank_account_number'] ?? '';

    if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo 'Invalid email address';
        exit;
    }

    $updated_by   = $_SESSION['username'] ?? 'system';
    $date_updated = date('Y-m-d H:i:s');

    // duplicate supplier code
    $check = $db->prepare("SELECT id FROM suppliers WHERE supplier_code=? AND id<>?");
    $check->bind_param("si", $supplier_code, $id);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        echo 'Error: Supplier Code already exists!';
        exit;
    }

    $stmt = $db->prepare("
        UPDATE suppliers SET
            supplier_code=?,
            name=?,
            address=?,
            tin=?,
            payment_terms=?,
            contact_person=?,
            person_contact=?,
            email=?,
            status=?,
            gl_account_code=?,
            tax_type=?,
            payment_method=?,
            bank_name=?,
            bank_account_number=?,
            date_updated=?,
            updated_by=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssssisssssssi",
        $supplier_code,
        $name,
        $address,
        $tin,
        $payment_terms,
        $contact_person,
        $person_contact,
        $email,
        $status,
        $gl_account_code,
        $tax_type,
        $payment_method,
        $bank_name,
        $bank_account_number,
        $date_updated,
        $updated_by,
        $id
    );

    if($stmt->execute()){
        $purchController->savePurchasingLog(
            'SUPPLIER',
            $id,
            'UPDATED',
            $_SESSION['purch_appnameuser'] ?? 'system'
        );
        echo 'Supplier updated successfully';
    }else{
        echo 'Error: '.$stmt->error;
    }

    $stmt->close();
}



if ($mode == 'savesupplier') {
    $supplier_code = $_POST['supplier_code'] ?? '';
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $tin = $_POST['tin'] ?? '';
    $payment_terms = $_POST['payment_terms'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $person_contact = $_POST['person_contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $status = $_POST['status'] ?? 1;

    // Optional accounting fields
    $gl_account_code     = $_POST['gl_account_code'] ?? '';
    $tax_type            = $_POST['tax_type'] ?? '';
    $payment_method      = $_POST['payment_method'] ?? '';
    $bank_name           = $_POST['bank_name'] ?? '';
    $bank_account_number = $_POST['bank_account_number'] ?? '';

    $added_by = $_SESSION['username'] ?? 'system';
    $date_added = date('Y-m-d H:i:s');

    // Check duplicate supplier_code first
    $check = $db->prepare("SELECT id FROM suppliers WHERE supplier_code=?");
    $check->bind_param("s", $supplier_code);
    $check->execute();
    $check->store_result();
    if($check->num_rows > 0){
        echo 'Error: Supplier Code already exists!';
        exit();
    }

    $stmt = $db->prepare("INSERT INTO suppliers 
        (supplier_code,name,address,tin,payment_terms,contact_person,person_contact,email,status,gl_account_code,tax_type,payment_method,bank_name,bank_account_number,date_added,added_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssssssssssss",
        $supplier_code,$name,$address,$tin,$payment_terms,$contact_person,$person_contact,$email,$status,$gl_account_code,$tax_type,$payment_method,$bank_name,$bank_account_number,$date_added,$added_by);

    if($stmt->execute()){
        echo 'Supplier added successfully.';
    } else {
        echo 'Error: '.$stmt->error;
    }

    $stmt->close();
}


if ($mode === 'approvepurchaserequest') {

    $prnumber = trim($_POST['prnumber'] ?? '');
    $approver = trim($_SESSION['purch_appnameuser'] ?? '');

    if ($prnumber === '') {
        echo json_encode(['success'=>false,'message'=>'PR number is required']);
        exit;
    }

    if ($approver === '') {
        echo json_encode(['success'=>false,'message'=>'Approver not found']);
        exit;
    }

    // 1️CHECK PR
    $stmt = $db->prepare("
        SELECT id, status 
        FROM purchase_request 
        WHERE pr_number = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $prnumber);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'PR not found']);
        exit;
    }

    $pr = $res->fetch_assoc();
    $pr_id = $pr['id'];

    if ($pr['status'] !== 'pending') {
        echo json_encode([
            'success'=>false,
            'message'=>'Only PENDING PR can be approved'
        ]);
        exit;
    }

    // 2️UPDATE PR (NOTE: approved_at)
    $stmt = $db->prepare("
        UPDATE purchase_request
        SET status = 'approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("si", $approver, $pr_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Nothing updated']);
        exit;
    }

    // 3️INSERT LOG
    // action_by is INT → use user ID or 0 if wala pa
    $user_id = $_SESSION['user_id'] ?? 0;

    $stmt = $db->prepare("
        INSERT INTO purchasing_logs
            (reference_type, reference_id, action, action_by, action_date)
        VALUES
            ('PR', ?, 'APPROVED', ?, NOW())
    ");
    $stmt->bind_param("is", $pr_id, $approver);
    $stmt->execute();

    echo json_encode([
        'success'=>true,
        'message'=>'Purchase Request approved successfully'
    ]);
    exit;
}



if ($mode === 'submitprform') {


    $items = $_POST['items'] ?? [];
    $grandTotal = floatval($_POST['grandTotal'] ?? 0);
    $remarks = $_POST['remarks'] ?? '';
    $date_time = date("Y-m-d H:i:s"); 
    $user = $_SESSION['purch_appnameuser'] ?? '';

    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items to submit.']);
        exit;
    }

    try {
        
        
		$db->begin_transaction();

		$pr_number  = $function->generateUniquePrNumber($db);
		
		$source = 'PROPERTY CUSTODIAN';
		$department = 'SUPPLY CHAIN';
		
		$stmt = $db->prepare("
		    INSERT INTO purchase_request 
		    (pr_number, request_date, source, department,requested_by, remarks, created_at)
		    VALUES (?, ?, ?, ?, ?, ?, ?)
		");
		$stmt->bind_param(
		    "sssssss", 
		    $pr_number,  
		    $date_time,
		    $source,
		    $department,
		    $user,
		    $remarks,
		    $date_time
		);
		
		if (!$stmt->execute()) {
		    throw new Exception($stmt->error);
		}
		
		$pr_id = $db->insert_id;
        
        

        // Prepare insert for items
        $stmt2 = $db->prepare("
            INSERT INTO purchase_request_items
            (pr_id, item_type, item_code, item_description, quantity, unit, estimated_cost, total_estimated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($items as $item) {

            $item_id = intval($item['item_id']);
            $item_type = $item['item_type'];
            $qty     = floatval($item['qty']);
            $cost    = floatval($item['cost']);
            $total   = floatval($item['total']);

            // Fetch trusted item data
            $res = $db->prepare("
                SELECT item_code, item_description, uom 
                FROM wms_itemlist 
                WHERE id = ? 
                LIMIT 1
            ");
            $res->bind_param("i", $item_id);
            $res->execute();
            $res->bind_result($item_code, $item_description, $unit);

            if (!$res->fetch()) {
                throw new Exception("Item ID {$item_id} not found.");
            }
            $res->close();
            
            
            $stmt2->bind_param(
			    "isssdsdd",  // 8 types: i=pr_id, s=item_type, s=item_code, d=qty, s=unit, d=cost, d=total
			    $pr_id, // i
			    $item_type,   // s
			    $item_code,	//s
			    $item_description, //s
			    $qty,	//d
			    $unit,	//s
			    $cost,	//d
			    $total	//d
			);

            
            
            if (!$stmt2->execute()) {
                throw new Exception("Failed to insert item: " . $stmt2->error);
            }
        }

        $db->commit();
        echo json_encode([
            'success'   => true,
            'pr_number' => $pr_number
        ]);

    } catch (Exception $e) {
        $db->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    exit;
}


if($mode == 'voidrequestform')
{
	$ptf_number = $_POST['ptf_number'];
	$queryDataUpdate = "UPDATE pcs_asset_request SET status='Void' WHERE status='Open' AND ptf_number='$ptf_number'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "VOID REQUEST::: Request has been Void ".$app_user." with PTF Number ".$ptf_number;
		echo $function->DoLogs($date_time,$activity,$app_user,$db);
		echo '
			<script>
				swal("Success", "Item has been successfuly re-open", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		';
	} else { 
		echo $db->error;
	}
}
if($mode == 'reopenclosedrequest')
{
	$ptf_number = $_POST['ptf_number'];
	function approveRequest($function,$date_time,$ptf_number,$app_user,$date,$db)
	{
		$queryDataUpdate = "UPDATE pcs_asset_request SET status='Open', approved_by=NULL, date_approved=NULL WHERE ptf_number='$ptf_number'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			$activity = "RE-OPENING REQUEST::: Request Re-Open by ".$app_user." with PTF Number ".$ptf_number;
			echo $function->DoLogs($date_time,$activity,$app_user,$db);
			echo '
				<script>
					swal("Success", "Request has been successfuly re-open", "success");
					load_createforms("'.$ptf_number.'");
					load_ptfRecordsForm("'.$ptf_number.'");
				</script>
			';
		} else { 
			echo $db->error;
		}
	}
	
	$query = "SELECT * FROM pcs_asset_holder_records WHERE ptf_number='$ptf_number'";
	$results = mysqli_query($db, $query); 
	$cnt = $results->num_rows;   
	if ( $results->num_rows > 0 ) 
	{
		$nt=0;
	    while($ROW = mysqli_fetch_array($results))  
		{
			$nt++;
			$tag_number = $ROW['tag_number'];
			$asset_holder = NULL;
			$ptf_number = $ROW['ptf_number'];
		
			echo $function->TakeFinalizedToItemRecords($tag_number,$db);			
			if($nt == $cnt)
			{
				echo $function->LockedItemRecords($ptf_number,$db);
				approveRequest($function,$date_time,$ptf_number,$app_user,$date,$db);
			}			
		}
	} else {
		echo 'Something is wrong';
	}
}
if($mode == 'reopenprocess')
{

	$ptf_number = $_POST['ptf_number'];
	$queryDataUpdate = "UPDATE pcs_asset_request SET status='Open' WHERE ptf_number='$ptf_number'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "RE-OPENING REQUEST::: Request Re-Open by ".$app_user." with PTF Number ".$ptf_number;
		echo $function->DoLogs($date_time,$activity,$app_user,$db);
		echo '
			<script>
				swal("Success", "Item has been successfuly re-open", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		';
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}		
		
if($mode == 'voidunit')
{
	$rowid = $_POST['rowid'];
	$item_code = $_POST['item_code'];
	$queryDataUpdate = "UPDATE pcs_item_records SET flag=1 WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "APPROVAL REQUEST::: Request has been Approved by ".$app_user." with PTF Number ".$ptf_number;
		echo $function->DoLogs($date_time,$activity,$app_user,$db);
		
		echo '
			<script>
				swal("Success", "Item has been successfuly Void", "success");
				loadDetailsData("'.$item_code.'");
			</script>
		';
	} else { 
		echo $db->error;
	}
}
if($mode == 'transferunit')
{
	$point_of_origin = $_POST['transfer_from'];
	$item_code = $_POST['item_code'];
	$tag_number = $_POST['tag_number'];
	$idcode = $_POST['asset_holder'];
	$ptf_number = $_POST['ptf_number'];
	$recipient = $_POST['recipient'];
	if($recipient == 'Individual')
	{
		$asset_holder = $function->GetEmployeeName($idcode,$db);		
		$column = "`point_of_origin`,`recipient`,`asset_holder`,`ptf_number`,`generated_by`,`generated_date`,`idcode`";    
		$insert = "'$point_of_origin','$recipient','$asset_holder','$ptf_number','$app_user','$date','$idcode'";
	} else {
		$asset_holder = $idcode;
		$column = "`point_of_origin`,`recipient`,`asset_holder`,`ptf_number`,`generated_by`,`generated_date`";    
		$insert = "'$point_of_origin','$recipient','$asset_holder','$ptf_number','$app_user','$date'";
	}
	
	$ptfnumber  = $ptf_number;	
	list($year, $PtfNumericPart) = explode('-', $ptfnumber);
	$PtfNumericPart = str_pad((int)$PtfNumericPart + 1, strlen($PtfNumericPart), '0', STR_PAD_LEFT);		
	$new_ptf_number = $year . '-' . $PtfNumericPart;
		
	$queryInsert = "INSERT INTO pcs_asset_request ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
	
		/* -------------------- UPDATING OLD RECORDS -------------------- */
	//	$queryDataUpdate = "UPDATE pcs_asset_holder_records SET status='Transfered',remarks= WHERE ='$rowid'";
	//	if ($db->query($queryDataUpdate) === TRUE){} else {}
		/* -------------------- PARTE KUNG SAAN MA ADD SYA SA PUTANG INA -------------------- */
		$item_code = $_POST['item_code'];
		$uom = $_POST['uom'];
		$serial_number = $_POST['serial_number'];
		$tag_number = $_POST['tag_number'];
		$unit_description = $_POST['unit_description'];
		$quantity = $_POST['quantity'];
		$remarks = "Transfered from ".$asset_holder;
		$date_created = $date_time;
	
		$column = "`asset_holder`,`ptf_number`,`item_code`,`unit_description`,`uom`,`quantity`,`remarks`,`date_created`,`tag_number`,`serial_number`";	
		$insert = "'$asset_holder','$ptf_number','$item_code','$unit_description','$uom','$quantity','$remarks','$date_created','$tag_number','$serial_number'";
		$queryInsert = "INSERT INTO pcs_asset_holder_records ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE)
		{
		} else {
			print_r('
				<script>
					swal("System Message", "'.$db->error.'", "warning");
				</script>
			');
		}
		/* -------------------- PARTE KUNG SAAN MA ADD SYA SA PUTANG INA -------------------- */	
		$function->updateNumbering('ptf_number',$new_ptf_number,$db);
		$activity = "MR RECORDS::: New MR Transfer Request bas been Generated From ".$point_of_origin. " to ".$asset_holder;
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
		print_r('
			<script>
				swal("Successful", "New Property Transfer Form has been created", "success");
				load_data();
			</script>
		');
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}

if($mode == 'getunitdescription')
{
	if(isset($_POST['search']) && $_POST['search'] != '')
	{
		$search = $_POST['search'];
		$q = "WHERE assigned=0 AND  assigned=0 AND unit_description LIKE '%$search%'";
	} else {
		$q = "WHERE assigned=0 LIMIT 50";
	}
	$query = "SELECT * FROM pcs_item_records $q";
	$results = mysqli_query($db, $query);    
	echo '<ul class="droplistings table-hover">';
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results))  
		{
			$tag_number = $ROW['tag_number'];
			$item_code = $ROW['item_code'];
			$serial_number = $ROW['serial_number'];
			
			$unit_description = $ROW['unit_description']." (".$ROW['brand_name'].") - ".$ROW['tag_number'];
			echo '<li onclick="showInfo(\'' . $tag_number . '\',\'' . $unit_description . '\',\'' . $item_code . '\',\'' . $serial_number . '\')">' . $unit_description . '</li>';
		}
	} else {
		echo '<li>Item not found.</li>';
	}
	echo '</ul>';
}
if($mode == 'approvedmrrequest')
{
	$ptf_number = $_POST['ptf_number'];
	function approveRequest($function,$date_time,$ptf_number,$app_user,$date,$db)
	{
		$queryDataUpdate = "UPDATE pcs_asset_request SET status='Closed', approved_by='$app_user', date_approved='$date' WHERE ptf_number='$ptf_number'";
		if ($db->query($queryDataUpdate) === TRUE)
		{
			$activity = "APPROVAL REQUEST::: Request has been Approved by ".$app_user." with PTF Number ".$ptf_number;
			echo $function->DoLogs($date_time,$activity,$app_user,$db);
			echo '
				<script>
					swal("Success", "Item has been successfuly Approved", "success");
					load_data();
				</script>
			';
		} else { 
			echo $db->error;
		}
	}
	$query = "SELECT * FROM pcs_asset_holder_records WHERE ptf_number='$ptf_number'";
	$results = mysqli_query($db, $query); 
	$cnt = $results->num_rows;   
	if ( $results->num_rows > 0 ) 
	{
		$nt=0;
	    while($ROW = mysqli_fetch_array($results))  
		{
			$nt++;
			$tag_number = $ROW['tag_number'];
			$asset_holder = $ROW['asset_holder'];
			$ptf_number = $ROW['ptf_number'];
		
			
			echo $function->FinalizedToItemRecords($tag_number,$ptf_number,$asset_holder,$db);			

			if($nt == $cnt)
			{
				echo $function->LockedItemRecords($ptf_number,$db);
				approveRequest($function,$date_time,$ptf_number,$app_user,$date,$db);
			}			
		}
	} else {
		echo '<li>Employee not found.</li>';
	}
}
if($mode == 'employeelistings')
{
	$db->set_charset("utf8mb4");
	$search = $_POST['search'];
	$query = "SELECT * FROM tbl_employees WHERE acctname LIKE '%$search%' OR firstname LIKE '%$search%' OR lastname LIKE '%$search%' LIMIT 50";
	$results = mysqli_query($db, $query);    
	echo '<ul class="droplistings table-hover">';
	if ( $results->num_rows > 0 ) 
	{
	    while($ROW = mysqli_fetch_array($results))  
		{
			$idcode = $ROW['idcode'];
			$asset_holder = $ROW['firstname']." ".$ROW['lastname'];
			echo '<li onclick="showInfo(\'' . $idcode . '\',\'' . $asset_holder . '\')">' . $asset_holder . '</li>';
		}
	} else {
		echo '<li>Employee not found.</li>';
	}
	echo '</ul>';
}
if($mode == 'finalizerequest')
{
	$ptf_number = $_POST['ptf_number'];

	$queryDataUpdate = "UPDATE pcs_asset_request SET status='Submitted' WHERE ptf_number='$ptf_number'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "FINALIZED REQUEST::: Request has been Finalized by ".$app_user." with PTF Number ".$ptf_number;
		echo $function->DoLogs($date_time,$activity,$app_user,$db);
		echo '
			<script>
				swal("Success", "Item has been successfuly Sumitted", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		';
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'updaterequestform')
{
	$ptf_number = $_POST['ptf_number'];
	$prepared_by = $_POST['prepared_by'];
	$point_of_origin = $_POST['point_of_origin'];
	$delivery_date = $_POST['delivery_date'];	
	$queryDataUpdate = "UPDATE pcs_asset_request SET prepared_by='$prepared_by',point_of_origin='$point_of_origin',delivery_date='$delivery_date',date_prepared='$date' WHERE ptf_number='$ptf_number'";
	if ($db->query($queryDataUpdate) === TRUE) {} else { echo $db->error;}
}
if($mode == 'deletemrentry')
{

	$rowid = $_POST['rowid'];
	$ptf_number = $_POST['ptf_number'];
	$item_description = $function->getItemName($rowid,$db);
	$sql = "DELETE FROM pcs_asset_holder_records WHERE id='$rowid'";
	if ($db->query($sql) === TRUE)
	{
		$activity = "PTF DATA ENTRY::: has been removed.";
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
    	echo '
			<script>
				swal("Success", "Item has been successfuly deleted", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		';
	} else {
	    print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'updatetoptfdata')
{
	$rowid = $_POST['rowid'];
	$item_code = $_POST['item_code'];
	$uom = $_POST['uom'];
	$serial_number = $_POST['serial_number'];
	$tag_number = $_POST['tag_number'];
	$unit_description = $_POST['unit_description'];
	$quantity = $_POST['quantity'];
	$remarks = $_POST['remarks'];
	$ptf_number = $_POST['ptf_number'];
	$date_created = $date_time;
	
	$update = "
		`item_code`='$item_code',`uom`='$uom',`serial_number`='$serial_number',`unit_description`='$unit_description',`quantity`='$quantity',`remarks`='$remarks',`updated_by`='$app_user',`date_updated`='$date_time'
	";
	
	$queryDataUpdate = "UPDATE pcs_asset_holder_records SET $update WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		echo '
			<script>
				swal("Successful", "'.$unit_description.' has been successfuly updated.", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		';
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'savetoptfdata')
{
	$db->set_charset("utf8mb4");
	$item_code = $_POST['item_code'];
	$asset_holder = $_POST['asset_holder'];
	$uom = $_POST['uom'];
	$serial_number = $_POST['serial_number'];
	$tag_number = $_POST['tag_number'];
	$unit_description = $_POST['unit_description'];
	$quantity = $_POST['quantity'];
	$remarks = $_POST['remarks'];
	$ptf_number = $_POST['ptf_number'];
	$date_created = $date_time;

	$column = "`asset_holder`,`ptf_number`,`item_code`,`unit_description`,`uom`,`quantity`,`remarks`,`date_created`,`tag_number`,`serial_number`";	
	$insert = "'$asset_holder','$ptf_number','$item_code','$unit_description','$uom','$quantity','$remarks','$date_created','$tag_number','$serial_number'";
	$queryInsert = "INSERT INTO pcs_asset_holder_records ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
	//	$function->assignedItem($tag_number,$asset_holder,$db);
		print_r('
			<script>
				swal("Successful", "New Property Transfer Form has been created", "success");
				load_createforms("'.$ptf_number.'");
				load_ptfRecordsForm("'.$ptf_number.'");
			</script>
		');
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}		
}

if($mode == 'gettagnumber')
{
	$tag_number = $_POST['tag_number'];
	$query = "SELECT * FROM pcs_item_records WHERE tag_number='$tag_number'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{				
				$item_code = $ROW['item_code'];
				$unit_description = $ROW['unit_description'];
				$serial_number = $ROW['serial_number'];
			}
			echo '
				<script>
					$("#item_code").val("'.$item_code.'");
					$("#unit_description").val("'.$unit_description.'");
					$("#serial_number").val("'.$serial_number.'");
				</script>
			';
		} else {
		} 
}
if($mode == 'updategeneratepftnumber')
{
	$db->set_charset("utf8mb4");
	$ptf_number = $_POST['ptf_number'];
	$asset_holder = $_POST['asset_holder'];
	$recipient = $_POST['recipient'];
	$idcode = $_POST['idcode'];

	$queryDataUpdate = "UPDATE pcs_asset_request SET idcode='$idcode', asset_holder='$asset_holder', recipient='$recipient' WHERE ptf_number='$ptf_number'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$activity = "MR RECORDS::: PTF bas been Updated for ". $asset_holder;
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
		$function->updateNumbering('ptf_number',$new_ptf_number,$db);
		print_r('
			<script>
				swal("Successful", "New Property Transfer Form has been created", "success");
				$("#formmodal").hide();
				load_data();
			</script>
		');
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'generatepftnumber')
{
	$db->set_charset("utf8mb4");
	$ptf_number = $function->getNumbering('ptf_number',$db);
	$asset_holder = $_POST['asset_holder'];
	$recipient = $_POST['recipient'];
	$idcode = $_POST['idcode'];
	
	if($function->checkPFTNumber($ptf_number,$db) == 1)
	{
		echo '
			<script>
				swal("System Message","The PTF Number already exists","warning");
				return false;
			</script>
		';
		
	}	
	
	$ptfnumber  = $ptf_number;
	list($year, $PtfNumericPart) = explode('-', $ptfnumber);
	$PtfNumericPart = str_pad((int)$PtfNumericPart + 1, strlen($PtfNumericPart), '0', STR_PAD_LEFT);		
	$new_ptf_number = $year . '-' . $PtfNumericPart;
		
	$column = "`recipient`,`asset_holder`,`ptf_number`,`generated_by`,`generated_date`,`idcode`";	
	$insert = "'$recipient','$asset_holder','$ptf_number','$app_user','$date','$idcode'";
	$queryInsert = "INSERT INTO pcs_asset_request ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		$function->updateNumbering('ptf_number',$new_ptf_number,$db);
		$activity = "MR RECORDS::: PTF bas been Generated for ". $asset_holder;
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
		print_r('
			<script>
				swal("Successful", "New Property Transfer Form has been created", "success");
				load_data();
			</script>
		');
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'deletingitem')
{
	$rowid = $_POST['rowid'];
	$item_description = $function->getItemName($rowid,$db);
	$sql = "DELETE FROM pcs_itemlist WHERE id='$rowid'";
	if ($db->query($sql) === TRUE)
	{
		$activity = "ITEMLIST::: ". $item_description ." has been Deleted.";
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
    	echo '
			<script>
				load_data();
				$("#formmodal").hide();
				swal("Success", "Item has been successfuly deleted", "success");
			</script>
		';
	} else {
	    print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
if($mode == 'updateitems')
{
	$rowid = $_POST['rowid'];
	$item_code = $_POST['item_code'];
	$item_description = strtoupper($_POST['item_description']);
	$category = $_POST['category']; 
	$queryDataUpdate = "UPDATE pcs_itemlist SET item_category_name='$item_description',category='$category', updated_by='$app_user', date_updated='$date_time' WHERE id='$rowid'";
	if ($db->query($queryDataUpdate) === TRUE)
	{
		$queryPTFDataUpdate = "UPDATE pcs_asset_holder_records SET unit_description='$item_description' WHERE item_code='$item_code'";
		if ($db->query($queryPTFDataUpdate) === TRUE)
		{	
			echo $function->UpdateItemRecords($item_code,$item_description,$db);
			$activity = "ITEMLIST::: ". $item_description ." has been Updated.";
			$logger = $app_user;
			echo $function->DoLogs($date_time,$activity,$logger,$db);
			print_r('
				<script>
					var item = "[[ '.$item_description.' ]] has been succesfully updateds";
					swal("Successful", "The item " + item, "success");
					load_data();
				</script>
			');
		} else {
			print_r('
				<script>
					swal("System Message", "'.$db->error.'", "warning");				
				</script>
			');
		}
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");				
			</script>
		');
	}
	
}
if($mode == 'saveitems')
{
	$item_code = $_POST['item_code'];
	$item_description = strtoupper($_POST['item_description']);
	$category = $_POST['category']; 	
	$number  = intval($item_code);
	$number += 1;
	$new_itemcode = str_pad($number, strlen($item_code), '0', STR_PAD_LEFT);
	
	$column = "`item_code`,`category`,`item_category_name`,`added_by`,`date_added`";	
	$insert = "'$item_code','$category','$item_description','$app_user','$date_time'";
	$queryInsert = "INSERT INTO pcs_itemlist ($column) VALUES ($insert)";
	if ($db->query($queryInsert) === TRUE)
	{
		$function->updateNumbering('item_code',$new_itemcode,$db);
		$activity = "ITEMLIST::: ". $item_description ." has been Added.";
		$logger = $app_user;
		echo $function->DoLogs($date_time,$activity,$logger,$db);
		print_r('
			<script>
				var item = "'.$item_description.' has been added";
				swal("Successful", "New item " + item, "success");
				$("#formmodal").hide();
				load_data();
			</script>
		');
	} else {
		print_r('
			<script>
				swal("System Message", "'.$db->error.'", "warning");
			</script>
		');
	}
}
?>