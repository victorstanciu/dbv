<?php if (count($this->_log)) { 
    foreach ($this->_log as $message) { 
    	echo "<div class='alert alert-{$message['type']}'>{$message['message']}</div>";
    }
}