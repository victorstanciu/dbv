<?php
/**
 * @package: Multi-db upgrade
 * @date: Dec 2013
 * @author: Chris Fortune - http://cfortune.kics.bc.ca/
 */

session_start();
if($_POST['add_rev'] == 'add_rev')
{
	$revision_id = $_POST['revision_id'];
	$revision_num = $_POST['revision_num'];
	$sql = $_POST['sql'];
	$db = $_POST['db'];
	
	if( $revision_id != '' &&  $revision_num != '')
	{
		if( !is_dir('data/revisions/'.$db.'/'.$revision_id) )
			mkdir('data/revisions/'.$db.'/'.$revision_id);
		
		if( !file_exists('data/revisions/'.$db.'/'.$revision_id.'/'.$revision_num) )
		{
			$my_file = $revision_num.'.sql';
			
			$handle = fopen('data/revisions/'.$db.'/'.$revision_id.'/'.$my_file, 'w') or die('Cannot open file:  '.'data/revisions/'.$db.'/'.$revision_id.'/'.$my_file);
			
			if( $sql != '' )
				fwrite($handle, $sql);	
				
			$_SESSION['act'] = 'Reviosn created successfully#success';
		}
		else
			$_SESSION['act'] = 'Revisio number is already exists#error';
	}
	else
		$_SESSION['act'] = 'Revision ID and Revision number should not be empty.#error';
	
	echo '<script>window.location.href="index.php?db='.$db.'"</script>';	
}
else if($_REQUEST['act'] == 'delfile')
{
	$db = $_REQUEST['db'];
	$dir_file= $_REQUEST['val'];
	$del_file = unlink('data/revisions/'.$db.'/'.$dir_file);
	
	if($del_file)
		$_SESSION['act'] = 'File deleted successfully#success';
	else
		$_SESSION['act'] = 'File could not deleted#error';
	
		echo '<script>window.location.href="index.php?db='.$db.'"</script>';
}
else if($_REQUEST['act'] == 'delfolder')
{
	$db = $_REQUEST['db'];
	$folder = $_REQUEST['val'];
	$del_folder = rrmdir('data/revisions/'.$db.'/'.$folder);
	
	if($del_folder)
		$_SESSION['act'] = 'Folder could not deleted#error';
	else
		$_SESSION['act'] = 'Folder deleted successfully#success';
		
	echo '<script>window.location.href="index.php?db='.$db.'"</script>';
}
else
	echo '<script>window.location.href="index.php?db='.$db.'"</script>';	


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