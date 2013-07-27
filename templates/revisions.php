<?php if (isset($this->_revisions) && count($this->_revisions)) { ?>
<form method="post" action="" class="nomargin" id="revisions">
    <fieldset>
        <legend style="border-bottom: none;">
            <h2 class="pull-left"><?php echo __('Revisions'); ?></h2>
            <input type="button" class="btn btn-primary pull-right" value="Toggle ran" id="toggle-ran" />
            <input type="button" class="btn btn-primary pull-right" value="Mark as ran" id="mark-as-ran" />
            <input type="submit" class="btn btn-primary pull-right" value="Run selected revisions" />
            <br style="clear: both;" />
        </legend>
        <div class="log"></div>

        <table class="table table-condensed table-striped table-bordered">
            <thead>
                <tr>
                    <th style="width: 13px;"><input type="checkbox" style="margin-top: 0;" /></th>
                    <th><?php echo __('Revision ID'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $run_revisions = array_flip($this->run_revisions); ?>
                <?php foreach ($this->_revisions as $revision) { ?>
                    <?php
                        $ran = array_key_exists($revision, $run_revisions);
                        $class = array();
                        if ($ran) {
                            $class[] = 'ran';
                            $class[] = 'hide';
                        }

                        $files = $this->_getRevisionFiles($revision);
                    ?>
                    <tr id="rev_<?php echo $revision; ?>" data-revision="<?php echo $revision; ?>"<?php echo count($class) ? ' class="' . implode(' ', $class) . '"'  : ''; ?>>
                        <td class="center">
                            <input type="checkbox" name="revisions[]" value="<?php echo $revision; ?>"<?php echo $ran ? '' : ' checked="checked"'; ?> style="margin-top: 7px;" />
                        </td>
                        <td>
                            <h3 class="nomargin">
                                <a href="javascript:" class="revision-handle"><?php echo $revision; ?></a>
                            </h3>

                            <?php if (count($files)) { ?>
                                <div class="revision-files" style="display: none;">
                                    <?php $i = 0; ?>
                                    <?php foreach ($files as $file) { ?>
                                        <?php
                                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                                            $content = htmlentities($this->_getRevisionFileContents($revision, $file), ENT_QUOTES, 'UTF-8');
                                            $lines = substr_count($content, "\n");
                                        ?>
                                        <div id="revision-file-<?php echo $revision; ?>-<?php echo ++$i; ?>">
                                            <div class="log"></div>
                                            <div class="alert alert-info heading">
                                                <button data-role="editor-save" data-revision="<?php echo $revision; ?>" data-file="<?php echo $file; ?>" type="button" class="btn btn-mini btn-info pull-right" style="margin-top: -1px;"><?php echo __('Save file') ?></button>
                                                <strong class="alert-heading"><?php echo $file; ?></strong>
                                            </div>
                                            <textarea data-role="editor" name="revision_files[<?php echo $revision; ?>][<?php echo $file; ?>]" rows="<?php echo $lines + 1; ?>"><?php echo $content; ?></textarea>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </fieldset>
</form>
<?php } else { ?>
    <div class="alert alert-info nomargin">
        <?php echo __('No revisions in #{path}', array('path' => '<strong>' . REVISIONS_PATH . '</strong>')) ?>
    </div>
<?php } ?>
<script type="text/javascript">
    document.observe('dom:loaded', function () {
        var form = $('revisions');
        if (!form) {
            return;
        }

        var textareas = form.select('textarea');
        textareas.each(function (textarea) {
            textarea['data-editor'] = CodeMirror.fromTextArea(textarea, {
                mode: "text/x-mysql",
                tabMode: "indent",
                matchBrackets: true,
                autoClearEmptyLines: true,
                lineNumbers: true,
                theme: 'default'
            });
        });

        $$('.revision-handle').invoke('observe', 'click', function (event) {
            var element = event.findElement('.revision-handle');
            var container = element.up('td').down('.revision-files');
            if (container) {
                container.toggle();
                if (!container.visible()) {
                    return;
                }

                var textareas = container.select('textarea[data-role="editor"]');
                if (textareas) {
                    textareas.each(function (textarea) {
                        textarea['data-editor'].refresh();
                    });
                }
            }
        });

        $$('button[data-role="editor-save"]').invoke('observe', 'click', function (event) {
            var self = this;

            var editor = this.up('.heading').next('textarea')['data-editor'];
            var container = this.up('[id^="revision-file"]');

            this.disable();

            clear_messages(container);

            new Ajax.Request('index.php?a=saveRevisionFile', {
                parameters: {
                    revision: this.getAttribute('data-revision'),
                    file: this.getAttribute('data-file'),
                    content: editor.getValue()
                },
                onSuccess: function (transport) {
                    self.enable();

                    var response = transport.responseText.evalJSON();

                    if (response.error) {
                        return render_messages('error', container, response.error);
                    }

                    render_messages('success', container, response.message);
                }
            });
        });
        
        $$('#toggle-ran').invoke('observe', 'click', function (event) {
            var trs = form.select('tr.ran');
            if (trs) {
                trs.each(function (tr) {
                    tr.toggleClassName("hide");
                });
            }
        });
        
        $$('#mark-as-ran').invoke('observe', 'click', function (event) {
            var data = form.serialize(true);
            clear_messages(form);

            if (!data.hasOwnProperty('revisions[]')) {
                render_messages('error', 'revisions', "<?php echo __("You didn't select any revisions to run") ?>");
                Effect.ScrollTo('log', {duration: 0.2});
                return false;
            }
            
            form.disable();
            
            new Ajax.Request('index.php?a=markAsRan', {
                parameters: {
                    "revisions[]": data['revisions[]']
                },
                onSuccess: function (transport) {
                    form.enable();

                    var response = transport.responseText.evalJSON();

                    if (typeof response.error != 'undefined') {
                        return APP.growler.error('<?php echo _('Error!'); ?>', response.error);
                    }

                    if (response.messages.error) {
                        render_messages('error', 'revisions', response.messages.error, '<?php echo __('The following errors occured:'); ?>');
                    }

                    if (response.messages.success) {
                        render_messages('success', 'revisions', response.messages.success, '<?php echo __('The following actions completed successfuly:'); ?>');
                    }

                    for (i in response.revisions_marked) {
                        var row = $('rev_' + response.revisions_marked[i]);
                        row.addClassName('ran');
                        row.down('.revision-files').hide();
                        row.down('input[type="checkbox"]').checked = false;
                    }

                    Effect.ScrollTo('log', {duration: 0.2});
                }
            });
        });

        form.on('submit', function (event) {
            event.stop();

            var data = form.serialize(true);

            clear_messages(this);

            if (!data.hasOwnProperty('revisions[]')) {
                render_messages('error', this, "<?php echo __("You didn't select any revisions to run") ?>");
                Effect.ScrollTo('log', {duration: 0.2});
                return false;
            }

            form.disable();

            new Ajax.Request('index.php?a=revisions', {
                parameters: {
                    "revisions[]": data['revisions[]']
                },
                onSuccess: function (transport) {
                    form.enable();

                    var response = transport.responseText.evalJSON();

                    if (typeof response.error != 'undefined') {
                        return APP.growler.error('<?php echo _('Error!'); ?>', response.error);
                    }

                    if (response.messages.error) {
                        render_messages('error', 'revisions', response.messages.error, '<?php echo __('The following errors occured:'); ?>');
                    }

                    if (response.messages.success) {
                        render_messages('success', 'revisions', response.messages.success, '<?php echo __('The following actions completed successfuly:'); ?>');
                    }

                    for (i in response.run_revisions) {
                        var row = $('rev_' + response.run_revisions[i]);
                        row.addClassName('ran');
                        row.down('.revision-files').hide();
                        row.down('input[type="checkbox"]').checked = false;
                    }

                    Effect.ScrollTo('log', {duration: 0.2});
                }
            });
        });
    });
</script>