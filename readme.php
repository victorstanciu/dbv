<?php include('header.php'); ?>

<div id="content" class="container" style="margin-top:10px; color:#FFFFFF;">
<pre>
/**
 * @package: Multi-db upgrade
 * @description: Multi-db upgrade, DB credentials manager, Run Selected Revision now works, Edit revision via web UI
 * @author: Chris Fortune - http://cfortune.kics.bc.ca/
 * @date: Dec 2013
 */
</pre>
<b>&lt;DOMAIN_NAME&gt;/ManageDB.php</b> - this file will allow user to manage all databases in the system.<br />
<ul>
	<li>User can create new DB.</li>
	<li>User can delete existing DB. (Use this option carefully. It will remove all schema and revisions related to that particular Database from disk.)</li>
	<li>When user creates a new DB, system will generate two directories having same name as DB name in the folders "data/revisions/" and "data/schema".</li>
	<li>All records of the databases are stored in the file "db.csv". User can use it by inserting records via browser and/or manually as well. Note: if there is a comma (,) in a password, then please write it in double quotes (" "). Example- ABCV","XYZ.</li>
</ul>

<b>Home Page</b>
<ul>
	<li>"Select Database" List box in the left top position allows user to switch between available databases in the system.</li>
	<li>The table "Database Schema" on left has three columns – "Schema Object" (for tables in the DB), "In DB" (indicating that table is in DB or not.) and "On disk" (indicating that schema file is in folder 'data/schema/&lt;DBNAME&gt;' or not).</li>
	<li>"Push to Database" button will run selected available schema in "data/schema" to DB.</li>
	<li>"Export to Disk" button will store schema of the selected tables to "data/schema" directory.</li>
	<li>"Revisions" on the right part of the page will display all revisions directory in the "data/revisions". [User has to make folders manually in the revision of that selected DB. And then he can have multiple files of queries in those folders.] User can write queries direct on the files and can save it by clicking on "Save File".</li>
	<li>"Run Selected Revisions" will run selected revision on the disk to the selected DB.</li>
</ul>

Note :- Application still works like original DBV project. Only change in usage is folder where we keep files for revisions and schemas. In Original application it stores them in data/schema and data/revision folder. Now we have data/db_name/schema and data/db_name/revision folders.

</div>

<?php include('footer.php'); ?>