$(document).ready(function() {
    $('#PrintButton').click(function(){
        window.open ("/admin/print_table.php","mywindow","location=0,status=0,scrollbars=1,width=700,height=500");
        return false;
    });
});