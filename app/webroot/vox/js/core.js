/*jslint white:false */
/*globals $, CKEDITOR, document, vox, window */

if (typeof vox == "undefined" || !vox) {
    var vox = {};
}

$.extend(vox, {
    /**
     * Repeat string `str` `num` times
     *
     * Originally by:
     * Alexandru Marasteanu <http://alexei.417.ro/>
     * sprintf v.0.4 from Google Code (GPL)
     */
    strRepeat: function (str, num) {
        var o = [];
        
        while (num > 0) {
            o[--num] = str;
        }
        
        return o.join('');
    },
    
    /**
     * Format a string like other languages' `sprintf()` functions
     *
     * Originally by:
     * Alexandru Marasteanu <http://alexei.417.ro/>
     * sprintf v.0.4 from Google Code (GPL)
     */
    sprintf: function () {
        var i = 0, a, f = arguments[i++], o = [], m, p, c, x;
        while (f) {
            if (null !== (m = /^[^\x25]+/.exec(f))) {
                o.push(m[0]);
            } else if (null !== (m = /^\x25{2}/.exec(f))) {
                o.push('%');
            } else if (null !== (m = /^\x25(?:(\d+)\$)?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-fosuxX])/.exec(f))) {
                if ((null === (a = arguments[m[1] || i++])) || (undefined === a)) {
                    throw 'Too few arguments.';
                } else if (/[^s]/.test(m[7]) && (typeof(a) !== 'number')) {
                    throw 'Expecting number but found ' + typeof(a);
                }

                switch (m[7]) {
                    case 'b': a = a.toString(2); break;
                    case 'c': a = String.fromCharCode(a); break;
                    case 'd': a = parseInt(a, 10); break;
                    case 'e': a = m[6] ? a.toExponential(m[6]) : a.toExponential(); break;
                    case 'f': a = m[6] ? parseFloat(a).toFixed(m[6]) : parseFloat(a); break;
                    case 'o': a = a.toString(8); break;
                    case 's': a = ((a = String(a)) && m[6] ? a.substring(0, m[6]) : a); break;
                    case 'u': a = Math.abs(a); break;
                    case 'x': a = a.toString(16); break;
                    case 'X': a = a.toString(16).toUpperCase(); break;
                }
                a = (/[def]/.test(m[7]) && m[2] && a > 0 ? '+' + a : a);
                c = m[3] ? m[3] == '0' ? '0' : m[3].charAt(1) : ' ';
                x = m[5] - String(a).length;
                p = m[5] ? vox.strRepeat(c, x) : '';
                o.push(m[4] ? a + p : p + a);
            } else {
                throw 'Unrecoverable sprintf error';
            }

            f = f.substring(m[0].length);
        }
        return o.join('');
    },    
    
    getDigit: function(str, i) {
        return parseInt(str.charAt(i), 10);
    },
    
    /**
     * Pad a string from the left
     */
    strPadLeft: function(str, pad, len) {
        if (typeof str !== 'string') {
            str = '' + str;
        }
        
        len -= str.length;
        
        return (len ? vox.strRepeat(pad, len) + str : str);
    },
    
    /**
     * Return the ordinal indicator for a given number
     */
    getOrdinalIndicator: function (num) {
        if (typeof num !== 'number') {
            num = parseInt(num, 10);
        }
        
        var lastDigit = Math.abs(num % 10);
        
        if (1 === lastDigit) {
            return 'st';
        } else if (2 === lastDigit) {
            return 'nd';
        } else if (3 === lastDigit) {
            return 'rd';
        }        

        return 'th';
    },
    
    dateStrings: {
        daysOfWeek: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
        daysOfWeekAbbrev: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
        months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
        monthsAbbrev: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        amPm: {
            amLower: 'am',
            pmLower: 'pm',
            amUpper: 'AM',
            pmUpper: 'PM'
        }
    },

    /**
     * Simplified padding mechanism for formatDate()
     */
    _datePadTo2: function (num) {
        if (typeof num !== 'number') {
            num = parseInt(num, 10);
        }
        
        return (num < 10 ? '0' + num : '' + num);
    },

    /**
     * Format a date similar to PHP's `date()` function
     *
     * @see http://www.php.net/manual/en/function.date.php
     */
    formatDate: function(str, dt) {
        var ret = [],
            arr = str.split(''),
            m = null,
            strings = this.dateStrings;

        if (!dt) {
            dt = new Date();
        } else if (typeof dt === 'string') {
            // Parse common MySQL date format, or let Date() try to parse the string
            if (null !== (m = /(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2}):(\d{2})/.exec(dt))) {
                dt = new Date(m[1], m[2]-1, m[3], m[4], m[5], m[6]);
            } else {
                dt = new Date(dt);
            }
        } else if ((typeof dt === 'object') && ('date' in dt)) {
            dt = new Date(dt.date);
        } else if (typeof dt === 'number') {
            dt = new Date(dt);
        }
        
        var v = {
            hours: dt.getHours(),
            date: dt.getDate(),
            day: dt.getDay(),
            month: dt.getMonth(),
            year: dt.getFullYear(),
            minutes: dt.getMinutes(),
            seconds: dt.getSeconds(),
            tzOffset: dt.getTimezoneOffset()
        };

        for (var x = 0; x < arr.length; x++) {
            switch (arr[x]) {
                case 'd':
                    ret.push(this._datePadTo2(v.date));
                    break;
                case 'D':
                    ret.push(strings.daysOfWeekAbbrev[v.day]);
                    break;
                case 'j':
                    ret.push(v.date);
                    break;
                case 'l':
                    ret.push(strings.daysOfWeek[v.day]);
                    break;
                case 'N':
                    ret.push(v.day || '7');
                    break;
                case 'S':
                    ret.push(this.getOrdinalIndicator(v.date));
                    break;
                case 'w':
                    ret.push(v.day);
                    break;
                case 'F':
                    ret.push(strings.months[v.month]);
                    break;
                case 'm':
                    ret.push(this._datePadTo2(v.month + 1));
                    break;
                case 'M':
                    ret.push(strings.monthsAbbrev[v.month]);
                    break;
                case 'n':
                    ret.push(v.month + 1);
                    break;
                case 't':
                    ret.push(this.getLastDayOfMonth(dt));
                    break;
                case 'Y':
                    ret.push(v.year);
                    break;
                case 'y':
                    ret.push(this._datePadTo2(v.year % 100));
                    break;
                case 'a':
                    ret.push((v.hours > 11 ? strings.amPm.pmLower : strings.amPm.amLower));
                    break;
                case 'A':
                    ret.push((v.hours > 11 ? strings.amPm.pmUpper : strings.amPm.amUpper));
                    break;
                case 'g':
                    ret.push((v.hours % 12) || '12');
                    break;
                case 'G':
                    ret.push(v.hours);
                    break;
                case 'h':
                    ret.push(v.hours ? this._datePadTo2(v.hours) : '12');
                    break;
                case 'H':
                    ret.push(this._datePadTo2(v.hours));
                    break;
                case 'i':
                    ret.push(this._datePadTo2(v.minutes));
                    break;
                case 's':
                    ret.push(this._datePadTo2(v.seconds));
                    break;
                case 'Z':
                    ret.push(v.tzOffset * 60);
                    break;
                default:
                    ret.push(arr[x]);
                    break;
            }
        }

        return ret.join('');
    },
    
    getLastDayOfMonth: function(dt) {
        if (!dt) {
            dt = new Date();
        }
        
        return (new Date(dt.getFullYear(), dt.getMonth() + 1, 0)).getDate();
    },
    
    getFullTime: ('now' in Date ? Date.now : function () { return (new Date()).getTime(); }),
    
    /**
     * Wrapper for console.log (in case browser lacks support)
     */
    log: function () {
        if (window.console && window.console.log) {
            return window.console.log.apply(window.console, arguments);
        }
    },
    
    /**
     * Trim the whitespace from a string (based on jQuery.trim())
     */
    trim: ('trim' in String.prototype ?
        // Use native function when available
        function (str) {
            return str == null ? '' : String.prototype.trim.call(str);
        } :
        function (str) {
            return str == null ? '' : str.toString().replace(/^\s+/, '').replace(/\s+$/, '');
        }
    ),
    
    ucfirst: function (str) {
        return str.charAt(0).toLocaleUpperCase() + str.slice(1);
    }
});
