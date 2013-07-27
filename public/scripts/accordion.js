/** Accordion v2.1
 *
 * Copyright (c) 2007 stickmanlabs
 * Additions (c) 2009 Thomas von Deyen
 * Author: Kevin P Miller | http://www.stickmanlabs.com
 * Additional Author: Thomas von Deyen | http://thomas.vondeyen.com
 * Prototype 1.6 fixes by the_coder via http://snipplr.com/view/5502/stickman-labs-accordion-updated-for-prototypejs-16-/
 *
 * Accordion is freely distributable under the terms of an MIT-style license.
 **/

if (typeof Effect == 'undefined') 
	throw("Accordion requires included script.aculo.us' effects.js library!");

/** 
 *  class Accordion(container[, options = {}]) -> Object
 *  - container (String | Element | CSSSelector): Containerelement of the accordion.
 *  - options (Object): options for Accordion.
 *  -- resizeSpeed (Integer): 8
 *  -- classNames (Object):
 *  --- toggle (String): The classname of the accordion toggle. Default = 'accordion_toggle'
 *  --- toggleActive (String): The classname of the active toggle. Default = 'accordion_toggle_active'
 *  --- content (String): The classname of the accordion content. Default = 'accordion_content'
 *  -- defaultSize (Object): Object with height and width values for fixed layout instead of auto height.
 *  -- direction: The direction of the accordion. Default = 'vertical'
 *  -- onEvent (String): The Eventname the toggle listens to. Default = 'click'
 *  -- afterComplete (Function): The Function to call after the Accordion animates.
 *  -- activeClosable (Boolean): Should the active Accordion be closable or not? Default = true.
 **/

var Accordion = Class.create();
Accordion.prototype = {
	showAccordion : null,
	currentAccordion : null,
	duration : null,
	effects : [],
	animating : false,
	initialize: function(container, options) {
	  if (!$(container)) {
	    if (typeof(console) != 'undefined') {
	      console.error(container + " doesn't exist!");
	    }
	    return false;
	  }	  
		this.options = Object.extend({
			resizeSpeed: 8,
			classNames: {
				toggle: 'accordion_toggle',
				toggleActive: 'accordion_toggle_active',
				content: 'accordion_content'
			},
			defaultSize: {
				height: null,
				width: null
			},
			direction: 'vertical',
			onEvent: 'click',
			afterComplete: function(){},
			activeClosable: true
		}, options || {});
		this.duration = ((11 - this.options.resizeSpeed) * 0.15);
		var accordions = $$('#' + container + ' .' + this.options.classNames.toggle);
		accordions.each(function(accordion) {
			Event.observe(accordion, this.options.onEvent, this.activate.bind(this, accordion), false);
			if (this.options.onEvent == 'click') {
			  accordion.onclick = function() {return false;};
			}
			if (this.options.direction == 'horizontal') {
				var options = {width: '0px', display: 'none'};
			} else {
				var options = {height: '0px', display: 'none'};
			}
			this.currentAccordion = $(accordion.next(0)).setStyle(options);
		}.bind(this));
	},
	
	/**
	 * Accordion.activate(toggle) -> null
	 * Activates an accordion
	 **/
	activate : function(accordion) {
		if (this.animating) {
			return false;
		}
		this.effects = [];
		this.currentAccordion = $(accordion.next(0));
		this.currentAccordion.setStyle({
			display: 'block'
		});
		this.currentAccordion.previous(0).addClassName(this.options.classNames.toggleActive);
		if (this.options.direction == 'horizontal') {
			this.scaling = $H({
				scaleX: true,
				scaleY: false
			});
		} else {
			this.scaling = $H({
				scaleX: false,
				scaleY: true
			});			
		}
		if (this.currentAccordion == this.showAccordion) {
		  this.deactivate();
		} else {
		  this._handleAccordion();
		}
	},

	/** 
	 * Accordion.deactivate() -> null
	 * Deactivates an active accordion
	 **/
	deactivate : function() {
		var options = $H({
		  duration: this.duration,
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			queue: {
				position: 'end', 
				scope: 'accordionAnimation'
			},
			scaleMode: { 
				originalHeight: this.options.defaultSize.height ? this.options.defaultSize.height : this.currentAccordion.scrollHeight,
				originalWidth: this.options.defaultSize.width ? this.options.defaultSize.width : this.currentAccordion.scrollWidth
			},
			afterFinish: function() {
				this.showAccordion.setStyle({
          height: 'auto',
					display: 'none'
				});				
				this.showAccordion = null;
				this.animating = false;
				this.options.afterComplete();
			}.bind(this)
		});
		if (this.options.activeClosable) {
  		this.showAccordion.previous(0).removeClassName(this.options.classNames.toggleActive);
		  new Effect.Scale(this.showAccordion, 0, options.update(this.scaling).toObject());
		} else {
		  this.options.afterComplete();
		}
	},

	/**
	 * Accordion._handleAccordion() -> null
	 * Handles the open/close actions of the accordion
	 **/
	_handleAccordion : function() {
		var options = $H({
			sync: true,
			scaleFrom: 0,
			scaleContent: false,
			transition: Effect.Transitions.sinoidal,
			scaleMode: { 
				originalHeight: this.options.defaultSize.height ? this.options.defaultSize.height : this.currentAccordion.scrollHeight,
				originalWidth: this.options.defaultSize.width ? this.options.defaultSize.width : this.currentAccordion.scrollWidth
			}
		});
		options.merge(this.scaling);
		this.effects.push(
			new Effect.Scale(this.currentAccordion, 100, options.update(this.scaling).toObject())
		);
		if (this.showAccordion) {
			this.showAccordion.previous(0).removeClassName(this.options.classNames.toggleActive);
			options = $H({
				sync: true,
				scaleContent: false,
				transition: Effect.Transitions.sinoidal
			});
			options.merge(this.scaling);
			this.effects.push(
				new Effect.Scale(this.showAccordion, 0, options.update(this.scaling).toObject())
			);				
		}
    new Effect.Parallel(this.effects, {
			duration: this.duration, 
			queue: {
				position: 'end', 
				scope: 'accordionAnimation'
			},
			beforeStart: function() {
				this.animating = true;
			}.bind(this),
			afterFinish: function() {
				if (this.showAccordion) {
					this.showAccordion.setStyle({
						display: 'none'
					});				
				}
				$(this.currentAccordion).setStyle({
				  height: 'auto'
				});
				this.showAccordion = this.currentAccordion;
				this.animating = false;
				this.options.afterComplete();
			}.bind(this)
		});
	}

};
