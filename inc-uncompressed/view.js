AJS.x.replyComment = 0;
AJS.x.replyCommentDisp = 0;
AJS.x.replyToComment = function(cid,nofocus,rcid) {
	rcid || (rcid=cid);
	if(this.replyCommentDisp == cid) return;
	$id('comment_reply_placeholder_'+cid).appendChild($id('comment_reply_form'));
	var nph = $('#comment_reply_placeholder_'+cid);
	nph.css({'display': 'none'})
	   .slideDown('fast', (nofocus==true ? null : function() {
		try {
			$id('newcomment_message').focus();
		} catch(x){}
	   }));
	if(cid)
		$id('comment_controls_'+cid).style.display='none';
	
	$id('comment_reply_placeholder_'+this.replyCommentDisp).style.display = 'none';
	if(this.replyCommentDisp)
		$id('comment_controls_'+this.replyCommentDisp).style.display='';
	//AJS.formValidatorAttach("newcomment");
	this.replyCommentDisp = cid;
	this.replyComment = rcid;
	
	$id('newcomment_reset').value = (cid?'Cancel':'Reset');
	// reset form to clear fields?
	
}.bind(AJS.x);

AJS.x.submitComment = function(frm) {
	var elems = $(frm).serializeArray();
	elems.push({name: "replyto", value: AJS.x.replyComment});
	AJS.AJAX.post(frm.action, elems, function(r){
		var repliedCid = AJS.x.replyComment;
		frm.reset();
		
		$id('view_comments_count').innerHTML = parseInt($id('view_comments_count').innerHTML.replace(/,/g,''))+1;
		
		r = r.replace(/[\r\n\t]/g, "");
		// create elem and insert b4 the comment box
		var nc = document.createElement("div");
		nc.innerHTML = AJS.fmtAJAX4HTML(r);
		//var replyContainer = $id('view_comments_replybox').parentNode;
		//replyContainer.insertBefore(nc, );
		//$(nc).insertBefore($($id('view_comments_replybox').parentNode));
		
		//$id('newcomments_placeholder_'+AJS.x.replyComment).appendChild(nc);
		$(nc).appendTo('#newcomments_placeholder_'+repliedCid);
		$(nc).slideDown('slow');
		AJS.evalScripts(r);
		
		// if we have a guest, hide the reply box
		if($id('newcomment_captcha')) {
			$id('comment_reply_form').style.display = 'none';
			// since there won't be any "Edit" links for guests, hide all reply buttons
			$('div.comment_buttons').css('display', 'none');
		}
		
		// grab anchor point
		var anchor = r.match(/<a id="([a-z0-9]+)">/i);
		if(anchor[1])
			window.location = "#"+anchor[1];
		
	});
	return false;
};

AJS.x.editCid = 0;
AJS.x.editCache = '';
AJS.x.editComment = function(cid) {
	if(this.editCid)
		this.cancelEditComment();
	
	this.editCid = cid;
	this.editCache = $id('comment_message_'+cid).innerHTML;
	
	// send ajax request
	var url = window.location.href;
	if((p = url.indexOf('#')) > 0)
		url = url.substr(0, p);
	var args = [{name:'cid', value:cid}, {name:'gettext', value:1}, {name:'do', value:'editcomment'}];
	AJS.AJAX.post(url, args, function(r) {
		$id('comment_message_'+cid).innerHTML = AJS.fmtAJAX4HTML(r);
		$id('comment_controls_'+cid).style.display='none';
		AJS.evalScripts(r);
		try {
			$id('editcomment_message').focus();
		} catch(x){}
	});
	
}.bind(AJS.x);

AJS.x.submitEditComment = function(frm, cid) {
	AJS.AJAX.post(frm.action, $(frm).serializeArray(), function(r) {
		if(eb = r.match(/^<modtitle>(.*?)<\/modtitle>/i)) {
			$id('comment_mod_'+cid).innerHTML = eb[1];
			r = r.substr(eb[0].length);
		}
		$id('comment_message_'+cid).innerHTML = AJS.fmtAJAX4HTML(r);
		$id('comment_controls_'+cid).style.display='';
		AJS.x.editCid = 0;
	});
	return false;
}.bind(AJS.x);

AJS.x.cancelEditComment = function() {
	$id('comment_message_'+this.editCid).innerHTML = this.editCache;
	$id('comment_controls_'+this.editCid).style.display='';
	this.editCid = 0;
}.bind(AJS.x);

AJS.x.voteUpComment = function(cid) {
	var cur = $id('comment_rating_'+cid).getAttribute('vote_state') | 0;
	return this._voteComment(cid, cur>0?0:1);
}.bind(AJS.x);
AJS.x.voteDownComment = function(cid) {
	var cur = $id('comment_rating_'+cid).getAttribute('vote_state') | 0;
	return this._voteComment(cid, cur<0?0:-1);
}.bind(AJS.x);

AJS.x._voteComment = function(cid, vote) {
	var url = window.location.href;
	if((p = url.indexOf('#')) > 0)
		url = url.substr(0, p);
	AJS.AJAX.post(url, [{name: "do", value: "commentvote"}, {name: "cid", value: cid}, {name: "vote", value: vote}], function(r) {
		if(r != 'ok') {
			alert("An unknown error occurred when submitting your rating.");
			return;
		}
		
		$('#comment_voteup_'+cid+', #comment_votedown_'+cid).removeClass('voted');
		if(vote > 0) $('#comment_voteup_'+cid).addClass('voted');
		else if(vote < 0) $('#comment_votedown_'+cid).addClass('voted');
		$id('comment_rating_'+cid).setAttribute('vote_state', vote);
	});
	return false;
}.bind(AJS.x);