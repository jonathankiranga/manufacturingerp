<?php
	if (!isset($RootPath)){
            $RootPath = dirname(htmlspecialchars($_SERVER['PHP_SELF']));
            if ($RootPath == '/' OR $RootPath == "\\") {
                $RootPath = '';
            }
	}
        
 

/* $Id: Login.php 6475 2013-12-04 17:56:10Z rchacon $*/


echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
?>
<html>
<head>
  <title>SMARTERNOW DATA VENTURE</title><meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="Jonathan Kirnaga">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="css/signin.css" type="text/css" />
      <?php   
        echo '<link href="'.$RootPath.'/javascripts/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="'.$RootPath.'/javascripts/jquery.min.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/jquery-1.12.0.min.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/jquery.tablesorter.pager.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/bootstrap.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/javascripts/bootstrap.min.js"></script>
        <script type="text/javascript" src="'.$RootPath.'/Ajax/jquerryportal.js"></script>';
        ?>
</head>
<body>

<div class="container">
 	<form autocomplete="off" class="form-signin"   action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">
        <h2 class="form-signin-heading">Please sign in (ERP)</h2>
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<span  class="icon-bar">
	<?php
	    if (isset($CompanyList) && is_array($CompanyList)) {
            foreach ($CompanyList as $key => $CompanyEntry){
                if ($DefaultDatabase == $CompanyEntry['database']) {
                    $CompanyNameField = "$key";
                    $DefaultCompany = $CompanyEntry['company'];
                }
            }
	        if ($AllowCompanySelectionBox === 'Hide'){
                        // do not show input or selection box
                        echo '<input type="hidden" name="CompanyNameField"  value="' .  $CompanyNameField . '" />';
		    } elseif ($AllowCompanySelectionBox === 'ShowInputBox'){
                        // show input box
                        echo _('Company') .':' .  '<input class="form-control" type="text" name="DefaultCompany"  autofocus="autofocus" required="required" value="' .  htmlspecialchars($DefaultCompany ,ENT_QUOTES,'UTF-8') . '" disabled="disabled"/>';//use disabled input for display consistency
		        echo '<input type="hidden" name="CompanyNameField"  value="' .  $CompanyNameField . '" />';
		    } else {
                // Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
                echo _('Company') . ':<br />';
                echo '<select name="CompanyNameField" class="form-control">';
                foreach ($CompanyList as $key => $CompanyEntry){
                    if (is_dir('companies/' . $CompanyEntry['database']) ){
                        if ($CompanyEntry['database'] == $DefaultDatabase) {
                            echo '<option selected="selected" label="'.htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8').'" value="'.$key.'">' . htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8') . '</option>';
                        } else {
                            echo '<option label="'.htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8').'" value="'.$key.'">' . htmlspecialchars($CompanyEntry['company'],ENT_QUOTES,'UTF-8') . '</option>';
                        }
                    }
                }
                echo '</select>';
            }
	    }
	      else { //provision for backward compat - remove when we have a reliable upgrade for config.php
            if ($AllowCompanySelectionBox === 'Hide'){
			    // do not show input or selection box
			    echo '<input type="hidden" name="CompanyNameField"  value="' . $DefaultCompany . '" />';
		    } else if ($AllowCompanySelectionBox === 'ShowInputBox'){
			    // show input box
			    echo _('Company') . '<input class="form-control" type="text" name="CompanyNameField"  autofocus="autofocus" required="required" value="' . $DefaultCompany . '" />';
		    } else {
      			// Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
    			echo _('Company') . ':';
	    		echo '<select name="CompanyNameField"  class="form-control">';
	    		$Companies = scandir('companies/', 0);
			    foreach ($Companies as $CompanyEntry){
                    if (is_dir('companies/' . $CompanyEntry) AND $CompanyEntry != '..' AND $CompanyEntry != '' AND $CompanyEntry!='.svn' AND $CompanyEntry!='.'){
                        if ($CompanyEntry==$DefaultDatabase) {
                            echo '<option selected="selected" label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">' . $CompanyEntry . '</option>';
                        } else {
                            echo '<option label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">' . $CompanyEntry . '</option>';
                        }
                    }
    	        }
    	         echo '</select>';
            }
        } //end provision for backward compat
	?>
	</span><br />
            <?php
	if(isset($demo_text)){
        echo $demo_text;
        }
        ?>
        <label for="UserNameEntryField" class="sr-only">User name</label>
	<input class="form-control" type="text" name="UserNameEntryField" required="required" autofocus="autofocus" maxlength="20" placeholder="<?php echo _('User name'); ?>" />
	<label for="Password" class="sr-only">Password</label>
	<input class="form-control" type="password" required="required" name="Password" placeholder="<?php echo _('Password'); ?>" />
	
	<input class="btn btn-lg btn-primary btn-block"  type="submit" value="<?php echo _('Sign In'); ?>" name="SubmitUser" />
	 <br/><div><label for="ForgotPassword">Forgot or Don't know Password</label>
             <input type="email" name="ForgotPassword" id="getEmail" maxlength="50" value="" placeholder="<?php echo _('Enter your official email'); ?>" />
        <input type="button" id="SendPword" value="Send Password" name="SendPassword" />
       </div>
	</form><div id="Hassentemail"></div>
</div>

</body>
</html>
