	<!-- jQuery -->
    <script src="/admin/vendor/jquery/jquery.min.js"></script>
    
    <!-- Bootstrap Core JavaScript -->
    <script src="/admin/vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="/admin/vendor/metisMenu/metisMenu.min.js"></script>

    <!-- Custom Theme JavaScript -->
    <script src="/admin/dist/js/sb-admin-2.js"></script>
	
	<!-- Common JS -->
    <script src="/admin/dist/js/admin-common.js"></script>

    <script type="text/javascript">
    function filterMenues() {
        SearchedString = $('#SearchMenuBox').val();

        div = document.getElementById('side-menu');

        /*ul = div.getElementsByTagName("ul");

        while (ul.length) {
            ul[0].classList.remove("in");
        }*/

        a = div.getElementsByTagName("a");
        
        for (i = 0; i < a.length; i++)
        {
            a[i].parentNode.parentNode.classList.remove('in');

            if (a[i].innerHTML.toUpperCase().indexOf(SearchedString.toUpperCase()) > -1)
            {
                a[i].parentNode.parentNode.classList.add('in');
                a[i].style.display = "";
            }
            else
            {
                a[i].parentNode.parentNode.classList.remove('sameer');
                a[i].style.display = "none";
            }
        }
    }
    </script>