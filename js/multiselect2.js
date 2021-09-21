/*
 * Jquery MultiselectPlug-in Chinese List Multi-select plug-ins
 * Use examples:
 * $('table').multiSelect({
 *  actcls: 'active',
 *  selector: 'tbody tr',
 *  callback: false
 * });
 */
(function ($) {
    $.fn.multiSelect = function (options) {
        $.fn.multiSelect.init($(this), options);
    };

    $.extend($.fn.multiSelect, {
        defaults: {
            actcls: 'active', //Check Style
            selector: 'tbody tr', //The selected row element
            except: ['tbody'], //Checked does not remove the effect of multiple elements of the queue
            statics: ['.static'], //Excluded row element condition
            callback: false //Select the callback
        },
        first: null, //When you press shift, the item to remember the first click
        last: null, //The last item clicked

        press_start_time: 0, 
        interval_timer: 1500, 

        mobile_touch: false, 
         

        init: function (scope, options) {
            this.scope = scope;
            this.options = $.extend({}, this.defaults, options);
            this.initEvent();
        },
        checkStatics: function (dom) {
            for (var i in this.options.statics) {
                if (dom.is(this.options.statics[i])) {
                    return true;
                }
            }
        },
        initEvent: function () {
            var self = this,
                scope = self.scope,
                options = self.options,
                callback = options.callback,
                actcls = options.actcls;

            scope.on('click.mSelect', options.selector, function (e) {
                if (!e.shiftKey && self.checkStatics($(this))) {
                    return;
                }


                /* при удержании мышки */
                var deferred_click = false;
                // if ((self.press_start_time + self.interval_timer) <= Date.now()){
                // 	deferred_click = true;
                // 	alert('12312');
                // }

                


                if ($(this).hasClass(actcls)) {
                    $(this).removeClass(actcls);
                } else {
                    $(this).addClass(actcls);
                }

                if (e.shiftKey && self.last) {
                    if (!self.first) {
                        self.first = self.last;
                    }
                    var start = self.first.index();
                    var end = $(this).index();
                    if (start > end) {
                        var temp = start;
                        start = end;
                        end = temp;
                    }
                    $(options.selector, scope).removeClass(actcls).slice(start + 2, end + 3).each(function () {
                        if (!self.checkStatics($(this))) {
                            $(this).addClass(actcls);
                        }
                    });
                    window.getSelection ? window.getSelection().removeAllRanges() : document.selection.empty();
                } else if (!e.ctrlKey && !e.metaKey && !deferred_click && !self.mobile_touch) {
                    $(this).siblings().removeClass(actcls);
                }
                self.last = $(this);
                $.isFunction(callback) && callback($(options.selector + '.' + actcls, scope));
            });

            /**
             * remove the selected state
             */
            $(document).on('click.mSelect', function (e) {

                for (var i in options.except) {
                    var except = options.except[i];
                    if ($(e.target).is(except) || $(e.target).parents(except).length) {
                        return;
                    }
                }
                scope.find(options.selector).each(function () {
                    if (!self.checkStatics($(this))) {
                        $(this).removeClass(actcls);
                    }
                });
                $.isFunction(callback) && callback($(options.selector + '.' + actcls, scope));
            });

            /**
             * Ctrl+A
             */
            $(document).on('keydown.mSelect', function (e) {
                if ((e.keyCode == 65) && (e.metaKey || e.ctrlKey)) {
                    $(options.selector, scope).each(function () {
                        if (!self.checkStatics($(this))) {
                            $(this).addClass(actcls);
                        }
                    });
                    $.isFunction(callback) && callback($(options.selector + '.' + actcls, scope));
                    e.preventDefault();
                    return false;
                }
            });

            /**
             * Shift
             */
            $(document).on('keyup.mSelect', function (e) {
                if (e.keyCode == 16) {
                    self.first = null;
                }
            });

            /**
             * MOUSE DOWN
             */
            $(document).on('mousedown.mSelect', function (e) {
                
                self.press_start_time = Date.now();
                  
            });

            /**
             * TOUCH MOBILE
             */
            $(document).on('touchstart.mSelect', function (e) {
                
                self.mobile_touch = true;

                //  alert(1231);
            });
        }
    });
})(jQuery);