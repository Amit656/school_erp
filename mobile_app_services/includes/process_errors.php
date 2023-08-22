<?php
function ProcessAppErrors($ErrorCode)
{
    $ErrorMessage = '';

    switch ($ErrorCode)
    {
        case 101:
            $ErrorMessage = 'You could not be logged in. Invalid username or password.';
        break;

        case 102:
            $ErrorMessage = 'Unknown error, please try again.';
        break;

        case 103:
            $ErrorMessage = 'Please select a valid date which is less then or equals to current date.';
        break;

        case 104:
            $ErrorMessage = 'This attendance date is not a working day, accordingly to global setting or academic event.';
        break;
        
        case 105:
            $ErrorMessage = 'Error Chat is not enabled!';
        break;

        default:
            $ErrorMessage = 'Unknown error, please try again.';
        break;
    }

    return $ErrorMessage;
}

function ProcessAppMessages($MessageCode)
{
    $Message = '';

    switch ($MessageCode)
    {
        case 10001:
            $Message = 'Login Successful.';
        break;

        case 10002:
            $Message = 'Saved Successfuly.';
        break;

        default:
            $Message = 'Unknown error, please try again.';
        break;
    }

    return $Message;
}
?>