(function() {
    tinymce.create('tinymce.plugins.minishotcodes', {
        init: function(ed, url) {
            var me = this;

            // Register the plugin command
            ed.addCommand('mceMinishotcodes', function() {
                ed.windowManager.open({
                    id: 'msc-dialog',
                    title: ed.getLang('minishotcodes.popupTitle'),
                    width: 700 + ed.getLang('minishotcodes.delta_width', 0),
                    height: 500 + ed.getLang('minishotcodes.delta_height', 0),
                    wpDialog: true
                }, {
                    plugin_url: url
                });
            });

            // Register example button
            ed.addButton('minishotcodes_button', {
                title: ed.getLang('minishotcodes.popupTitle'),
                cmd: 'mceMinishotcodes',
                image: url + '/btn.png'
            });

            // Replace shortcode before editor content set.
            ed.onBeforeSetContent.add(function(ed, o) {
                o.content = me._showPlaceholder(o.content, url);
            });

            // Add a double click event to the placeholder image to edit shortcode.
            ed.onLoadContent.add(function(ed, o) {
                tinymce.dom.Event.add(ed.dom.select('.msc-placeholder'), 'dblclick', function(e) {
                    tinyMCE.activeEditor.execCommand('mceMinishotcodes');
                    e.currentTarget.title.replace(/moo_([a-z]+)\s+(.*)/gi, function(m, name, params) {
                        msc.getShortcode(name).edit(params);
                    });
                });
            });

            // On insert replace shortcode with placeholder image.
            ed.onExecCommand.add(function(ed, cmd) {
                if (cmd === 'mceInsertContent') {
                    tinyMCE.activeEditor.setContent(me._showPlaceholder(tinyMCE.activeEditor.getContent(), url));
                }
            });

            // On save post replace placeholder with shortcode.
            ed.onPostProcess.add(function(ed, o) {
                if (o.get) {
                    o.content = me._getShortcode(o.content);
                }
            });
        },
        createControl: function(n, cm) {
            return null;
        },
        getInfo: function() {
            return {
                longname: 'Mini Shortcodes plugin',
                author: 'Mohamed Alsharaf',
                authorurl: 'http://my.geek.nz',
                version: "1.0"
            };
        },
        _showPlaceholder: function(co, url) {
            // Replace shortcode with placeholder image.
            return co.replace(/\[moo_([^\]]*)\]/g, function(a, b) {
                return '<img src="' + url + '/icon.png" class="msc-placeholder mceItem" title="moo_' + tinymce.DOM.encode(b) + '" />';
            });
        },
        _getShortcode: function(co) {
            // Replace placeholder image with the shortcode.
            function getAttr(s, n) {
                n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
                return n ? tinymce.DOM.decode(n[1]) : '';
            }
            return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a, im) {
                var cls = getAttr(im, 'class');

                if (cls.indexOf('msc-placeholder') !== -1) {
                    return '<p>[' + tinymce.trim(getAttr(im, 'title')) + ']</p>';
                }

                return a;
            });
        }
    });

    // Register plugin
    tinymce.PluginManager.add('minishotcodes', tinymce.plugins.minishotcodes);
})();
