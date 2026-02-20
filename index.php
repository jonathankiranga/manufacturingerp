<<<<<<< HEAD
<?php
$PageSecurity=0;
include('includes/session.inc');
$Title =_('Main Menu');
include('includes/default_header.inc');
/*The module link codes are hard coded in a switch statement below to determine the options to show for each tab */
include('includes/MainMenuLinksArray.php');
echo '<div style="padding:0">';
 
echo '</div>';
echo '<div class="well well-treeview">';

echo '<div class="frame-treeview">';
echo '<ul class="nav nav-list">' ;
        
 $i=0;
while ($i < count($ModuleLink)){
   $Module = $ModuleLink[$i];
        
	if ($_SESSION['ModulesEnabled'][$i]==1)	{
        echo  '<li class="divider"></li>'
            . '<li><label class="tree-toggler nav-header">'.$ModuleList[$i].'</label>';
                    echo '<ul class="nav nav-list tree">';
                    echo '<li><label class="tree-toggler nav-header grouper">Transactions</label>'
                       . '<ul class="nav nav-list tree">';
                        $t=0;
                         foreach ($MenuItems[$Module]['Transactions']['Caption'] as $Caption) {
                                 $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Transactions']['URL'][$t],1));
                                 $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                 if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                      echo '<li><a target="mainContentIFrame" href="' . $RootPath . $MenuItems[$Module]['Transactions']['URL'][$t] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                 }
                            $t++;
                         }
                   echo '</ul></li>';
                          
                    echo '<li class="divider"></li>'
                          . '<li><label class="tree-toggler nav-header grouper">Inquiries and Reports</label>'
                          . '<ul class="nav nav-list tree"> ';
                            $r=0;
                            foreach ($MenuItems[$Module]['Reports']['Caption'] as $Caption) {
                                    $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Reports']['URL'][$r],1));
                                    $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                    if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                        echo '<li><a target="mainContentIFrame"  href="' . $RootPath . $MenuItems[$Module]['Reports']['URL'][$r] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                    }
                               $r++;
                            }
                        echo '</ul></li>';
                          
                    echo ' <li class="divider"></li>'
                          . '<li><label class="tree-toggler nav-header grouper">Set Up</label>'
                          . '<ul class="nav nav-list tree"> ';
                            $m=0;
                            foreach ($MenuItems[$Module]['Maintenance']['Caption'] as $Caption) {
                                    $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Maintenance']['URL'][$m],1));
                                    $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                    if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                        echo '<li><a target="mainContentIFrame" href="' . $RootPath . $MenuItems[$Module]['Maintenance']['URL'][$m] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                    }
                               $m++;
                            }
                          echo '</ul></li>';
                          
                  echo '</ul>';
              echo '</li>';
        }
   $i++;
}
 
echo '</ul></div></div>';

echo '<div id="mainBody"><iframe name="mainContentIFrame" frameborder="0" id="mainContentIFrame" scrolling="Yes"></iframe></div>';
echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/treeview/tree.js"></script>';


echo '<link href="'.$RootPath.'/vendor/harvesthq/chosen/chosen.css" rel="stylesheet" type="text/css" />';
echo '<link href="'.$RootPath.'/vendor/harvesthq/prism.css" rel="stylesheet" type="text/css" />';

  echo  '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/jquery-3.2.1.min.js"></script>
   <script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/chosen/chosen.jquery.js"></script>'
 . '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/prism.js"></script>'
 . '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/init.js"></script>';

include('includes/default_footer.inc');

 echo '<script type="text/javascript">function Loadhomepage(rootpath){ ';
 echo " var a = document.createElement('a');";
 echo " a.href=rootpath+'/homepage.php';
        a.target = 'mainContentIFrame';
         a.id     = 'autoload';
         document.body.appendChild(a);
         a.click();   ";
echo " }  Loadhomepage('".$RootPath."');</script>";



?>

<script>
   
$('#my_select_box').on('change', function(evt, params) {
     if ($(this).val() != "") {
      $("#autoload").prop('href',$(this).val());
   
      $('#autoload')[0].click(); 
  }
  
});
=======
<?php
$PageSecurity=0;
include('includes/session.inc');
$Title =_('Main Menu');
include('includes/default_header.inc');
/*The module link codes are hard coded in a switch statement below to determine the options to show for each tab */
include('includes/MainMenuLinksArray.php');
echo '<div style="padding:0">';
 
echo '</div>';
echo '<div class="well well-treeview">';

echo '<div class="frame-treeview">';
echo '<ul class="nav nav-list">' ;
        
 $i=0;
while ($i < count($ModuleLink)){
   $Module = $ModuleLink[$i];
        
	if ($_SESSION['ModulesEnabled'][$i]==1)	{
        echo  '<li class="divider"></li>'
            . '<li><label class="tree-toggler nav-header">'.$ModuleList[$i].'</label>';
                    echo '<ul class="nav nav-list tree">';
                    echo '<li><label class="tree-toggler nav-header grouper">Transactions</label>'
                       . '<ul class="nav nav-list tree">';
                        $t=0;
                         foreach ($MenuItems[$Module]['Transactions']['Caption'] as $Caption) {
                                 $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Transactions']['URL'][$t],1));
                                 $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                 if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                      echo '<li><a target="mainContentIFrame" href="' . $RootPath . $MenuItems[$Module]['Transactions']['URL'][$t] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                 }
                            $t++;
                         }
                   echo '</ul></li>';
                          
                    echo '<li class="divider"></li>'
                          . '<li><label class="tree-toggler nav-header grouper">Inquiries and Reports</label>'
                          . '<ul class="nav nav-list tree"> ';
                            $r=0;
                            foreach ($MenuItems[$Module]['Reports']['Caption'] as $Caption) {
                                    $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Reports']['URL'][$r],1));
                                    $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                    if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                        echo '<li><a target="mainContentIFrame"  href="' . $RootPath . $MenuItems[$Module]['Reports']['URL'][$r] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                    }
                               $r++;
                            }
                        echo '</ul></li>';
                          
                    echo ' <li class="divider"></li>'
                          . '<li><label class="tree-toggler nav-header grouper">Set Up</label>'
                          . '<ul class="nav nav-list tree"> ';
                            $m=0;
                            foreach ($MenuItems[$Module]['Maintenance']['Caption'] as $Caption) {
                                    $ScriptNameArray = explode('?', substr($MenuItems[$Module]['Maintenance']['URL'][$m],1));
                                    $PageSecurity = $_SESSION['PageSecurityArray'][$ScriptNameArray[0]];
                                    if ((in_array($PageSecurity,$_SESSION['AllowedPageSecurityTokens']) OR !isset($PageSecurity))) {
                                        echo '<li><a target="mainContentIFrame" href="' . $RootPath . $MenuItems[$Module]['Maintenance']['URL'][$m] .'"><label class="nav-header nav-items">' . $Caption . '</label></a></li>';
                                    }
                               $m++;
                            }
                          echo '</ul></li>';
                          
                  echo '</ul>';
              echo '</li>';
        }
   $i++;
}
 
echo '</ul></div></div>';

echo '<div id="mainBody"><iframe name="mainContentIFrame" frameborder="0" id="mainContentIFrame" scrolling="Yes"></iframe></div>';
echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/treeview/tree.js"></script>';


echo '<link href="'.$RootPath.'/vendor/harvesthq/chosen/chosen.css" rel="stylesheet" type="text/css" />';
echo '<link href="'.$RootPath.'/vendor/harvesthq/prism.css" rel="stylesheet" type="text/css" />';

  echo  '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/jquery-3.2.1.min.js"></script>
   <script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/chosen/chosen.jquery.js"></script>'
 . '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/prism.js"></script>'
 . '<script type="text/javascript" src="'.$RootPath.'/vendor/harvesthq/init.js"></script>';

include('includes/default_footer.inc');

 echo '<script type="text/javascript">function Loadhomepage(rootpath){ ';
 echo " var a = document.createElement('a');";
 echo " a.href=rootpath+'/homepage.php';
        a.target = 'mainContentIFrame';
         a.id     = 'autoload';
         document.body.appendChild(a);
         a.click();   ";
echo " }  Loadhomepage('".$RootPath."');</script>";



?>

<script>
   
$('#my_select_box').on('change', function(evt, params) {
     if ($(this).val() != "") {
      $("#autoload").prop('href',$(this).val());
   
      $('#autoload')[0].click(); 
  }
  
});
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
</script>