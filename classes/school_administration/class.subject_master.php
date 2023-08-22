<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/classes/class.db_connect.php');
error_reporting(E_ALL);

class SubjectMaster
{
    // CLASS MEMBERS ARE DEFINED HERE	//
    private $LastErrorCode;
    private $DBObject; // VARIABLE TO HOLD THE DB CONNECTION //

    private $SubjectID;
    private $Subject;

    private $IsActive;
    private $CreateUserID;
    private $CreateDate;

    // PUBLIC METHODS START HERE	//
    public function __construct($SubjectID = 0)
    {
        $this->DBObject = new DBConnect;
        $this->LastErrorCode = 0;

        if($SubjectID != 0)
        {
            $this->SubjectID = $SubjectID;
            // SET THE VALUES FROM THE DATABASE.
            $this->GetSubjectMasterByID();
        }
        else
        {
            //SET THE DEFAULT VALUES TO LOOK ATTRIBUTES
            $this->SubjectID = 0;
            $this->Subject = '';

            $this->IsActive = 0;
            $this->CreateUserID = 0;
            $this->CreateDate = '0000-00-00 00:00:00';
        }
    }

    // GETTER AND SETTER FUNCTIONS START HERE	//
    public function GetSubjectID()
    {
        return $this->SubjectID;
    }

    public function GetSubject()
    {
        return $this->Subject;
    }
    public function SetSubject($Subject)
    {
        $this->Subject = $Subject;
    }

    public function GetIsActive()
    {
        return $this->IsActive;
    }
    public function SetIsActive($IsActive)
    {
        $this->IsActive = $IsActive;
    }

    public function GetCreateUserID()
    {
        return $this->CreateUserID;
    }
    public function SetCreateUserID($CreateUserID)
    {
        $this->CreateUserID = $CreateUserID;
    }

    public function GetCreateDate()
    {
        return $this->CreateDate;
    }

    public function GetLastErrorCode()
    {
        return $this->LastErrorCode;
    }

    //  END OF GETTER AND SETTER FUNCTIONS 	//

    public function Save()
    {
        try
        {
            return $this->SaveDetails();
        }
        catch (ApplicationDBException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (ApplicationARException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (Exception $e)
        {
            $this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
            return false;
        }
    }

    public function Remove()
    {
        try
        {
            $this->RemoveSubjectMaster();
            return true;
        }
        catch (ApplicationDBException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (ApplicationARException $e)
        {
            $this->LastErrorCode = $e->getCode();
            return false;
        }
        catch (Exception $e)
        {
            $this->LastErrorCode = APP_ERROR_UNDEFINED_ERROR;
            return false;
        }
    }

    public function CheckDependencies()
    {
        try
        {
            $RSCount = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_class_subjects WHERE subjectID = :|1;');
            $RSCount->Execute($this->SubjectID);
    
            if ($RSCount->FetchRow()->totalRecords > 0) 
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException: SubjectMaster::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception: SubjectMaster::CheckDependencies. Stack Trace: ' . $e->getTraceAsString());
            return false;
        }       
    }

    public function RecordExists()
    {
        try
        {
            $QueryString = '';

            if ($this->SubjectID > 0)
            {
                $QueryString = ' AND subjectID != ' . $this->DBObject->RealEscapeVariable($this->SubjectID);
            }

            $RSTotal = $this->DBObject->Prepare('SELECT COUNT(*) AS totalRecords FROM asa_subject_master WHERE subject = :|1' . $QueryString . ';');
            $RSTotal->Execute($this->Subject);
            
            if ($RSTotal->FetchRow()->totalRecords > 0)
            {
                return true;
            }
            
            return false;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SubjectMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SubjectMaster::RecordExists(). Stack Trace: ' . $e->getTraceAsString());
            return true;
        }
    }

    // END OF PUBLIC METHODS	//

    // START OF STATIC METHODS	//
    static function GetAllSubjectMasters()
    {
        $AllSubjectMasters = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT asm.*, u.userName AS createUserName FROM asa_subject_master asm 
                                                INNER JOIN users u ON asm.createUserID = u.userID
                                                ORDER BY asm.subjectID;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSubjectMasters;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllSubjectMasters[$SearchRow->subjectID]['Subject'] = $SearchRow->subject;
                $AllSubjectMasters[$SearchRow->subjectID]['IsActive'] = $SearchRow->isActive;

                $AllSubjectMasters[$SearchRow->subjectID]['CreateUserID'] = $SearchRow->createUserID;
                $AllSubjectMasters[$SearchRow->subjectID]['CreateUserName'] = $SearchRow->createUserName;

                $AllSubjectMasters[$SearchRow->subjectID]['CreateDate'] = $SearchRow->createDate;
            }

            return $AllSubjectMasters;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SubjectMaster::GetAllSubjectMasters(). Stack Trace: '.$e->getTraceAsString());
            return $AllSubjectMasters;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SubjectMaster::GetAllSubjectMasters(). Stack Trace: '.$e->getTraceAsString());
            return $AllSubjectMasters;
        }
    }

    static function GetActiveSubjectMasters()
    {
        $AllSubjectMasters = array();

        try
        {
            $DBConnObject = new DBConnect();

            $RSSearch = $DBConnObject->Prepare('SELECT * FROM asa_subject_master WHERE isActive = 1;');
            $RSSearch->Execute();

            if ($RSSearch->Result->num_rows <= 0)
            {
                return $AllSubjectMasters;
            }

            while($SearchRow = $RSSearch->FetchRow())
            {
                $AllSubjectMasters[$SearchRow->subjectID] = $SearchRow->subject;
            }

            return $AllSubjectMasters;
        }
        catch (ApplicationDBException $e)
        {
            error_log('DEBUG: ApplicationDBException at SubjectMaster::GetActiveSubjectMasters(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSubjectMasters;
        }
        catch (Exception $e)
        {
            error_log('DEBUG: Exception at SubjectMaster::GetActiveSubjectMasters(). Stack Trace: ' . $e->getTraceAsString());
            return $AllSubjectMasters;
        }
    }
    // END OF STATIC METHODS	//

    // START OF PRIVATE METHODS	//
    private function SaveDetails()
    {
        if ($this->SubjectID == 0)
        {
            $RSSave = $this->DBObject->Prepare('INSERT INTO asa_subject_master (subject, isActive, createUserID, createDate)
                                                        VALUES (:|1, :|2, :|3, NOW());');

            $RSSave->Execute($this->Subject, $this->IsActive, $this->CreateUserID);

            $this->SubjectID = $RSSave->LastID;
        }
        else
        {
            $RSUpdate = $this->DBObject->Prepare('UPDATE asa_subject_master
                                                    SET subject = :|1,
                                                        isActive = :|2
                                                    WHERE subjectID = :|3 LIMIT 1;');

            $RSUpdate->Execute($this->Subject, $this->IsActive, $this->SubjectID);
        }

        return true;
    }

    private function RemoveSubjectMaster()
    {
        if(!isset($this->SubjectID)) 
        {
            throw new ApplicationARException('', APP_AR_ERROR_DELETE_WITHOUT_ID);
        }
        
        $RSDeleteTaskGroup = $this->DBObject->Prepare('DELETE FROM asa_subject_master WHERE subjectID = :|1 LIMIT 1;');
        $RSDeleteTaskGroup->Execute($this->SubjectID);                
    }

    private function GetSubjectMasterByID()
    {
        $RSSubjectMaster = $this->DBObject->Prepare('SELECT * FROM asa_subject_master WHERE subjectID = :|1 LIMIT 1;');
        $RSSubjectMaster->Execute($this->SubjectID);

        $SubjectMasterRow = $RSSubjectMaster->FetchRow();

        $this->SetAttributesFromDB($SubjectMasterRow);
    }

    private function SetAttributesFromDB($SubjectMasterRow)
    {
        $this->SubjectID = $SubjectMasterRow->subjectID;
        $this->Subject = $SubjectMasterRow->subject;

        $this->IsActive = $SubjectMasterRow->isActive;
        $this->CreateUserID = $SubjectMasterRow->createUserID;
        $this->CreateDate = $SubjectMasterRow->createDate;
    }
}
?>