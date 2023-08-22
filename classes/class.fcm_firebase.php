<?php 
 
class Firebase 
{

    private $FIREBASE_API_KEY_TEACHER = 'AAAAuzFc9iM:APA91bEXAiIWyX8bPDAHimceD6OGqOFoKr9pGfnST4UZZvQTWQX7mTwUNo65nQnxZ8K6iU34ayZO2ZG3lncj_2NEILcfzQsqVH3zN1oPwQa1S-hMP2jCCHYBcqSyzISKjBpgNmCsiif-';

    private $FIREBASE_API_KEY_PARENT = 'AAAA2dhw7rY:APA91bHyoM2sNTKsWPNfJaUqPOMq6US7jgCkre6cDJz_U31PlgLc0NaRJETkY2YJYZafPNaeED7AQP89QjbOOpSZsSS1jo_kGueauWbUCuhhoOaEvUjpLzQyA94LW_C-F58h_lvElDu_';
 
    public function Send($RegistrationIDs, $Message, $ApiKey) 
    {
        $Fields = array('registration_ids' => $RegistrationIDs, 'data' => $Message);

        return $this->SendPushNotification($Fields, $ApiKey);
    }
    
    /*
    * This function will make the actual curl request to firebase server
    * and then the message is sent 
    */

    private function SendPushNotification($Fields, $ApiKey) 
    {

        //importing the constant files
        //require_once 'FCMConfig.php';
        
        //firebase server url to send the curl request
        $URL = 'https://fcm.googleapis.com/fcm/send';
 
        //building headers for the request
        $Headers = array(
            // 'Authorization: key=' . FIREBASE_API_KEY,
            // 'Content-Type: application/json'
            'Authorization: key=' . $ApiKey,
            'Content-Type: application/json'
        );
 
        //Initializing curl to open a connection
        $ch = curl_init();
 
        //Setting the curl url
        curl_setopt($ch, CURLOPT_URL, $URL);
        
        //setting the method as post
        curl_setopt($ch, CURLOPT_POST, true);
 
        //adding headers 
        curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        //disabling ssl support
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        //adding the fields in json format 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Fields));
 
        //finally executing the curl request 
        $Result = curl_exec($ch);

        if ($Result === FALSE) 
        {
            die('Curl failed: ' . curl_error($ch));
        }
 
        //Now close the connection
        curl_close($ch);
 
        //and return the result 
        return $Result;
    }
}

?>