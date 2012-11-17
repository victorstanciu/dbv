document.observe('dom:loaded', function () {
    APP.growler = (function () {
        var noticeOptions = {
            header: '&nbsp;',
            speedin: 0.3,
            speedout: 0.5,
            outDirection: {
                y: 20
            },
            life: 5,
            sticky: false,
            className: ""
        };
        var growlerOptions = {
            location: "br",
            width: "500px"
        };
        function removeNotice(n, o) {
            o = o || noticeOptions;
            new Effect.Parallel([
    	        new Effect.Move(n, Object.extend({
    	            sync: true,
    	            mode: 'relative'
    	        }, o.outDirection)),
    	        new Effect.Opacity(n, {
    	            sync: true,
    	            to: 0
    	        })
            ], {
                duration: o.speedout,
                afterFinish: function () {
                    try {
                        var ne = n.down("div.notice-exit");
                        if (ne != undefined) {
                            ne.stopObserving("click", removeNotice);
                        }
                        if (o.created && Object.isFunction(o.created)) {
                            n.stopObserving("notice:created", o.created);
                        }
                        if (o.destroyed && Object.isFunction(o.destroyed)) {
                            n.fire("notice:destroyed");
                            n.stopObserving("notice:destroyed", o.destroyed);
                        }
                    } catch (e) {}
                    try {
                        n.remove();
                    } catch (e) {}
                }
            });
        }

        function createNotice(growler, msg, options) {
            var opt = Object.clone(noticeOptions);
            options = options || {};
            Object.extend(opt, options);
            var notice;
            if (opt.className != "") {
                notice = new Element("div", {
                    "class": opt.className
                }).setStyle({
                    display: "block",
                    opacity: 0
                });
            } else {
                notice = new Element("div", {
                    "class": "Growler-notice"
                }).setStyle({
                    display: "block",
                    opacity: 0
                });
            }
            if (opt.created && Object.isFunction(opt.created)) {
                notice.observe("notice:created", opt.created);
            }
            if (opt.destroyed && Object.isFunction(opt.destroyed)) {
                notice.observe("notice:destroyed", opt.destroyed);
            }
            if (opt.sticky) {
                var noticeExit = new Element("button", {
                    "class": "close pull-right"
                }).update("&times;");

                noticeExit.observe("click", function () {
                    removeNotice(notice, opt);
                });
                notice.insert(noticeExit);
            }
            notice.insert(new Element("div", {
                "class": "Growler-notice-head"
            }).update(opt.header));
            notice.insert(new Element("div", {
                "class": "Growler-notice-body"
            }).update(msg));
            growler.insert(notice);
            new Effect.Opacity(notice, {
                to: 0.85,
                duration: opt.speedin
            });
            if (!opt.sticky) {
                removeNotice.delay(opt.life, notice, opt);
            }
            notice.fire("notice:created");
            return notice;
        }

        function specialNotice(g, m, o, t, b, c) {
            o.header = o.header || t;
            var n = createNotice(g, m, o);
            n.setStyle({
                backgroundColor: b,
                color: c
            });
            return n;
        }

        Growler = Class.create({
            initialize: function (options) {
                var opt = Object.clone(growlerOptions);
                options = options || {};
                Object.extend(opt, options);
                this.growler = new Element("div", {
                    "class": "Growler",
                        "id": "Growler"
                });
                this.growler.setStyle({
                    position: 'fixed',
                    padding: "10px",
                        "width": opt.width,
                        "z-index": "50000"
                });

                switch (opt.location) {
                    case "br":
                        this.growler.setStyle({
                            bottom: 0,
                            right: 0
                        });
                        break;
                    case "tl":
                        this.growler.setStyle({
                            top: 0,
                            left: 0
                        });
                        break;
                    case "bl":
                        this.growler.setStyle({
                            top: 0,
                            right: 0
                        });
                        break;
                    case "tc":
                        this.growler.setStyle({
                            top: 0,
                            left: "25%",
                            width: "50%"
                        });
                        break;
                    case "bc":
                        this.growler.setStyle({
                            bottom: 0,
                            left: "25%",
                            width: "50%"
                        });
                        break;
                    default:
                        this.growler.setStyle({
                            top: 0,
                            right: 0
                        });
                        break;
                }
                this.growler.wrap(document.body);
            },
            growl: function (msg, options) {
                return createNotice(this.growler, msg, options);
            },
            error: function (header, message) {
            	return this.growl(message, {
    				header: header,
    				sticky: true,
    				className: 'Growler-notice error'
    			});
            },
            success: function (header, message) {
            	return this.growl(message, {
    				header: header,
    				sticky: false,
    				className: 'Growler-notice success'
    			});	
            },
            ungrowl: function (n, o) {
                removeNotice(n, o);
            }
        });

    	return new Growler;
    })();
});