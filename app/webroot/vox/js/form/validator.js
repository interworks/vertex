/**
 * Form validation features
 *
 * @author Josh Varner <josh.varner@gmail.com>
 * @depends jquery.js
 * @depends swfobject.js
 */

/*jslint browser:true, white:false, onevar:false, nomen:false, bitwise:false, plusplus:false */
/*globals window, jQuery */

if (!this.vox) {
    this.vox = {};
}

(function ($, vox) {

$.fn.validator = function (options) {
    var isMethodCall = (typeof options === 'string'),
        args = Array.prototype.slice.call(arguments, 0),
        name = 'validator';

    // handle initialization and non-getter methods
    return this.each(function () {
        var instance = $.data(this, name);

        // prevent calls to internal methods
        if (isMethodCall && options.substring(0, 1) === '_') {
            return this;
        }

        if (!instance && !isMethodCall) {
            // constructor
            $.data(this, name, new $.validator(this))._init(args);
        } else if (instance && isMethodCall && $.isFunction(instance[options])) {
            // method call
            instance[options].apply(instance, args.slice(1));
        }
    });
};

$.validator = function (element) {
    if (!element || !element.nodeType) {
        var el = $('<div>');
        return el.validator.apply(el, Array.prototype.slice.call(arguments, 0));
    }

    this.container = $(element);
};

/**
 * Static members
 **/
$.extend($.validator, {
    defaults: {
        errorFlagHtml: false, /*'<span class="form-errorIcon ui-icon ui-icon-alert"></span>', */
        errorOverviewContainer: false,
        validators: [],
        excludeFields: [],
        preValidation: false,
        onNoErrorsFound: false,
        showMessages: false,
        autoForm: false,
        enabled: true
    }
});

/**
 * Non-static members
 **/
$.extend($.validator.prototype, {
    container: false,
    options: {},

    _init: function (args) {
        var self = this;

        args = $.isArray(args) ? args : [];

        args.unshift($.validator.defaults);
        args.unshift({});

        this.options = $.extend.apply($, args);

        /* Sometimes the state doesn't reset on a back button press (which we should discourage, but
           can't prevent) */
        this.container.find(':submit').removeAttr('disabled');
        this.container.bind('submit', function (ev) {
            return self._submitHandler.call(self, this, ev);
        });

    },

    addError: function ($item, message) {
        $item = $item.is('.formItem') ? $item : $item.closest('.formItem');

        $item
            .addClass('ui-state-error')
            .find('.form-errorIcon').remove();

        if (this.options.showMessages && message) {
            var errorList = $item.find('.errors');

            if (!errorList.size()) {
                errorList = $('<ul class="errors"></ul>').insertAfter($item.find('.formInputItem'));
            }

            errorList.append($('<li>', { text: message }));
        }

        if (this.options.errorFlagHtml) {
            $item.after($(this.options.errorFlagHtml));
        }
    },

    _removeError: function ($item) {
        $item.closest('.formItem')
            .removeClass('ui-state-error')
            .find('.form-errorIcon').remove().end()
            .find('.errors').remove();
    },

    validate: function () {
        var nonBlankFields = [],
            field = false,
            key = false,
            validator = false,
            x = 0,
            c = 0,
            self = this,
            stopNow = false;

        if ($.isFunction(this.options.preValidation)) {
            this.options.preValidation(this);
        }

        $(this.options.errorOverviewContainer).hide();

        this.container.find('.errors, .form-errorIcon').remove().end()
            .find('.ui-state-error').removeClass('ui-state-error');

        if (false !== this.options.autoForm) {
            $.each(this.options.autoForm.getRequiredElements(), function (i, item) {
                var val = '';

                if (item.type === 'checkbox') {
                    val = item.element.find(':input:checked').length ? item.checkedValue : '';
                } else if (item.type === 'radio') {
                    val = item.element.find(':input[name="' + item.name + '"]:checked').val();
                } else {
                    val = item.element.find(':input').val();
                }

                if (!val || $.trim(val).length <= 0) {
                    self.addError(item.element, 'Value is required and cannot be empty');
                }
            });
        } else {
            this.container.find('label.required').each(function () {
                var labelFor = $(this).attr('for');

                if ($.inArray(labelFor, self.options.excludeFields) !== -1) {
                    return;
                }

                nonBlankFields.push(labelFor);
            });

            for (x = 0, c = nonBlankFields.length; x < c; x++) {
                field = this.container.find('#' + nonBlankFields[x]);

                if (field.length && $.trim(field.val()).length <= 0) {
                    this.addError(field, "Value is required and cannot be empty");
                }
            }
        }

        for (key in this.options.validators) {
            field = key;

            if (typeof field === 'string') {
                field = this.container.find(field);
            }

            if ($.inArray(field.attr('id'), self.options.excludeFields) !== -1) {
                continue;
            }

            if (field.closest('.formItem').hasClass('ui-state-error')) {
                continue;
            }

            for (x = 0, c = this.options.validators[key].length; x < c; x++) {
                validator = this.options.validators[key][x];

                if ($.isArray(validator)) {
                    validator = new vox.validator[validator[0]](validator[1]);
                }

                if (!validator.isValid(field)) {
                    this.addError(field, 'getMessage' in validator ? validator.getMessage() : false);

                    if (!('options' in validator) || validator.options.breakOnFail) {
                        stopNow = true;
                        break;
                    }
                }
            }

            if (stopNow) {
                break;
            }
        }

        if (this.container.find('.ui-state-error').length === 0) {
            if ($.isFunction(this.options.onNoErrorsFound)) {
                this.options.onNoErrorsFound(this);
            }
            return true;
        }

        $(this.options.errorOverviewContainer).show();
        return false;
    },

    enable: function () {
        this.options.enabled = true;
    },

    disable: function () {
        this.options.enabled = false;
    },

    /**
     * Validate the form before submission. If the form is not valid, cancel the submit.
     */
    _submitHandler: function (element, ev) {
        // Validate the form
        if (this.options.enabled && !this.validate()) {
            ev.preventDefault();
            return false;
        }

        // We're going to allow submission. Disable submit button (don't want to charge them twice)
        // this.container.find(':submit').attr('disabled', 'disabled');
    }
});

(function () {

// From John Resig's inheritance blog post
var initializing = false,
    fnCallsSuper = function () { return true; },
    fnTest = function (prop, sup) {
        return (typeof prop === 'function' && typeof sup === 'function' && fnCallsSuper(prop));
    };

// Testing a regular expression on a function will determine if the browser can treat the
// function body as a string (decompilation). If it does, we'll change fnCallsSuper to search
// the provided function for calls to _super, so that if there aren't any, we don't waste time
// setting up the necessary stuff for it to just go unused.
if (/xyz/.test(function () { xyz; })) {
    fnCallsSuper = function (val) { return (/\b_super\n/).test(val); };
}

// The base Class implementation (does nothing)
var Class = function () {};

// Create a new Class that inherits from this class
Class.extend = function (prop) {
    var _super = this.prototype;

    // Instantiate a base class (but only create the instance,
    // don't run the init constructor)
    initializing = true;
    var prototype = new this();
    initializing = false;

    // Copy the properties over onto the new prototype
    for (var name in prop) {
        // Check if we're overwriting an existing function
        if (fnTest(prop[name], _super[name])) {
            prototype[name] = (function (name, fn) {
                return function () {
                    var tmp = this._super;

                    // Add a new ._super() method that is the same method
                    // but on the super-class
                    this._super = _super[name];

                    // The method only need to be bound temporarily, so we
                    // remove it when we're done executing
                    var ret = fn.apply(this, arguments);
                    this._super = tmp;

                    return ret;
                };
            })(name, prop[name]);
        } else {
            prototype[name] = prop[name];
        }
    }

    // The dummy class constructor
    function Class() {
        // All construction is actually done in the init method
        if (!initializing && this.init) {
            this.init.apply(this, arguments);
        }
    }

    // Populate our constructed prototype object
    Class.prototype = prototype;

    // Enforce the constructor to be what we expect
    Class.constructor = Class;

    // And make this class extendable
    Class.extend = arguments.callee;

    // Save static properties
    for (var name in this) {
        if (name !== 'prototype' && name !== 'constructor' && name !== 'extend' && name !== 'self') {
            Class[name] = this[name];
        }
    }

    // Provide a reference to the static properties
    Class.prototype.self = Class;

    return Class;
};


var validator = {};

var base = Class.extend({
    init: function (opts) {
        this.options = $.extend({}, this.self.defaults, opts || {});
    },
    getMessage: function () {
        return this.options.message;
    }
});

base.defaults = {
    message: false,
    breakOnFail: true
};

validator.selector = (function () {
    // Constructor
    var selector = function (selectorStr) {
        if (selectorStr) {
            this._selector = selectorStr;
        }
    };

    // Non-static Members
    $.extend(selector.prototype, {
        _selector: '*',


        isValid: function (item) {
            return (item.is(this._selector));
        }
    });

    return selector;
})();

validator.regex = (function () {
    // Constructor
    var regex = function (expression) {
        if (expression) {
            this._expression = expression;
        }
    };

    // Non-static Members
    $.extend(regex.prototype, {
        _expression: /.*/,

        isValid: function (item) {
            return (this._expression.test(item.val()));
        }
    });

    return regex;
})();

validator.regexInv = (function () {
    // Constructor
    var regexInv = function (expression) {
        if (expression) {
            this._expression = expression;
        }
    };

    // Non-static Members
    $.extend(regexInv.prototype, {
        _expression: /.*/,

        isValid: function (item) {
            return (false === this._expression.test(item.val()));
        }
    });

    return regexInv;
})();


validator.zipcode = (function () {
    var zipcode = function () {
        validator.regex.prototype.constructor.call(this, /^\d{5}(\-\d{4})?$/);
    };

    $.extend(zipcode.prototype, validator.regex.prototype);

    return zipcode;
})();

validator.phoneNumber = (function () {
    var phoneNumber = function () {
        validator.regex.prototype.constructor.call(this, /^1?\D*\d{3}\D*\d{3}\D*\d{4}.*$/);
    };

    $.extend(phoneNumber.prototype, validator.regex.prototype);

    return phoneNumber;
})();

validator.emailAddress = (function () {
    var emailAddress = function () {
        validator.regex.prototype.constructor.call(this, /^.+@[^@]+\.[^@\.]+$/);
    };

    $.extend(emailAddress.prototype, validator.regex.prototype);

    return emailAddress;
})();

validator.ccNum = (function () {
    // Constructor
    var ccNum = function () {};

    var luhnCheck = function (number) {
        var parity = number.length % 2,
            total  = 0;

        // Loop through each digit and do the maths
        for (var i = 0, c = number.length; i < c; i++) {
            var digit = number.charAt(i);
            // Multiply alternate digits by two
            if (i % 2 === parity) {
                digit *= 2;
                // If the sum is two digits, add them together (in effect)
                if (digit > 9) {
                    digit -= 9;
                }
            }

            total += parseInt(digit, 10);
        }

        // If the total mod 10 equals 0, the number is valid
        if (total % 10 === 0) {
            return true;
        } else {
            return false;
        }
    };

    // Non-static Members
    $.extend(ccNum.prototype, {
        isValid: function (item) {
            var value = item.val();

            // Dummy test number
            if (value === '4222222222222222') {
                return true;
            }

            if (!(/^\d{13,16}$/.test(value))) {
                return false;
            }

            if (!luhnCheck(value)) {
                return false;
            }

            return true;
        }
    });

    return ccNum;
})();

validator.stringLength = base.extend({
    isValid: function (item) {
        var length = item.val().length,
            o = this.options;

        if (o.min !== -1 && length < o.min) {
            return false;
        }

        if (o.max !== -1 && length > o.max) {
            return false;
        }

        return true;
    }
});

validator.stringLength.defaults = $.extend({}, base.defaults, {
    min: -1,
    max: -1
});

validator.greaterThan = (function () {
    // Constructor
    var greaterThan = function (amount) {
        if (typeof amount !== 'undefined') {
            this._amount = amount;
        }
    };

    // Non-static Members
    $.extend(greaterThan.prototype, {
        _amount: 0,

        isValid: function (item) {
            return (item.val() > this._amount);
        }
    });

    return greaterThan;
})();

validator.floatingPoint = (function () {
    // Constructor
    var floatingPoint = function () {};

    // Non-static Members
    $.extend(floatingPoint.prototype, {
        isValid: function (item) {
            var value = item.val(), valueFiltered = parseFloat(value);

            return (!isNaN(valueFiltered) && value === valueFiltered);
        }
    });

    return floatingPoint;
})();

validator.mustMatch = base.extend({
    isValid: function (item) {
        var match = this.options.match,
            localVal = $(item).val(),
            isMatch = !this.options.inverse;

        if (!match) {
            return !isMatch;
        } else if ($.isFunction(match)) {
            match = match(item);
        }

        if (typeof match === 'string') {
            if (this.options.type === 'selector') {
                match = $(match);
            } else {
                return (localVal === match ? isMatch : !isMatch);
            }
        }

        if (!match.size()) {
            return !isMatch;
        }

        match.each(function () {
            if ($(this).val() !== localVal) {
                isMatch = !isMatch;
                return false;
            }
        });

        return isMatch;
    }
});

validator.mustMatch.defaults = $.extend({}, base.defaults, {
    message: 'Values must match',
    match: false,
    type: 'selector',
    inverse: false
});

validator.callback = (function () {
    // Constructor
    var callback = function (fn) {
        this._fn = fn;
    };

    // Non-static Members
    $.extend(callback.prototype, {
        _fn: false,

        isValid: function (item) {
            return (true === this._fn(item));
        }
    });

    return callback;
})();

vox.validator = validator;

})();

})(jQuery, this.vox);
