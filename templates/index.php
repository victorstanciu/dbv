<?php session_start(); ?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo _("dbv.php - Database versioning, made easy"); ?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="content-language" content="en" />
	<meta name="robots" content="noindex,nofollow" />
	<meta name="author" content="Victor Stanciu - http://victorstanciu.ro" />

	<link rel="stylesheet" type="text/css" media="screen" href="public/stylesheets/dbv.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="public/stylesheets/codemirror.css" />

	<script type="text/javascript">
		var APP = {};
	</script>

	<script type="text/javascript" src="public/scripts/prototype.js"></script>
	<script type="text/javascript" src="public/scripts/builder.js"></script>
	<script type="text/javascript" src="public/scripts/effects.js"></script>

	<script type="text/javascript" src="public/scripts/codemirror/codemirror.js"></script>
	<script type="text/javascript" src="public/scripts/codemirror/mode/mysql.js"></script>
	<script type="text/javascript" src="public/scripts/codemirror/mode/php.js"></script>

	<script type="text/javascript" src="public/scripts/dbv.js"></script>
</head>
<body>
	<div class="navbar navbar-static-top navbar-inverse">
		<div class="navbar-inner">
			<div class="container">
				<a href="index.php" class="brand">dbv</a>
				<ul class="nav pull-right">
					<li><a href="ManageDB.php"><?php echo __('Add new Database'); ?></a></li>
                    
                    <li><a href="http://dbv.vizuina.com/documentation/#usage" target="_blank"><?php echo __('Help'); ?></a></li>
                    <li><a href="readme.php"><?php echo __('Readme'); ?></a></li>
				</ul>
			</div>
		</div>
	</div>
    
	<div class="clear"></div>
    
    <div id="content" class="container" style="margin-top:10px;">
    	<span style="color:#FFFFFF; font-size:12px; font-weight:bold;">Select Database :- </span> 
        <select name="database" onChange="window.location.href='index.php?db='+this.value">
        	<?php foreach($database_list as $key=>$value) { $exp_tmp = explode(':',$value); ?>
            <option value="<?php echo $exp_tmp[4]; ?>" <?php if($_REQUEST['db'] == $exp_tmp[4]) echo 'selected'; ?>>
				<?php echo strtoupper($exp_tmp[4]); ?>
            </option>
            <?php } ?>
        </select>
    </div>
    
    <div class="clear"></div>
    
    
	<div id="content" class="container">
		<div id="log" style="margin: 20px 0 -10px 0;">
			
			<?php $this->_view('log'); ?>
            
            <?php if( $_SESSION['act'] != '' ) { $mes = $_SESSION['act']; $temp_exp = explode('#',$mes) ?>
            <div class="alert alert-<?php echo $temp_exp[1]; ?>">
				<?php echo $temp_exp[0]; ?>
            </div>
            <?php unset($_SESSION['act']);} ?>
            
		</div>
		<div class="row-fluid">
			<div class="span4">
				<div id="left">
					<?php $this->_view('schema'); ?>
				</div>
			</div>
			<div class="span8">
				<div id="right">
                
                	<!-- Add revision form -->
                    <div class="add_revison">
	                	<input type="button" class="btn btn-info" value="Add Revision" onClick="ShowHide();" />
                    </div>
                    
                    <div id="add_rev_form" style="display:none;">
                    	<form action="add_rev.php" method="post" name="add_revision">
                    	<table class="table table-condensed table-striped">
                        <tr>
                        	<th width="20%" style="border:none;">Revision ID</th>
                            <td width="80%" style="border:none;">
                            	<input type="text" name="revision_id" id="revision_id" /> 
                            </td>
                        </tr>
                        <tr>
                        	<th style="border:none;">Revision Number</th>
                            <td style="border:none;">
                            	<input type="text" name="revision_num" id="revision_num" /> 
                            </td>
                        </tr>
                        <tr>
                        	<th style="border:none;">SQL</th>
                            <td style="border:none;">
                            	<textarea name="sql" id="sql" style="width:90%; height:100px;"></textarea>
                            </td>
                        </tr>
                        <tr>
                        	<td style="border:none;"></td>
                            <td style="border:none;">
                            	<input type="submit" class="btn btn-primary" value="Submit" />
                                <input type="hidden" name="add_rev" value="add_rev" />
                                <input type="button" class="btn" value="Cancel" onClick="ShowHide()" />
                                <input type="hidden" name="db" value="<?php echo DB_NAME; ?>" />
                            </td>
                        </tr>
                        </table>
                        </form>
                    </div>
                    <div style="clear:both;"></div>
					<?php $this->_view('revisions'); ?>
				</div>
			</div>
		</div>
	</div>
</body>
</html>