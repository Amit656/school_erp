<?php 
 
class Push {
    //notification Title
    private $Title;
 
    //notification message 
    private $Message;
 
    //notification image url 
    private $Image;

    // push Message payload
    private $Data;
 
    //initializing values in this constructor
    public function __construct() 
    {
        $this->Title = '';
        $this->Message = ''; 
        $this->Image = ''; 
        $this->Data = ''; 
    }

    public function SetTitle($Title) 
    {
        $this->Title = $Title;
    }
 
    public function SetMessage($Message) 
    {
        $this->Message = $Message;
    }
 
    public function SetImage($ImageUrl) 
    {
        $this->Image = $ImageUrl;
    }
 
    public function SetPayload($Data) 
    {
        $this->Data = $Data;
    }
    
    //getting the push notification
    public function GetPush() 
    {
        $Result = array();

        $Result['data']['title'] = $this->Title;
        $Result['data']['message'] = $this->Message;
        $Result['data']['image'] = $this->Image;
        $Result['data']['payload'] = $this->Data;

        return $Result;
    }


 
}

?>