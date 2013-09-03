/**
 * o      |                              |         
 * .,---.-|-- ,---.,---..     .,---.,---.|,--,---.
 * ||   | |   |---'|    |  |  || o ||    |   `---.
 * ``   ' `-- `--  `    `--'--'`---'`    '`--`---'
 * vox javascript
 */

/*jslint white:false */
/*globals $, CKEDITOR, document, vox, window */

if (typeof vox == "undefined" || !vox) {
    var vox = {};
}

(function () {

var cache = {};
var support = {
    autofocus: function (fix) {
        if (!('autofocus' in cache)) {
            cache.autofocus = ('autofocus' in document.createElement('input'));
        }

        if (fix && !cache.autofocus) {
            $(function () {
                $('[autofocus]:first').focus();
            });
        }

        return cache.autofocus;
    },
    
    placeholder: function (fix) {
        if (!('placeholder' in cache)) {
            cache.placeholder = ('placeholder' in document.createElement('input'));
        }

        if (fix && !cache.placeholder) {
            $(function () {
                $('[placeholder]').live({
                        'iwmultigroupchildadd': function () {
                            var el = $(this);
                            el.val(el.attr('placeholder'));
                        },
                        'focus keydown': function () {
                            var el = $(this);
                            
                            if (el.hasClass('placeholder') && el.val() === el.attr('placeholder')) {
                                el.val('');
                                el.removeClass('placeholder');
                            }
                        },
                        blur: function () {
                            var el = $(this);
                            
                            if (!el.val().length) {
                                el.addClass('placeholder');
                                el.val(el.attr('placeholder'));
                            }
                        }
                    })
                    .blur()
                    .parents().live({
                        submit: function () {
                            $(this).find('[placeholder].placeholder').val('');
                        }
                    });
            });
        }

        return cache.placeholder;
    },
        
    fix: function (features) {
        if (!$.isArray(features)) {
            features = features.split(' ');
        }
        
        $.each(features, function (i, val) {
            support[val](true);
        });
    }
};

vox['support'] = support;

}());
