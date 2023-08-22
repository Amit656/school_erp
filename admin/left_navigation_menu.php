            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li class="sidebar-search">
                            <div class="input-group custom-search-form">
                                <input type="text" class="form-control" id="SearchMenuBox" placeholder="Search..." onkeyup="filterMenues();">
                                <span class="input-group-btn">
                                <button class="btn btn-default" type="button">
                                    <i class="fa fa-search"></i>
                                </button>
                            </span>
                            </div>
                            <!-- /input-group -->
                        </li>
                        <li>
                            <a href="/admin/admin_default.php"><i class="fa fa-dashboard fa-fw"></i> Dashboard</a>
                        </li>
                        <ul style="overflow-y:auto; height:507px;"  class="nav" id="side-menu">
<?php
                $CurrentModuleID = 0;

                if (isset($_SESSION['CurrentModuleID']))
                {
                    $CurrentModuleID = (int) $_SESSION['CurrentModuleID'];
                }
                
                $UserMenusArray = array();
                $UserMenusArray = $LoggedUser->GetUserMenus($CurrentModuleID);
                
                if (is_array($UserMenusArray) && count($UserMenusArray) > 0)
                {
                    foreach ($UserMenusArray as $MenuName => $MenuDetailsArray)
                    {
                        echo '<li>';
                        echo '<a href="#"><i class="fa fa-bar-chart-o fa-fw"></i> ' . $MenuName . '<span class="fa arrow"></span></a>';
                        echo '<ul class="nav nav-second-level">';

                        foreach ($MenuDetailsArray as $SubmenuId => $SubmenuDetailsArray)
			            {
                            echo '<li><a href="/admin/' . $SubmenuDetailsArray['LinkedFilename'] . '">' . $SubmenuDetailsArray['SubmenuName'] . '</a></li>';
                        }

                        echo '</ul>';
                        echo '</li>';
                    }
                }
?>
                        </ul>
                    </ul>
                </div>
                <!-- /.sidebar-collapse -->
            </div>