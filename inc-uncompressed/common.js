
Function.prototype.bind = function(o) {
	var s = this;
	return function() {
		return s.apply(o, arguments);
	}
}

var $id = function(e) {
	return document.getElementById(e);
};




var AJS = {
	x: {},
	frmDefMsgs: {},
	
	// link controls
	openWin: function(url)
	{
		window.open(url);
		return false;
	},
	
	openWinBasic: function(e, w, h, tb)
	{
		w || (w = 400);
		h || (h = 300);
		window.open(e.href, "", "status=0,toolbar="+(tb?"1":"0")+",location=0,menubar=0,directories=0,resizable=1,scrollbars=1,width="+w+",height="+h);
		return false;
	},
	
	linkTargetBlank: function(o)
	{
		o.target="_blank";
		o.rel="noopener";
		return true;
	},
	
	
	// form controls
	
	validateForm: function(f)
	{
		var valid=true;
		var e = f.elements;
		for(i=0; i<e.length; i++) {
			if(this.validateFormElement(e[i], f.id+"_Validator", valid)) {
				// is this the first error encountered?
				if(valid) {
					try {
						e[i].focus();
						e[i].select();
					} catch(x){}
				}
				valid=false;
			}
		}
		return valid;
	},
	resetFormErrors: function(f,sel)
	{
		var e = f.elements;
		for(i=0; i<e.length; i++) {
			if(ep = $id(e[i].id+"_error")) {
				ep.innerHTML = this.frmDefMsgs[e[i].id];
				if(!ep.innerHTML) ep.style.display="none";
				this.formElemSetErrorClass(e[i],ep.innerHTML);
				//f.elements[e].className = f.elements[e].className.replace(/_error$/,"");
			}
			if(sel && e[i].type && e[i].type!="hidden") {
				sel = false;
				try {
					e[i].focus();
					e[i].select();
				} catch(x){}
			}
		}
		return true;
	},
	
	validateFormElement: function(e,cn,popup)
	{
		var c = window[cn];
		var f = c[e.name+"_validate"];
		if(f) {
			// do we have an error placeholder?
			var ep = $id(e.id+"_error");
			if(error = (f.bind(e))()) {
				if(ep) {
					//ep.innerHTML = "<br />"+error;
					ep.innerHTML = error;
					ep.style.display = "block";
					//if(e.className.substr(-6) != "_error")
					//	e.className += "_error";
					this.formElemSetErrorClass(e,true);
				}
				else if(popup)
					alert(error);
				
			} else {
				// clear any error we may have
				if(ep) {
					ep.innerHTML = "";
					ep.style.display = "none";
					//e.className = e.className.replace(/_error$/,"");
					this.formElemSetErrorClass(e,false);
				}
			}
			return error;
		}
		return false;
	},
	
	formElemSetErrorClass: function(e,error)
	{
		if(error) {
			if(e.className.substr(-6) != "_error")
				e.className += "_error";
		}
		else
			e.className = e.className.replace(/_error$/,"");
	},
	
	formValidatorAttach: function(id) {
		var e=$id(id).elements;
		var i;
		for(i=0; i<e.length; i++) {
			eval('var ve='+id+'_Validator["'+e[i].name+'_validate"];');
			if(ve)
				e[i].onblur=function(){AJS.validateFormElement(this,id+"_Validator");}.bind(e[i]);
		}
	},
	
	
	// copied from Lightbox
	getPageScroll: function(){
		var xScroll, yScroll;
		
		if (self.pageYOffset) {
			yScroll = self.pageYOffset;
			xScroll = self.pageXOffset;
		} else if (document.documentElement && document.documentElement.scrollTop) {	 // Explorer 6 Strict
			yScroll = document.documentElement.scrollTop;
			xScroll = document.documentElement.scrollLeft;
		} else if (document.body) {// all other Explorers
			yScroll = document.body.scrollTop;
			xScroll = document.body.scrollLeft;
		}
		
		arrayPageScroll = new Array(xScroll,yScroll)
		return arrayPageScroll;
	},
	
	
	tempNoticeVisible: false,
	displayTempNotice: function(n) {
		if(this.tempNoticeVisible) {
			// hide old notice
			this.hideTempNotice();
		}
		this.tempNoticeVisible = true;
		var s = $('#notice_popup_c');
		if(!s.length)
		{
			s = $(document.createElement('div'));
			s.attr('id', 'notice_popup_c')
			 .html('<div id="notice_popup"><a class="close" onclick="return AJS.hideTempNotice();" href="#">x</a><span id="notice_popup_msg"></span></div>')
			 .appendTo(document.body);
			
			$(window).scroll(function() {
				var scrl = AJS.getPageScroll();
				$('#notice_popup_c').css('top', scrl[1]+'px');
			});
		}
		
		$id('notice_popup_msg').innerHTML = n;
		
		var scrl = AJS.getPageScroll();
		//var pt = ((($(window).height() - s.height())/2)*0.8 + scrl[1]);
		s.css({'display': 'block', 'top': scrl[1]+'px', 'opacity': 0});
		s.fadeTo('fast', 0.8);
		
		setTimeout(function(){
			if(!this.tempNoticeVisible) return;
			s.fadeOut('slow', function(){
				this.tempNoticeVisible = false;
			}.bind(this));
		}.bind(this), 4000);
	},
	hideTempNotice: function() {
		if(!this.tempNoticeVisible) return;
		$('#notice_popup_c').hide();
		return false;
	},
	
	
	extractScripts: function(s) {
		var sf = '';
		var matchOne = new RegExp('<script[^>]*>([\t\r\n ]*<!--)?([\\S\\s]*?)(-->[\t\r\n ]*)?</script>', 'im');
		
		var r=[];
		while(m=s.match(matchOne)) {
			r.push(m[2]);
			s=s.replace(matchOne,"");
		}
		
		return r;
	},
	
	evalScripts: function(__s__) {
		var __scripts__ = AJS.extractScripts(__s__);
		for(__i__=0; __i__<__scripts__.length; __i__++) {
			$.globalEval(__scripts__[__i__]);
		}
	},
	
	fmtAJAX4HTML: function(s) {
		//return s.replace(/[\r\n\t]/g,"").replace(/<script[^>]*>.*?<\/script>/ig,"").replace(/<noscript>.*?<\/noscript>/ig,"");
		return s.replace(/<script[^>]*>.*?<\/script>/ig,"").replace(/<noscript>.*?<\/noscript>/ig,"");
	},
	
	
	// copied from lightbox (jQuery's method is dodgey)
	getPageSize: function() {
		var xScroll, yScroll;
		
		if (window.innerHeight && window.scrollMaxY) {
			xScroll = window.innerWidth + window.scrollMaxX;
			yScroll = window.innerHeight + window.scrollMaxY;
		} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
			xScroll = document.body.scrollWidth;
			yScroll = document.body.scrollHeight;
		} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
			xScroll = document.body.offsetWidth;
			yScroll = document.body.offsetHeight;
		}
		
		var windowWidth, windowHeight;
		
		if (self.innerHeight) {	// all except Explorer
			if(document.documentElement.clientWidth){
				windowWidth = document.documentElement.clientWidth;
			} else {
				windowWidth = self.innerWidth;
			}
			windowHeight = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
			windowWidth = document.documentElement.clientWidth;
			windowHeight = document.documentElement.clientHeight;
		} else if (document.body) { // other Explorers
			windowWidth = document.body.clientWidth;
			windowHeight = document.body.clientHeight;
		}	
		
		// for small pages with total height less then height of the viewport
		if(yScroll < windowHeight){
			pageHeight = windowHeight;
		} else { 
			pageHeight = yScroll;
		}

		// for small pages with total width less then width of the viewport
		if(xScroll < windowWidth){
			pageWidth = xScroll;
		} else {
			pageWidth = windowWidth;
		}

		return {docW: pageWidth, docH: pageHeight, winW: windowWidth, winH: windowHeight};
	},

	
	hndlTopBGFixed: function() {
		// the following doesn't work well with the theme switcher
		/*var vert = "0";
		try {
			vert = document.defaultView.getComputedStyle($id("topbar_c")).backgroundPosition.replace(/^[^ ]* /, '') || "0";
		} catch(x){}*/
		$id("topbar_c").style.backgroundPosition = (
			(window.innerWidth || document.documentElement.offsetWidth)
			- this.winWidthOffs
			- (window.innerWidth ? document.documentElement.offsetWidth : document.body.offsetWidth)
		)/2 + "px 0"; //+ vert;
	},
	initTopBGFixed: function() {
		if(window.innerWidth || document.documentElement) {
			var d = document.documentElement;
			// retrieve the difference in widths between the html and body elements
			if(window.innerWidth) {
				var ds = d.style;
				var omw = ds.maxWidth;
				ds.maxWidth = "none";
				this.winWidthOffs = window.innerWidth-d.offsetWidth;
				ds.maxWidth = omw;
			} else {
				this.winWidthOffs = d.offsetWidth - document.body.offsetWidth;
			}
			$id("topbar_c").style.backgroundAttachment = "fixed";
			var resizeFunc = this.hndlTopBGFixed.bind(this);
			$(window).resize(resizeFunc);
			resizeFunc();
		}
	}
};


AJS.AJAX = {
	post: function(url, data, cf) {
		this.loader.show();
		return this.spost(url, data, cf, function(){this.loader.hide();}.bind(this));
	},
	
	spost: function(url, data, successF, completeF) {
		data = data || [];
		var hasAjax=false, hasPk=false;
		for(i=0; i<data.length; i++) {
			if(data[i].name.toLowerCase()=="ajax")
				hasAjax=true;
			if(data[i].name.toLowerCase()=="postkey")
				hasPk=true;
		}
		if(!data.push) {
			// convert to array
			var data2 = [];
			for(n in data)
				data2.push({name: n, value: data[n]});
			data = data2;
		}
		if(!hasAjax)
			data.push({name: "ajax", value: 1});
		if(!hasPk && url.indexOf("?postkey=") < 1 && url.indexOf("&postkey=") < 1)
			data.push({name: "postkey", value: postkey});
		
		var doneFunc = function(o, stat, o2) {
			if(o.responseText) r=o.responseText; else r=o;
			if(newPk = r.match(/^<error>newPostKey\:(.*)<\/error>$/i)) {
				newPk = newPk[1];
				// set new postkey and retry
				if(url.indexOf("?postkey=") >0 || url.indexOf("&postkey=") >0) {
					url = url.replace(/([?&]postkey=)[a-f0-9]+/i, "$1"+newPk);
				} else {
					for(i=0; i<data.length; i++) {
						if(data[i].name.toLowerCase()=="postkey") {
							data[i].value = newPk;
							break;
						}
					}
				}
				// retry
				var doneFunc2 = function(r) {
					if(r.responseText) r=r.responseText;
					this.completeFunc(r, successF);
				}.bind(this);
				$.ajax({url: url, type: 'POST', dataType: 'text', data: data, success: doneFunc2, error: doneFunc2, complete: completeF, global: true});
			} else {
				this.completeFunc(r, successF);
				// jQuery has some funky argument orderings
				if(o.responseText) // error
					completeF(o, stat);
				else // success
					completeF(o2, stat);
			}
		}.bind(this);
		$.ajax({url: url, type: 'POST', dataType: 'text', data: data, success: doneFunc, error: doneFunc, global: true});
		
		return false;
	},
	
	completeFunc: function(r, successF) {
		if(error = r.match(/^<error>(.*)<\/error>$/i)) {
			if(error[1].indexOf("newPostKey:") == 0) {
				// for some reason, we got a postkey error...
				alert("Unexpected error returned: Postkey verification failure\n\nTry reloading the page and submitting again.");
			} else
				alert(error[1]);
		}
		else if(redir = r.match(/^<redirect>(.*)<\/redirect>$/im))
			window.location.href = redir[1];
		else
			successF(r);
	},
	
	loader: {
		visible: false,
		show: function()
		{
			if(this.visible) return;
			this.visible = true;
			var s = $('#ajax_shade');
			if(!s.length)
			{
				s = $(document.createElement('div'));
				s.attr('id', 'ajax_shade')
				 .css('display', 'none')
				 .html('<div id="ajax_shade_text">Loading...</div>')
				 .appendTo(document.body);
				
				$(window).scroll(function() {
					var scrl = AJS.getPageScroll();
					$id('ajax_shade_text').style.paddingTop = ((AJS.getPageSize()).winH/2 - 25 + scrl[1])+'px';
				});
				$(window).resize(function() {
					var scrl = AJS.getPageScroll();
					var dims = AJS.getPageSize();
					$id('ajax_shade').style.height = dims.docH+'px';
					$id('ajax_shade_text').style.paddingTop = (dims.winH/2 - 25 + scrl[1])+'px';
				});
			}
			
			var scrl = AJS.getPageScroll();
			var dims = AJS.getPageSize();
			s.css({'display': '', 'height': (dims.docH)+'px', 'opacity': 0});
			$id('ajax_shade_text').style.paddingTop = (dims.winH/2 - 25 + scrl[1])+'px';
			s.clearQueue().fadeTo('def', 0.7);
		},
		
		hide: function()
		{
			if(!this.visible) return;
			this.visible = false;
			$('#ajax_shade').clearQueue().fadeOut('fast');
		}
	}
};
