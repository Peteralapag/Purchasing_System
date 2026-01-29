<?php
class WMSFunctions
{

	public function generateUniquePrNumber($db)
	{
	    $datePrefix = date('Ymd');
	    $source = 'PC';
	
	    try {
	        $db->begin_transaction();
	
	        $sql = "SELECT pr_number
	                FROM purchase_request
	                WHERE pr_number LIKE ?
	                ORDER BY pr_number DESC
	                LIMIT 1
	                FOR UPDATE";
	
	        $like = "$source-$datePrefix-%";
	        $stmt = $db->prepare($sql);
	        $stmt->bind_param("s", $like);
	        $stmt->execute();
	        $stmt->bind_result($lastPr);
	        $stmt->fetch();
	        $stmt->close();
	
	        $lastNumber = $lastPr
	            ? (int) substr($lastPr, -4)
	            : 0;
	
	        $newNumber = $lastNumber + 1;
	
	        $newPr = "$source-$datePrefix-" . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
	
	        $db->commit();
	        return $newPr;
	
	    } catch (Exception $e) {
	        $db->rollback();
	        throw $e;
	    }
	}


	public function GetActiveVoid($stats)
	{
		$status = array(            
            "Submitted" => "Submitted",
            "Open" => "Open",
            "Closed" => "Closed",
            "Void" => "Void",
            "Show All" => "Show All"
        );
        $return = '';
        foreach ( $status as $key => $value )
        {
        	$selected = "";
        	if($value == $stats)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;

	}
	public function LockedItemRecords($ptf_number,$db)
	{
		$queryDataUpdate = "UPDATE pcs_asset_holder_records SET status='Locked' WHERE ptf_number='$ptf_number'";
	    if ($db->query($queryDataUpdate) === TRUE)
	    {} else { return $db->error; }
	}
	public function TakeFinalizedToItemRecords($tag_number,$db)
	{
	    $queryDataUpdate = "UPDATE pcs_item_records SET ptf_number=NULL,asset_holder=NULL,assigned=0 WHERE tag_number='$tag_number'";
	    if ($db->query($queryDataUpdate) === TRUE)
	    {} else { return $db->error; }
	}
	public function FinalizedToItemRecords($tag_number,$ptf_number,$asset_holder,$db)
	{
	    $queryDataUpdate = "UPDATE pcs_item_records SET ptf_number='$ptf_number',asset_holder='$asset_holder',assigned=1 WHERE tag_number='$tag_number'";
	    if ($db->query($queryDataUpdate) === TRUE)
	    {} else { return $db->error; }
	}
	public function assignedItem($tag_number,$asset_holder,$db)
	{
	    $queryDataUpdate = "UPDATE pcs_item_records SET assigned=1,asset_holder='$asset_holder' WHERE tag_number='$tag_number'";
	    if ($db->query($queryDataUpdate) === TRUE) {		        
	    } else { return $db->error; }
		mysqli_close($db);
	}
	public function GetCheckRecordsData($item_code,$tag_number,$db)
	{
		$query = "SELECT * FROM pcs_item_records WHERE item_code='$item_code' AND tag_number='$tag_number'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			return 1;
		} else {
			return 0;
		}
		mysqli_close($db);
	}
	public function GetRecordsData($column,$ptf_number,$db)
	{
		$query = "SELECT * FROM pcs_asset_request WHERE ptf_number='$ptf_number'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$return = $ROW[$column];
			}
			return $return;
		} else {
			return null;
		}
		mysqli_close($db);
	}
	public function UpdateDeliveryDate($delivery_date,$ptf_number,$db)
	{
		$queryDataUpdate = "UPDATE pcs_asset_request SET delivery_date='$delivery_date' WHERE ptf_number='$ptf_number'";
		if ($db->query($queryDataUpdate) === TRUE)
		{} else { return $db->error;}
	}
	public function GetUOM($uom,$db)
	{
		$query = "SELECT * FROM pcs_units_measures";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT UOM ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$units = $ROW['unit_name'];
				$selected = '';
				if($units == $uom)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$units.'">'.$units.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO UOM ---</option>';
		}
		mysqli_close($db);
	}
	public function GetItem_Code($item_description,$db)
	{
		$query = "SELECT item_code FROM pcs_itemlist WHERE item_description=?";
		$stmt = $db->prepare($query);
		$stmt->bind_param('s', $item_description);
		$stmt->execute();
		$stmt->bind_result($itemcode);

		if ($stmt->fetch())
		{
		    $stmt->close();
		    $db->close();
		    return $itemcode;
		} else {
		    $stmt->close();
		    $db->close();
		    return null;
		}		
		$db->close();
	}
	public function GetItemListForForm($items,$db)
	{
		$query = "SELECT * FROM pcs_item_records WHERE assigned=0";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$item_description = utf8_encode($ROW['unit_description']);
				$tag_number = $ROW['tag_number'];
				$selected = '';
				if($items == $item_description)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$tag_number.'">'.$item_description.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO ITEMS ---</option>';
		} 
		mysqli_close($db);
	}
	public function GetProcessFormData($ptf_number,$column,$db)
	{
		$query = "SELECT * FROM pcs_asset_request WHERE ptf_number='$ptf_number'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				$return = $ROW[$column];
			}
			return $return;
		} else {
			return '---';
		} 
		mysqli_close($db);
	}
	public function checkPFTNumber($pftnumber,$db)
	{
		$query = "SELECT * FROM pcs_asset_request WHERE ptf_number='$pftnumber'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			return 1;
		} else {
			return 0;
		}
	}
	public function GetSupplierName($supplier_name,$db)
	{
		$query = "SELECT * FROM wms_supplier";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$supplier = utf8_encode($ROW['name']);
				$supplier = strtoupper($supplier);
				$selected = '';
				if($supplier == $supplier_name)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$supplier.'"></option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO RECORDS ---</option>';
		} 
		mysqli_close($db);
	}
	public function DoLogs($date,$activity,$logger,$db)
	{
		$column = "`date_time`,`activity`,`log_by`";	
		$insert = "'$date','$activity','$logger'";
		
		$queryInsert = "INSERT INTO pcs_activity_logs ($column) VALUES ($insert)";
		if ($db->query($queryInsert) === TRUE){} else { return $db->error;}
		mysqli_close($db);
	}	
	public function GetItemCodeCounts($itemcode,$db)
	{
		$query = "SELECT COUNT(quantity) as quantity FROM pcs_asset_holder_records WHERE item_code=?";
		$stmt = $db->prepare($query);
		$stmt->bind_param('s', $itemcode);
		$stmt->execute();
		$stmt->bind_result($quantity);
		$stmt->fetch();
		$stmt->close();		
		return $quantity;
		$db->close();
	}
	public function GetItemCountsOpen($ptf_number,$db)
	{
		$sqlQuery = "SELECT *, COUNT(quantity) as quantity FROM pcs_asset_holder_records  WHERE ptf_number='$ptf_number' AND status!='Void'";
		$results = mysqli_query($db, $sqlQuery);    
	    if ( $results->num_rows > 0 ) 
	    {
	    	while($ROWS = mysqli_fetch_array($results))  
			{
				return $ROWS['quantity'];
			}
		} else {
			return 0;
		}
		$db->close();
	}
	public function GetItemCounts($recipient,$db)
	{
	
		$sqlQuery = "SELECT COUNT(quantity) as quantity FROM pcs_asset_holder_records WHERE asset_holder='$recipient' AND status='Locked'";
//		return $sqlQuery;
		$results = $db->query($sqlQuery);
		if ( $results->num_rows > 0 ) 
	    {
	    	while($ROWS = mysqli_fetch_array($results))  
			{
				return $ROWS['quantity'];
			}
		} else {
			return 0;
		}
		$db->close();
	}	
	public function GetRecipient($recipient,$db)
	{
		$depLenght = array(            
            "Branch" => "Branch",
            "Department" => "Department",
            "Individual" => "Individual"
        );
        $return = '';
        foreach ( $depLenght as $key => $value )
        {
        	$selected = "";
        	if($value == $recipient)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function UpdateItemRecords($item_code,$item_description,$db)
	{
		$sqlQuery = "SELECT item_code FROM pcs_item_records WHERE item_code='$item_code'";
		$results = mysqli_query($db, $sqlQuery);		
		if (!$results) {
		    return mysqli_error($db);
		}		
		if ($results->num_rows > 0) {
		    $queryDataUpdate = "UPDATE pcs_item_records SET unit_description='$item_description' WHERE item_code='$item_code'";
		    if ($db->query($queryDataUpdate) === TRUE) {		        
		    } else { return $db->error; }
		} else {		    
		}
		$results->close();	
	}
	public function GetItemQuantity($item_code,$db)
	{
		$query = "SELECT COUNT(*) as quantity FROM pcs_item_records WHERE item_code = ?";
		$stmt = $db->prepare($query);
		$stmt->bind_param('s', $item_code);
		$stmt->execute();
		$stmt->bind_result($quantity);
		$stmt->fetch();
		$stmt->close();		
		return $quantity;
		$db->close();
	}
	public function GetDepreciationLenght($lenght,$db)
	{
		$depLenght = array(            
            "1 Year" => "1",
            "2 Years" => "2",
            "3 Years" => "3",
            "4 Years" => "4",
            "5 Years" => "5",
            "6 Years" => "6",
            "7 Years" => "7",
            "8 Years" => "8",
            "9 Years" => "9",
            "10 Years" => "10"
        );
        $return = '';
        foreach ( $depLenght as $key => $value )
        {
        	$selected = "";
        	if($value == $lenght)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	function LimitString($inputString, $maxLength) {
    	if (strlen($inputString) > $maxLength) {
    	    return substr($inputString, 0, $maxLength)."...";
    	} else {
    	    return $inputString;
    	}
	}
	public function GetPreparedBy($prepared_by,$db)
	{
		$query = "SELECT * FROM tbl_employees";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$preparator = utf8_encode($ROW['firstname']." ".$ROW['lastname']);
				$selected = '';
				if($preparator == $$prepared_by)
				{
					$selected = "selected";
				}
				$return .= '<option '.$preparator.' value="'.$preparator.'"></option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO CATEGORY ---</option>';
		} 
		mysqli_close($db);
	}
	public function GetEmployeeName($idcode,$db)
	{
		$query = "SELECT * FROM tbl_employees WHERE idcode='$idcode'";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['firstname']." ".$ROW['lastname'];
			}
			return $return;
		} else {
			
		} 
		mysqli_close($db);
	}
	public function GetEmployeeIDCODE($employees,$db)
	{
		$employees = utf8_encode($employees);
		$query = "SELECT * FROM tbl_employees WHERE acctname='$employees'";
//		return $query;
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['idcode'];
			}
		} else {
		
		} 
		mysqli_close($db);
	}
	public function GetEmployee($employees,$db)
	{
		$query = "SELECT * FROM tbl_employees";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$employee = utf8_encode($ROW['firstname']." ".$ROW['lastname']);
				$idcode = $ROW['idcode'];
				$selected = '';
				if($employee == $employees)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$idcode.'">'.$employee.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO EMPLOYEE ---</option>';
		} 
		mysqli_close($db);
	}
	public function GetDepartment($depertment,$db)
	{
		$query = "SELECT * FROM tbl_department";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$department = $ROW['department'];
				$selected = '';
				if($department == $depertment)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$department.'">'.$department.'</option>';
			}
			return $return;
		}
		mysqli_close($db);
	}
	public function GetBranch($brench,$db)
	{
		$query = "SELECT * FROM tbl_branch";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$branch = $ROW['branch'];
				$selected = '';
				if($branch == $brench)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$branch.'">'.$branch.'</option>';
			}
			return $return;
		}
		mysqli_close($db);
	}
	public function GetItemStatus($status)
	{
        $limit = array(            
            "Active" => "Active",
            "For Repair" => "For Repair",
            "Reserve" => "Reserve",
            "Condemed" => "Condemed",
            "Void" => "Void",
        );
        $return = '';
        foreach ( $limit as $key => $value )
        {
        	$selected = "";
        	if($value == $status)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function updateNumbering($column,$newvalue,$db)
	{
		$queryDataUpdate = "UPDATE pcs_form_numbering SET $column='$newvalue' WHERE id=1";
		if ($db->query($queryDataUpdate) === TRUE)
		{
		} else {
			return $db->error;
		}
	}
	public function getNumbering($column,$db)
	{
		$query = "SELECT * FROM pcs_form_numbering WHERE id=1";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW[$column];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function getItemName($rowid,$db)
	{
		$query = "SELECT * FROM pcs_itemlist WHERE id='$rowid'";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['item_description'];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function getItemCode($db)
	{
		$query = "SELECT * FROM pcs_form_numbering WHERE id=1";
		$results = $db->query($query);			
	    if($results->num_rows > 0)
	    {
		    while($ROW = mysqli_fetch_array($results))  
			{
				return $ROW['item_code'];
			}
	    } else {
	    	return 0;
	    }
	    mysqli_close($db);
	}
	public function GetItemCategory($cat,$db)
	{
		$query = "SELECT * FROM pcs_item_category WHERE active=1";
		$results = mysqli_query($db, $query);    
		if ( $results->num_rows > 0 ) 
		{
			$return = '<option value="">--- SELECT CATEGORY ---</option>';
		    while($ROW = mysqli_fetch_array($results))  
			{
				$category = $ROW['category'];
				$selected = '';
				if($category == $cat)
				{
					$selected = "selected";
				}
				$return .= '<option '.$selected.' value="'.$category.'">'.$category.'</option>';
			}
			return $return;
		} else {
			return '<option value="">--- NO CATEGORY ---</option>';
		} 
		mysqli_close($db);
	}
	public function GetRowLimit($showlimit)
	{
        $limit = array(            
            "50" => "50",            
            "100" => "100",
			"250" => "250",
			"500" => "500",
			"1000" => "1000",
			"2000" => "2000"
        );
        foreach ( $limit as $key => $value )
        {
        	$selected = "";
        	if($value == $showlimit)
        	{
        		$selected = "selected";
        	}
            $return .= '<option '.$selected.' value="'.$value.'">'.$key.'</option>';                        
        }
        return $return;
	}
	public function checkPolicy($username,$module,$permission,$user_level,$db)
	{
		if($user_level >= 80)
		{
			return 1;
		} 
		else
		{
			$checkPolicy = "SELECT * FROM tbl_system_permission WHERE username='$username' AND modules='$module' AND $permission=1";
			$pRes = mysqli_query($db, $checkPolicy);    
		    if ( $pRes->num_rows > 0 ) 
		    {
		    	return 1;
			} else {
				return 0;
			}
		}
	}
}