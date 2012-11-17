<?php if (count($this->_log)) { ?>
    <?php foreach ($this->_log as $message) { ?>
        <div class="alert alert-<?php echo $message['type']; ?>">
            <?php echo $message['message']; ?>
        </div>
    <?php } ?>
<?php } ?>
