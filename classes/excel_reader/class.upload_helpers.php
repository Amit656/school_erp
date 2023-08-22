<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class UploadHelpers
{
	static function SaveStockPurchaseFromExcel($ExcelData)
    {		
        try
		{
			$DBConnObject = new DBConnect();
			
			$DBConnObject->BeginTransaction();
			
			foreach ($ExcelData as $RowData)
			{
				# GET PRODUCT_UNIT_ID
				
				$ProductUnitID = 0;
				
				$RSProductUnit = $DBConnObject->Prepare('SELECT productUnitID FROM aim_master_product_units WHERE productUnitName = :|1 LIMIT 1;');
				$RSProductUnit->Execute($RowData['ProductUnitName']);
				
				if ($RSProductUnit->Result->num_rows > 0)
				{
					$ProductUnitID = $RSProductUnit->FetchRow()->productUnitID;
				}
				else
				{
					$RSSaveProductUnit = $DBConnObject->Prepare('INSERT INTO aim_master_product_units (productUnitName, isActive, createUserID, createDate) 
																VALUES (:|1, 1, 1000005, NOW());');
																
					$RSSaveProductUnit->Execute($RowData['ProductUnitName']);
					
					$ProductUnitID = $RSSaveProductUnit->LastID;
				}
				# GET PRODUCT_ID
				
				$ProductID = 0;
				
				$RSSearchProduct = $DBConnObject->Prepare('SELECT productID FROM aim_products WHERE productName = :|1 LIMIT 1;');
				$RSSearchProduct->Execute($RowData['Particulars']);
				
				if ($RSSearchProduct->Result->num_rows > 0)
				{
					$ProductID = $RSSearchProduct->FetchRow()->productID;
				}
				else
				{
					$ProductUnitValue = $RowData['Amount'] / $RowData['Quantity'];
					$RSSaveProduct = $DBConnObject->Prepare('INSERT INTO aim_products (productCategoryID, productName, productUnitID, productUnitValue, isActive, createUserID, createDate) 
																VALUES (1, :|1, :|2, :|3, 1, 1000005, NOW());');
																
					$RSSaveProduct->Execute($RowData['Particulars'], $ProductUnitID, $ProductUnitValue);
					
					$ProductID = $RSSaveProduct->LastID;
				}
				
				# GET PRODUCT_VENDOR_ID
				
				$ProductVendorID = 0;
				
				$RSSearchVendor = $DBConnObject->Prepare('SELECT productVendorID FROM aim_product_vendors WHERE vendorName = :|1 LIMIT 1;');
				$RSSearchVendor->Execute($RowData['Supplier']);
				
				if ($RSSearchVendor->Result->num_rows > 0)
				{
					$ProductVendorID = $RSSearchVendor->FetchRow()->productVendorID;
				}
				else
				{
					$RSSaveVendor = $DBConnObject->Prepare('INSERT INTO aim_product_vendors (vendorName, isActive, createUserID, createDate) 
																VALUES (:|1, :|2, :|3, NOW());');
					
					$RSSaveVendor->Execute($RowData['Supplier'], 1, 1000005);
					
					$ProductVendorID = $RSSaveVendor->LastID;
				}
				
				# GET PURCHASE_ID

				$PurchaseID = 0;

				$RSSearchPurchase = $DBConnObject->Prepare('SELECT purchaseID FROM aim_product_purchases WHERE productVendorID = :|1 AND voucherNumber = :|2 LIMIT 1;');
				$RSSearchPurchase->Execute($ProductVendorID, $RowData['VoucherNumber']);
				
				if ($RSSearchPurchase->Result->num_rows > 0)
				{
					$PurchaseID = $RSSearchPurchase->FetchRow()->purchaseID;
				}
				else
				{
					$RSSavePurchase = $DBConnObject->Prepare('INSERT INTO aim_product_purchases (productVendorID, voucherNumber, purchaseDate, description, isActive, createUserID, createDate) 
																VALUES (:|1, :|2, :|3, :|4, :|5, :|6, NOW());');
					
					$RSSavePurchase->Execute($ProductVendorID, $RowData['VoucherNumber'], $RowData['Date'], '', 1, 1000005);

					$PurchaseID = $RSSavePurchase->LastID;
				}

				# GET PURCHASE_DETAILS_ID

				$PurchaseDetailID = 0;

				$RSSearchPurchaseDetails = $DBConnObject->Prepare('SELECT purchaseDetailID FROM aim_product_purchases_details WHERE purchaseID = :|1 AND productID = :|2 LIMIT 1;');
				$RSSearchPurchaseDetails->Execute($RowData['Supplier'], $RowData['Date']);
				
				if ($RSSearchPurchaseDetails->Result->num_rows > 0)
				{
					$PurchaseDetailID = $RSSearchPurchaseDetails->FetchRow()->purchaseDetailID;
				}
				else
				{
					$Rate = $RowData['Amount'] / $RowData['Quantity'];

					$RSSavePurchaseDetails = $DBConnObject->Prepare('INSERT INTO aim_product_purchases_details (purchaseID, productID, rate, quantity, amount) 
																VALUES (:|1, :|2, :|3, :|4, :|5);');
					
					$RSSavePurchaseDetails->Execute($PurchaseID, $ProductID, $Rate, $RowData['Quantity'], $RowData['Amount']);

					$RSUpdateProductStock = $DBConnObject->Prepare('UPDATE aim_products
																		SET	stockQuantity = (stockQuantity + :|1)
																		WHERE productID = :|2 
																		LIMIT 1;');
					$RSUpdateProductStock->Execute($RowData['Quantity'], $ProductID);
				}
			}
			
			$DBConnObject->CommitTransaction();
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at UploadHelpers::SaveStockPurchaseFromExcel(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at UploadHelpers::SaveStockPurchaseFromExcel(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
    }

    static function SaveStockIssueFromExcel($ExcelData)
    {		
        try
		{
			$DBConnObject = new DBConnect();
			
			$DBConnObject->BeginTransaction();
			
			foreach ($ExcelData as $RowData)
			{
				# GET BRANCH_STAFF_ID
				
				$BranchStaffID = 0;
				
				$RSBranchStaff = $DBConnObject->Prepare('SELECT branchStaffID FROM asa_branch_staff WHERE CONCAT(firstName, " ", lastName) LIKE "%" :|1 "%";');
				$RSBranchStaff->Execute($RowData['Buyer']);

				if ($RSBranchStaff->Result->num_rows == 1)
				{
					$BranchStaffID = $RSBranchStaff->FetchRow()->branchStaffID;
				}

				else
				{
					continue;					
				}

				# GET PRODUCT_UNIT_ID
				
				$ProductUnitID = 0;
				
				$RSProductUnit = $DBConnObject->Prepare('SELECT productUnitID FROM aim_master_product_units WHERE productUnitName = :|1 LIMIT 1;');
				$RSProductUnit->Execute($RowData['ProductUnitName']);
				
				if ($RSProductUnit->Result->num_rows > 0)
				{
					$ProductUnitID = $RSProductUnit->FetchRow()->productUnitID;
				}
				else
				{
					$RSSaveProductUnit = $DBConnObject->Prepare('INSERT INTO aim_master_product_units (productUnitName, isActive, createUserID, createDate) 
																VALUES (:|1, 1, 1000005, NOW());');
																
					$RSSaveProductUnit->Execute($RowData['ProductUnitName']);
					
					$ProductUnitID = $RSSaveProductUnit->LastID;
				}

				# GET PRODUCT_ID
				
				$ProductID = 0;
				
				$RSSearchProduct = $DBConnObject->Prepare('SELECT productID FROM aim_products WHERE productName = :|1 LIMIT 1;');
				$RSSearchProduct->Execute($RowData['Particulars']);
				
				if ($RSSearchProduct->Result->num_rows > 0)
				{
					$ProductID = $RSSearchProduct->FetchRow()->productID;
				}
				else
				{
					$ProductUnitValue = $RowData['Amount'] / $RowData['Quantity'];

					$RSSaveProduct = $DBConnObject->Prepare('INSERT INTO aim_products (productCategoryID, productName, productUnitID, productUnitValue, isActive, createUserID, createDate) 
																VALUES (1, :|1, :|2, :|3, 1, 1000005, NOW());');
																
					$RSSaveProduct->Execute($RowData['Particulars'], $ProductUnitID, $ProductUnitValue);
					
					$ProductID = $RSSaveProduct->LastID;
				}
				
				# GET STOCK_ISSUE_ID
				
				$StockIssueID = 0;
				
				$RSStockIssue = $DBConnObject->Prepare('SELECT stockIssueID FROM aim_stock_issue WHERE branchStaffID = :|1 AND voucherNumber = :|2 LIMIT 1;');
				$RSStockIssue->Execute($BranchStaffID, $RowData['VoucherNumber']);
				
				if ($RSStockIssue->Result->num_rows > 0)
				{
					$StockIssueID = $RSStockIssue->FetchRow()->stockIssueID;
				}
				else
				{
					$RSSaveStockIssue = $DBConnObject->Prepare('INSERT INTO aim_stock_issue (branchStaffID, voucherNumber, createUserID, createDate) 
																VALUES (:|1, :|2, :|3, NOW());');
					
					$RSSaveStockIssue->Execute($BranchStaffID, $RowData['VoucherNumber'], 1000005);
					
					$StockIssueID = $RSSaveStockIssue->LastID;
				}
				
				# GET STOCK_ISSUE_DETAIL_ID

				// Insert into aim_stock_issue_details

				$StockIssueDetailID = 0;

				$RSSearchStockIssueDetails = $DBConnObject->Prepare('SELECT stockIssueDetailID FROM aim_stock_issue_details WHERE stockIssueID = :|1  AND productID = :|2 LIMIT 1;');
				$RSSearchStockIssueDetails->Execute($StockIssueID, $ProductID);
				
				if ($RSSearchStockIssueDetails->Result->num_rows > 0)
				{
					$StockIssueDetailID = $RSSearchStockIssueDetails->FetchRow()->stockIssueDetailID;
				}
				else
				{
					$RSSaveStockIssueDetail = $DBConnObject->Prepare('INSERT INTO aim_stock_issue_details (stockIssueID, productID, quantity, issueDate, returnDate) 
																		VALUES (:|1, :|2, :|3, :|4, :|5);');
					
					$RSSaveStockIssueDetail->Execute($StockIssueID, $ProductID, $RowData['Quantity'], $RowData['Date'], $RowData['Date']);

					$StockIssueDetailID = $RSSaveStockIssueDetail->LastID;

					$RSUpdateProductStock = $DBConnObject->Prepare('UPDATE aim_products
																		SET	stockQuantity = (stockQuantity - :|1)
																		WHERE productID = :|2 
																		LIMIT 1;');
					$RSUpdateProductStock->Execute($RowData['Quantity'], $ProductID);
				}
			}
			
			$DBConnObject->CommitTransaction();
			
			return true;
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at UploadHelpers::SaveStockIssueFromExcel(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at UploadHelpers::SaveStockIssueFromExcel(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
    }

    static function IsMultipleStaffExist($StaffName) #, &$BranchStaffID
    {		
        try
		{
			$DBConnObject = new DBConnect();

			$RSBranchStaff = $DBConnObject->Prepare('SELECT branchStaffID FROM asa_branch_staff WHERE CONCAT(firstName, " ", lastName) LIKE "%" :|1 "%";');
			$RSBranchStaff->Execute($StaffName);
			
			if ($RSBranchStaff->Result->num_rows > 0)
			{
				return true;
			}			
		}
		catch (ApplicationDBException $e)
		{
			error_log('DEBUG: ApplicationDBException at UploadHelpers::IsMultipleStaffExist(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
		catch (Exception $e)
		{
			error_log('DEBUG: Exception at UploadHelpers::IsMultipleStaffExist(). Stack Trace: ' . $e->getTraceAsString());
			$DBConnObject->RollBackTransaction();
			return false;
		}
    }
}
?>