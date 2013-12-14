<?php
/**
 * @package: Multi-db upgrade
 * @date: Dec 2013
 * @author: Chris Fortune - http://cfortune.kics.bc.ca/
 */

include('header.php');

?>

<div id="content" class="container" style="margin-top:10px; color:#FFFFFF;">

<?php

if( isset($_POST['submit']) && $_POST['submit'] == 'Save')
{
	if($_POST['subact'] == 'add')
	{
		$localhost = $_POST['host'];
		$port = $_POST['port'];
		$username = $_POST['username'];
		$password = $_POST['password'];
		$db = $_POST['db'];
		
		$conn = @mysql_pconnect($localhost,$username,$password);
		$seldb = @mysql_select_db($db,$conn);
		
		$all_db = ReadCSVDB();
		
		if($seldb)
		{
			if(!in_array($db,$all_db))
			{
				$arry_to_write = "\n".$localhost.','.$port.','.$username.',"'.$password.'",'.$db;
				$fp = fopen('db.csv','a') or die("can't open file");
				$written = fwrite($fp, $arry_to_write);
				
				if($written)
				{
					mkdir('data/schema/'.$db);
					mkdir('data/revisions/'.$db);
				}
				
				fclose($fp);
				
				echo '<script>window.location.href="ManageDB.php"</script>';
			}
			else
				echo 'Database already exists!!';	
		}
		else
			echo 'DB Connection failed!!';
	}
}

if( isset($_REQUEST['view']) && $_REQUEST['view'] == 'Del' && $_REQUEST['id']!='' && $_REQUEST['db']!='')
{	
	$id = $_GET['id'];
	
	if($id) 
	{
		$delete = csv_delete_rows('db.csv', $id, $id, true);
		
		if($delete)
		{
			rrmdir('data/schema/'.$_REQUEST['db']);
			rrmdir('data/revisions/'.$_REQUEST['db']);
		}	
		
		echo '<script>window.location.href="ManageDB.php"</script>';
	}
}

if(isset( $_REQUEST['view']) && $_REQUEST['view'] == 'Add')
{
	?>
    <h3>Add new Database</h3>
    <form method="post" action="" onsubmit="return CheckForm(this)" name="adddb">
    <table border="0" cellpadding="0" cellspacing="0" width="50%">
    <tr>
    	<td width="20%">Host:</td>
        <td width="80%"><input type="text" name="host" id="host" /></td>
    </tr>
    <tr>
    	<td>Port</td>
        <td><input type="text" name="port" id="port" /></td>
    </tr>
    <tr>
    	<td>Username</td>
        <td><input type="text" name="username" id="username" /></td>
    </tr>
    <tr>
    	<td>Password</td>
        <td><input type="text" name="password" id="password" /></td>
    </tr>
    <tr>
    	<td>DB Name</td>
        <td><input type="text" name="db" id="db" /></td>
    </tr>
    <tr>
    	<td></td>
        <td>
        	<input type="submit" name="submit" value="Save" />
            <input type="button" name="cancel" value="Cancel" onclick="window.location.href='ManageDB.php'" />
            <input type="hidden" name="subact" value="add" />
		</td>
    </tr>
    </table>
    </form>
    <?php
}
else
{
	$fp = fopen('db.csv','r') or die("can't open file");
	
	?>
	<a href="ManageDB.php?view=Add">Add new DB</a> &nbsp;&nbsp;|&nbsp;&nbsp; <a href="index.php">Go to home</a>
	<table width="100%" cellpadding="0" cellspacing="0" border="1" style="border-color:#FFFFFF; margin-top:15px;">
	
	<?php
	
	$g =1 ;
	
	while($csv_line = fgetcsv($fp,1024)) 
	{
		?>
		
		<tr>
		
		<?php
		
		for ($i = 0, $j = count($csv_line); $i < $j; $i++) 
		{
			print '<td align="center">'.$csv_line[$i].'</td>';
			
			if($i == $j-1)
			{
				if($g == 1)
					echo '<td align="center"><b>Action</b></td>';
				else
					echo '<td align="center">
							<a href="#" onclick="doconfirm(\'ManageDB.php?view=Del&id='.$g.'&db='.$csv_line[$i].'\')">Delete</a>
						</td>';
			}
		}
		
		?>
		
		</tr>
		
		<?php
		
		$g++;
	}
	
	?>
	
	</table>
	
<?php
	
	fclose($fp) or die("can't close file");
}

?>

<script>

function doconfirm(url)
{
	if(confirm('Do you want to delete this db with all its schema and revisions?'))
	{
		window.location.href = url;	
	}
}

function CheckForm(frm)
{
	if(frm.host.value == '')
	{
		alert('Please Enter hostname');	
		frm.host.focus();
		return false;
	}
	
	if(frm.username.value == '')
	{
		alert('Please Enter Username');	
		frm.username.focus();
		return false;
	}
	
	
	if(frm.db.value == '')
	{
		alert('Please Enter DB Name');	
		frm.db.focus();
		return false;
	}
	
	return true;
}

</script>

<?php

function csv_delete_rows($filename=NULL, $startrow=0, $endrow=0, $inner=true) {
    $status = 0;
    //check if file exists
    if (file_exists($filename)) {
        //end execution for invalid startrow or endrow
        if ($startrow < 0 || $endrow < 0 || $startrow > 0 && $endrow > 0 && $startrow > $endrow) {
            die('Invalid startrow or endrow value');
        }
        $updatedcsv = array();
        $count = 0;
        //open file to read contents
        $fp = fopen($filename, "r");
        //loop to read through csv contents
        while ($csvcontents = fgetcsv($fp)) {
            $count++;
            if ($startrow > 0 && $endrow > 0) {
                //delete rows inside startrow and endrow
                if ($inner) {
                    $status = 1;
                    if ($count >= $startrow && $count <= $endrow)
                        continue;
					array_push($updatedcsv, '"'.implode('", "', $csvcontents).'"');
                }
                //delete rows outside startrow and endrow
                else {
                    $status = 2;
                    if ($count < $startrow || $count > $endrow)
                        continue;
                    array_push($updatedcsv, '"'.implode('", "', $csvcontents).'"');
                }
            }
            else if ($startrow == 0 && $endrow > 0) {
                $status = 3;
                if ($count <= $endrow)
                    continue;
				array_push($updatedcsv, '"'.implode('", "', $csvcontents).'"');
            }
            else if ($endrow == 0 && $startrow > 0) {
                $status = 4;
                if ($count >= $startrow)
                    continue;
				array_push($updatedcsv, '"'.implode('", "', $csvcontents).'"');
            }
            else if ($startrow == 0 && $endrow == 0) {
                $status = 5;
            } else {
                $status = 6;
            }
        }//end while
        if ($status < 5) {
            $finalcsvfile = implode("\n", $updatedcsv);
            fclose($fp);
            $fp = fopen($filename, "w");
            fwrite($fp, $finalcsvfile);
        }
        fclose($fp);
        return $status;
    } else {
        die('File does not exist');
    }
}

function ReadCSVDB()
{
	$fp = fopen('db.csv','r') or die("can't open file");

	$db = array();
	$g =1 ;
	
	while($csv_line = fgetcsv($fp,1024)) 
	{	
		for ($i = 0, $j = count($csv_line); $i < $j; $i++) 
		{
			if($g>1)
			{
				if(!in_array($csv_line[4],$db))
					array_push($db,$csv_line[4]);
			}
		}
		
		$g++;
	}
		
	fclose($fp) or die("can't close file");	
	
	return $db;
}

function rrmdir($dir) 
{ 
	if (is_dir($dir)) 
	{ 
    	$objects = scandir($dir); 
     	foreach ($objects as $object) 
		{ 
       		if ($object != "." && $object != "..") 
			{ 
         		if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       		} 
     	} 
     	
		reset($objects); 
     	rmdir($dir); 
   	} 
} 

?>
</div>
<?php
include('footer.php');
 
?>