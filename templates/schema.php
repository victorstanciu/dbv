<h2 class="pull-left"><?php echo __('Database schema'); ?></h2>
<input type="button" class="btn btn-primary pull-right" value="Toggle YESs" id="toggle-yes" />
<br style="clear:both;" />
<div class="log"></div>

<?php if (isset($this->schemas) && count($this->schemas)) { ?>
<div id="accordion">
    <?php $missing = array(); ?>
    <?php foreach ($this->schemas as $schema => $items) { ?>
    <?php $missing[$schema] = 0; ?>
    <h2 class="accordion_toggle btn-large btn-primary<?php echo ($schema == $this->active_schema ? " accordion_toggle_active" : ""); ?>"><?php echo $schema; ?></h2>
    <div class="accordion_content">
        <form method="post" action="" style="margin-bottom:20px;">
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
                    <?php foreach ($items as $name => $flags) { ?>
                    <tr<?php if (isset($flags['database']) && isset($flags['disk'])) { ?> class="yes hide"<?php } else { $missing[$schema]++; } ?>>
                        <td class="center">
                            <input type="checkbox" name="items[]" value="<?php echo $name; ?>" id="object-<?php echo $name; ?>" style="margin-top: 0;" />
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

            <button data-role="create" data-schema="<?php echo $schema; ?>" class="btn btn-primary btn-mini"><?php echo __('Push to database'); ?></button>
            <button data-role="export" data-schema="<?php echo $schema; ?>" class="btn btn-primary btn-mini"><?php echo __('Export to disk'); ?></button>
        </form>
    </div>
    <?php } ?>
</div>

<script type="text/javascript">
var missing = JSON;
<?php foreach ($missing as $schema => $amount) { ?>
missing.<?php echo $schema; ?> = <?php echo $amount; ?>;
<?php } ?>
$$('#toggle-yes').invoke('observe', 'click', function (event) {
    var trs = $('accordion').select('tr.yes');
    if (trs) {
        trs.each(function (tr) {
            tr.toggleClassName("hide");
        });
    }
});

$('accordion').select('button[data-role]').invoke('observe', 'click', function (event) {
    event.stop();

    var form = this.up('form');
    var data = form.serialize(true);

    form.disable();
    clear_messages('left');

    data.action = this.getAttribute('data-role');
    data.schema = this.getAttribute('data-schema');

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
<?php }
