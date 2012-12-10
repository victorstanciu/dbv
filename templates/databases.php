<select name="database" onchange="window.location.href= 'index.php?activeDatabase='+this.value;">
	<option value="">Select your database</option>
	<?php		
		if (count($this->databases)) {
			$active = $this->_getAdapter()->getActiveDatabase();
			foreach ($this->databases as $dbname) {
				$revision = $this->_getCurrentRevision($dbname);
				$rev = $revision === 0 ? "" : " ({$revision})";
				$selected = $active == $dbname ? ' selected' : '';
				echo("<option value='{$dbname}' {$selected}>{$dbname} {$rev}</option>");	
		} 
	} ?>
</select>