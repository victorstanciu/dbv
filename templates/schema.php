<?php
	$selected = "tables";
	if (in_array($this->_action, array("tables", "views"))) {
		$selected = $this->_action;
	}
?>

<h2><?php echo __('Database schema'); ?></h2>
<div class="log"></div>

<?php if (isset($this->schema) && count($this->schema)) { ?>
    <form method="post" action="" class="nomargin" id="schema">
        <table class="table table-condensed table-striped table-bordered">
            <thead>
                <tr>
                    <th style="width: 13px;"><input type="checkbox" style="margin-top: 0;" /></th>
                    <th><?php echo _('Schema object'); ?></th>
                    <th style="text-align: center; width: 50px;"><?php echo __('In DB'); ?></th>
                    <th style="text-align: center; width: 50px;"><?php echo __('On disk'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->schema as $name => $flags) { ?>
                    <tr>
                        <td class="center">
                            <input type="checkbox" name="schema[]" value="<?php echo $name; ?>" id="object-<?php echo $name; ?>" style="margin-top: 0;" />
                        </td>
                        <td>
                            <label for="object-<?php echo $name; ?>">
                                <?php echo $name; ?>
                            </label>
                        </td>
                        <td style="text-align: center;" data-role="database">
                            <?php if (isset($flags['database'])) { ?>
                                <span class="label label-success"><?php echo __('YES'); ?></span>
                            <?php } else { ?>
                                <span class="label label-important"><?php echo __('NO'); ?></span>
                            <?php } ?>
                        </td>
                        <td style="text-align: center;" data-role="disk">
                            <?php if (isset($flags['disk'])) { ?>
                                <span class="label label-success"><?php echo __('YES'); ?></span>
                            <?php } else { ?>
                                <span class="label label-important"><?php echo __('NO'); ?></span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <button data-role="create" class="btn btn-primary btn-mini"><?php echo __('Push to database'); ?></button>
        <button data-role="export" class="btn btn-primary btn-mini"><?php echo __('Export to disk'); ?></button>
    </form>

    <script type="text/javascript">
        $('schema').select('button[data-role]').invoke('observe', 'click', function (event) {
            event.stop();

            var form = this.up('form');
            var data = form.serialize(true);

            form.disable();
            clear_messages('left');

            data.action = this.getAttribute('data-role');

            new Ajax.Request('index.php?a=schema', {
                parameters: data,
                onSuccess: function (transport) {
                    form.enable();

                    var response = transport.responseText.evalJSON();

                    if (typeof response.error != 'undefined') {
                        return APP.growler.error('<?php echo __('Error!'); ?>', response.error);
                    }

                    if (response.messages.error) {
                        render_messages('error', 'left', response.messages.error, '<?php echo __('The following errors occured:'); ?>');
                    }

                    if (response.messages.success) {
                        render_messages('success', 'left', response.messages.success, '<?php echo __('The following actions completed successfuly:'); ?>');
                    }

                    var items = response.items;

                    for (var name in items) {
                        var row = $('object-' + name).up('tr');
                        for (var key in items[name]) {
                            var label = row.down('[data-role="' + key + '"]').down('.label');
                                label.removeClassName('label-success').removeClassName('label-important');

                            if (items[name][key]) {
                                label.addClassName('label-success').update('YES');
                            } else {
                                label.addClassName('label-important').update('NO');
                            }
                        }
                    }

                    Effect.ScrollTo('log', {duration: 0.2});
                }
            });
        });
    </script>
<?php } else { ?>
	<div class="alert alert-info nomargin"><?php echo __('No schema objects found on disk or in the database.'); ?></div>
<?php } ?>
