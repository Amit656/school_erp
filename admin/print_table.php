<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Bootstrap Core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="dist/css/sb-admin-2.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- Custom CSS By Singleton -->
    <link href="css/custom.css" rel="stylesheet">
    
    <style type="text/css">
        .print-hidden {display:none !important;}
    </style>
</head>
<body style="background-color: #fff;">
	<div style="margin:2px; padding:2px;" id="PrintContent"></div>
    
    	<!-- jQuery -->
    <script src="vendor/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap Core JavaScript -->
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="dist/js/sb-admin-2.js"></script>
    
    <script type="text/javascript">
		$('#PrintContent').append(window.opener.$("#RecordTableHeading").html());
		$('#PrintContent').append(window.opener.$("#RecordTable").html());
		
		window.print();
    </script>
</body>
</html>