/* global ajaxurl, tinymce, wpLinkL10n, tinyMCEPopup, setUserSetting, wpActiveEditor */
var msc;

(function($) {
    var dialog;

    msc = {
        shortcodeBase: {
            _name: '',
            _form: null,
            init: function() {
                return this;
            },
            getForm: function() {
                if (this._form === null) {
                    this._form = $('#msc-form-' + this._name);
                }
                return this._form;
            },
            _getValue: function(name) {
                return this.getForm().find('#msc-' + name + '-field').val();
            },
            _setValue: function(name, value) {
                return this.getForm().find('#msc-' + name + '-field').val(value);
            },
            open: function() {
                var form = this.getForm();
                if (form.hasClass('close')) {
                    form.prev('h2').trigger('click');
                }
            },
            update: function() {
                return msc.insertIntoMce(this.getCode());
            },
            encodeTags: function(str) {
                var tagsToReplace = {
                    '<': '{%',
                    '>': '%}'
                };
                return str.replace(/[<>]/g, function(tag) {
                    return tagsToReplace[tag] || tag;
                });
            },
            decodeTags: function(str) {
                var tagsToReplace = {
                    '{%': '<',
                    '%}': '>'
                };
                return str.replace(/{%|%}/g, function(tag) {
                    return tagsToReplace[tag] || tag;
                });
            }
        },
        shortcodes: {},
        addShortcode: function(object, base) {
            if (typeof base === 'undefined') {
                base = msc.shortcodeBase;
            } else {
                base = msc.shortcodes[base];
            }
            msc.shortcodes[object._name] = $.extend($.extend({}, base), object);
        },
        init: function() {
            dialog = $('#msc-dialog');

            // Toggle shortcode form
            dialog.on('click', 'h2', function(e) {
                $(this).next('.msc-form').slideToggle(300).toggleClass('close');
            });

            // Process shortcode form
            dialog.on('click', '.msc-submit', function(e) {
                e.preventDefault();
                return msc.getShortcode($(this).data('tag')).update();
            });

            // Init shortcodes
            $.each(msc.shortcodes, function() {
                this.init();
            });
        },
        getShortcode: function(tag) {
            return msc.shortcodes[tag];
        },
        insertIntoMce: function(content) {

            // Embed code to TinyMCE
            tinymce.activeEditor.execCommand('mceInsertRawHTML', false, content);

            // Close dialog
            tinyMCEPopup.close();
            tinymce.activeEditor.focus();

            return this;
        },
        _stringToObject: function(string) {
            var element = $('<div ' + string + '/>');
            return  element;
        },
        _getEditor: function() {
            return tinyMCEPopup.editor;
        }
    };

    msc.addShortcode({
        _name: 'age',
        getCode: function() {
            var date = this._getValue('date'), append = this._getValue('append');
            return '[moo_age date="' + date + '" append="' + append + '"]';
        },
        edit: function(params) {
            var paramsObj = msc._stringToObject(params);
            this.open();
            this._setValue('date', paramsObj.attr('date'));
            this._setValue('append', paramsObj.attr('append'));
        }
    });

    msc.addShortcode({
        _name: 'listing',
        _list: null,
        init: function() {
            var form = this.getForm();
            this._list = form.find('.msc-item-list');

            // Add item
            form.on('click', '.msc-add-item', $.proxy(this._addItem, this));
            // Add value to items
            form.on('click', '.msc-add-value', $.proxy(this._addValue, this));
            // Remove item
            form.on('click', '.msc-delete-item', $.proxy(this._deleteItem, this));
            // Remove values from item
            form.on('click', '.msc-delete-value', $.proxy(this._deleteValue, this));
            // Show filter parameters
            form.on('change', '.msc-item-value select', this._showFilterParams);
        },
        _addItem: function(e) {
            e.preventDefault();
            var item = $(this._getItemHtml());
            var values = this._list.find('.msc-item:first .msc-item-value-wrapper').length;
            for (var i = 0; i < values; i++) {
                item.find('.msc-item-values-wrapper').append(this._getValueHtml());
            }
            item.appendTo(this._list);
            this._reorderItems();
        },
        _addValue: function(e) {
            e.preventDefault();
            this._list.find('.msc-item-values-wrapper').each($.proxy(function(i, item) {
                $(item).append(this._getValueHtml(i));
            }, this));
            this._reorderItems();
        },
        _deleteItem: function(e) {
            e.preventDefault();
            $(e.currentTarget).parents('.msc-item').remove();
            this._reorderItems();
        },
        _deleteValue: function(e) {
            e.preventDefault();
            var index = $(e.currentTarget).parents('.msc-item-value-wrapper').data('index');
            this._list.find('.msc-item-value-wrapper-' + index).remove();
            this._reorderItems();
        },
        _showFilterParams: function() {
            $(this).siblings('.msc-filter-options').hide();
            $(this).siblings('.msc-filter-options-' + this.value).slideToggle();
        },
        _getItemHtml: function() {
            return "<li class='msc-item'>"
                    + "<div class='msc-item-label'><a href='' class='msc-delete-item'>" + msc._getEditor().getLang('minishotcodes.delete', 'Delete') + "</a><h4>" + msc._getEditor().getLang('minishotcodes.item', 'Item') + " 0</h4></div>"
                    + "<ul class='msc-item-values-wrapper'></ul>"
                    + "</li>";
        },
        _getValueHtml: function(index) {
            var html = "<li class='msc-item-value-wrapper'>"
                    + "<ul class='msc-item-value-inner'>"
                    + "<li class='msc-delete-value'><a href=''>" + msc._getEditor().getLang('minishotcodes.delete', 'Delete') + "</a></li>"
                    + "<li class='msc-item-value'><label>" + msc._getEditor().getLang('minishotcodes.value', 'Value') + " 0</label><input type='text' name='msc-item-value[0][]' /></li>"
                    + "</ul>"
                    + "</li>";

            if (index === 0) {
                html = $('<div>' + html + '</div>');
                html.find('.msc-item-value').append(this._getFilterHtml(this._list.data('filters'), true));
                return html.html();
            }

            return html;
        },
        _getFilterHtml: function(filters) {
            var filterHtml = '', paramsHtml = '';
            filterHtml += "<label>" + msc._getEditor().getLang('minishotcodes.filter', 'Filter') + " 0</label>";
            filterHtml += "<select name='msc-item-value-filter[0][]'>";
            $.each(filters, function(name, options) {
                filterHtml += "<option value='" + name + "'>" + options.label + "</option>";
                if (typeof options.params !== 'undefined') {
                    var params = '';
                    $.each(options.params, function(i, param) {
                        var value = '';
                        if (typeof options.defaults !== 'undefined' && typeof options.defaults[i] !== 'undefined') {
                            value = options.defaults[i];
                        }
                        params += "<label>" + param + "</label><input type='text' name='msc-item-value-param[0][]' value='" + value + "' />";
                    });
                    if (params) {
                        paramsHtml += "<div class='msc-filter-options msc-filter-options-" + name + "'><h4>" + msc._getEditor().getLang('minishotcodes.filter_params', 'Filter Parameters') + ":</h4>" + params + "</div>";
                    }
                }
            });
            filterHtml += "</select>" + paramsHtml;

            return filterHtml;
        },
        _reorderItems: function() {
            this._list.find('.msc-item-label h4').each(function(i, h4) {
                h4 = $(h4);
                h4.text(h4.text().replace(/\d+/g, i));
            });
            var valuesCount = this._list.find('.msc-item:first .msc-item-value-wrapper').length, counter = 0;
            this._list.find('.msc-item-value').each(function(i, values) {
                values = $(values);
                values.find('label').each(function(j, label) {
                    label.innerHTML = label.innerHTML.replace(/\d+/g, counter);
                });
                values.parents('.msc-item-value-wrapper')
                        .removeAttr('class')
                        .addClass('msc-item-value-wrapper msc-item-value-wrapper-' + counter)
                        .attr('data-index', counter);
                values.find('input').removeAttr('name').attr('name', 'msc-item-value[' + counter + '][]');
                values.find('select').removeAttr('name').attr('name', 'msc-item-value-filter[' + counter + '][]');
                values.find('.msc-filter-options input').removeAttr('name').attr('name', 'msc-item-value-param[' + counter + '][]');
                counter++;
                if (counter >= valuesCount) {
                    counter = 0;
                }
            });
        },
        _getCode: function() {
            var shortcode = '[moo_' + this._name + ' ', me = this;

            var options = ['format', 'class', 'tag', 'max', 'sort', 'before', 'after', 'delimiter'];
            $.each(options, function(i, option) {
                var value = me._getValue(option);
                if (typeof value !== 'undefined') {
                    if (option == 'format') {
                        value = me.encodeTags(value);
                    }
                    shortcode += option + '="' + value.replace(/(\r\n|\n|\r)/gm, " ") + '" ';
                }
            });

            return shortcode;
        },
        getCode: function() {
            var shortcode = this._getCode(), delimiter = this._getValue('delimiter');
            this.getForm().find('.msc-item').each(function(i, item) {
                var values = $(item).find('input[name^="msc-item-value["]').map(function() {
                    return this.value;
                }).get().join(delimiter);
                shortcode += 'item' + i + '="' + values + '" ';

                if (i === 0) {
                    $(item).find('select[name^="msc-item-value-filter"]').each(function(i, filter) {
                        shortcode += 'filter' + i + '="' + filter.value + ':';

                        shortcode += $(filter).find('.msc-filter-options-' + filter.value + ' input').map(function() {
                            return this.value;
                        }).get().join(':');

                        // Remove : from the end of the string
                        shortcode = shortcode.replace(new RegExp("[:]+$", "g"), "");

                        shortcode += '" ';
                    });
                }
            });

            shortcode += ']';

            return shortcode;
        },
        _edit: function(params) {
            var paramsObj = msc._stringToObject(params), me = this;
            var options = ['format', 'class', 'tag', 'max', 'sort', 'before', 'after', 'delimiter'];
            $.each(options, function(i, option) {
                var value = paramsObj.attr(option);
                if (typeof value !== 'undefined') {
                    if (option == 'format') {
                        value = me.decodeTags(value);
                        console.log(value)
                    }
                    console.log(value)
                    me._setValue(option, value);
                }
            });
            return paramsObj;
        },
        edit: function(params) {
            this.open();
            this._edit(params);
            var paramsObj = msc._stringToObject(params);
            var form = this.getForm();

            // Clear any current items and values
            form.find('.msc-item-list').html('');

            // Create the items and values
            var delimiter = paramsObj.attr('delimiter');

            // Generate the items and values elements
            $.each(paramsObj.get(0).attributes, function(i, attrib) {
                var name = attrib.name;
                if (name.indexOf('item') !== -1) {
                    form.find('.msc-add-item').trigger('click');
                } else if (name.indexOf('filter') !== -1) {
                    form.find('.msc-add-value').trigger('click');
                }
            });

            // Populate the items and values elements
            form.find('.msc-item').each(function(i, item) {
                var itemString = paramsObj.attr('item' + i);
                var values = itemString.split(delimiter);
                var itemElement = $(item);

                $.each(values, function(j, value) {
                    itemElement.find('input[name^="msc-item-value[' + j + '][]"]').val(value);
                });

                if (i === 0) {
                    itemElement.find('select[name^="msc-item-value-filter"]').each(function(i, filter) {
                        var filterString = paramsObj.attr('filter' + i);
                        var filterParams = filterString.split(':');
                        var filterElement = $(filter);
                        var filterName = filterParams.shift();
                        filterElement.val(filterName);

                        itemElement.find('.msc-filter-options-' + filterName).slideDown();
                        $.each(filterParams, function(p, param) {
                            itemElement.find('.msc-filter-options-' + filterName + ' input[name^="msc-item-value-param[' + j + '][]"]').val(param);
                        });
                    });
                }
            });
        }
    });

    msc.addShortcode({
        _name: 'instagram',
        init: function() {
        },
        getCode: function() {
            var shortcode = this._getCode(),
                    clientId = this._getValue('client_id'),
                    userId = this._getValue('user_id');

            shortcode += 'client_id="' + clientId + '" user_id="' + userId + '"';
            shortcode += ']';

            return shortcode;
        },
        edit: function(params) {
            this.open();
            var paramsObj = this._edit(params);
            this._setValue('client_id', paramsObj.attr('client_id'));
            this._setValue('user_id', paramsObj.attr('user_id'));
        }
    }, 'listing');

    msc.addShortcode({
        _name: 'posts',
        init: function() {
            var form = this.getForm();
            this._list = form.find('.msc-item-list');

            // Add item
            form.on('click', '.msc-add-filter', $.proxy(this._addFilter, this));
            // Remove item
            form.on('click', '.msc-delete-value', $.proxy(this._deleteFilter, this));
            // Show filter parameters
            form.on('change', '.msc-item-value select', this._showFilterParams);
        },
        _addFilter: function(e) {
            e.preventDefault();
            var item = $(this._getValueFilterHtml());
            item.appendTo(this._list);
            this._reorderItems();
        },
        _deleteFilter: function(e) {
            return this._deleteValue(e);
        },
        _reorderItems: function() {
            var valuesCount = this._list.find('.msc-item-value-wrapper').length, counter = 0;
            this._list.find('.msc-item-value-wrapper').each(function(i, value) {
                value = $(value);
                value.find('label').each(function(j, label) {
                    label.innerHTML = label.innerHTML.replace(/\d+/g, counter);
                });
                value.removeAttr('class')
                        .addClass('msc-item-value-wrapper msc-item-value-wrapper-' + counter)
                        .attr('data-index', counter);
                value.find('input').removeAttr('name').attr('name', 'msc-item-value[' + counter + '][]');
                value.find('select').removeAttr('name').attr('name', 'msc-item-value-filter[' + counter + '][]');
                value.find('.msc-filter-options input').removeAttr('name').attr('name', 'msc-item-value-param[' + counter + '][]');
                counter++;
                if (counter >= valuesCount) {
                    counter = 0;
                }
            });
        },
        _getValueFilterHtml: function() {
            var html = "<li class='msc-item-value-wrapper'>"
                    + "<ul class='msc-item-value-inner'>"
                    + "<li class='msc-delete-value'><a href=''>" + msc._getEditor().getLang('minishotcodes.delete', 'Delete') + "</a></li>"
                    + "<li class='msc-item-value'></li>"
                    + "</ul>"
                    + "</li>";

            html = $('<div>' + html + '</div>');
            html.find('.msc-item-value').append(this._getFilterHtml(this._list.data('filters'), false));
            return html.html();
        },
        getCode: function() {
            var shortcode = this._getCode();

            this.getForm().find('.msc-item-value-wrapper').each(function(i, item) {
                var select = $(item).find('select');
                shortcode += 'filter' + i + '="' + select.val() + ':';

                shortcode += $(item).find('.msc-filter-options-' + select.val() + ' input').map(function() {
                    return this.value;
                }).get().join(':');

                // Remove : from the end of the string
                shortcode = shortcode.replace(new RegExp("[:]+$", "g"), "");

                shortcode += '" ';
            });

            shortcode += ']';

            return shortcode;
        },
        edit: function(params) {
            this.open();
            var paramsObj = this._edit(params);

            var form = this.getForm();

            // Clear any current items and values
            form.find('.msc-item-list').html('');

            // Create the items and values
            var itemIndex = 0;
            while (true) {
                if (!paramsObj.is("[filter" + itemIndex + "]")) {
                    break;
                }

                form.find('.msc-add-filter').trigger('click');

                itemIndex++;
            }

            // Populate the items and values
            form.find('.msc-item-value-wrapper').each(function(i, item) {
                var filterString = paramsObj.attr('filter' + i);
                var filterParams = filterString.split(':');
                var filterElement = $(item).find('select');
                var filterName = filterParams.shift();
                filterElement.val(filterName);
                $(item).find('.msc-filter-options-' + filterName).slideDown();
                $.each(filterParams, function(p, param) {
                    $(item).find('.msc-filter-options-' + filterName + ' input[name^="msc-item-value-param[' + i + '][]"]').val(param);
                });
            });
        }
    }, 'listing');

    $(document).ready(msc.init);
})(jQuery);
