var CurrentPage = {
           id: function() { return $('Form_EditForm_ID') ? $('Form_EditForm_ID').value : null; },
    saveCount: function() { return $('SiteTree_Alert') ? $('SiteTree_Alert').getAttribute('savecount') : null; },
 setSaveCount: function(count) { if ($('SiteTree_Alert')) { $('SiteTree_Alert').setAttribute('savecount', count); } },
    isDeleted: function() { return $('SiteTree_Alert') ? $('SiteTree_Alert').getAttribute('deletedfromstage') : null; }
};


// Protect global namespace using this throwaway function.
(function ConcurrentEditingNamespace() {

	var timerID = null;
	var saveHasBeenClicked = false;
	var showOverwroteMessage = false;

	function getSetting(attr) {
		return $('SiteTree_Alert').getAttribute(attr);
	}

	/**
	 * This function is called every `pagePingInterval` seconds, and checks the
	 * concurrent editing status.
	 */
	function pingFunction() {
		if ($('Form_EditForm_ID') && $('SiteTree_Alert') && !window.location.toString().match(/compareversions/)) {
			var url = "admin/concurrentEditingPing?ID="+CurrentPage.id()+'&SaveCount='+CurrentPage.saveCount();
			new Ajax.Request(url, {
				onSuccess: function(t) {
					var data = eval('('+t.responseText+')');
					var hasAlert = false;

					switch(data.status) {
						case 'editing':
							if (showOverwroteMessage && data.isLastEditor) {
								if(data.lastEditor == 'myself') {
									showOverwroteMessage = false;
									break;
								}
								$('SiteTree_Alert').style.border = '2px solid #FFD324';
								$('SiteTree_Alert').style.backgroundColor = '#fff6bf';
								$('SiteTree_Alert').innerHTML = "You just overwrote the version saved by " + data.lastEditor + ".  Compare those versions " + data.compareVersionsLink;
								hasAlert = true;
								setTimeout(function() { showOverwroteMessage = false; }, getSetting('overwriteDisplayDuration') * 1000);
								break;
							}
							$('SiteTree_Alert').style.border = '2px solid #B5D4FE';
							$('SiteTree_Alert').style.backgroundColor = '#F8FAFC';
							if (data.names.length) {
								hasAlert = true;
								$('SiteTree_Alert').innerHTML = "This page is also being edited by: "+data.names.join(', ');
							}
							saveHasBeenClicked = false;
							break;
						case 'deleted':
							// handle deletion by another user (but not us, or if we're already looking at a deleted version)
							if (CurrentPage.isDeleted() == 0) {
								$('SiteTree_Alert').style.border = '2px solid #ffd324';
								$('SiteTree_Alert').style.backgroundColor = '#fff6bf';
								$('SiteTree_Alert').innerHTML = "This page has been deleted recently.";
								$('SiteTree_Alert').innerHTML += " <a href='" + data.viewDeletedUrl + "'>View</a> or <a href='" + data.restoreDeletedUrl + "'>restore</a>.";
								jQuery('#form_actions_right input').attr("disabled", 'disabled');
								jQuery('#form_actions_right').fadeTo("slow", 0.25);
								hasAlert = true;
							}
							saveHasBeenClicked = false;
							break;
						case 'not_current_version':
							// handle another user publishing
							$('SiteTree_Alert').style.border = '2px solid #FFD324';
							$('SiteTree_Alert').style.backgroundColor = '#fff6bf';
							$('SiteTree_Alert').innerHTML = "This page has been saved since you opened it. You may want to reload it, or risk overwriting changes.";
							hasAlert = true;
							showOverwroteMessage = true;
							saveHasBeenClicked = false;
							break;
						case 'not_found':
							break;
					}

					if (hasAlert) {
						$('SiteTree_Alert').style.padding = '5px';
						$('SiteTree_Alert').style.marginBottom = '5px';
						$('SiteTree_Alert').style.display = 'block';
					} else {
						$('SiteTree_Alert').innerHTML = '';
						$('SiteTree_Alert').style.padding = '0px';
						$('SiteTree_Alert').style.marginBottom = '0px';
						if ($('SiteTree_Alert').style.display != 'none') $('SiteTree_Alert').style.display = 'none';
					}
				},
				onComplete: function(transport) {
					if(transport.status >= 400) location.href = location.href;
				}
			});
		}
	} // pingFunction

	/*
	 * Register the SiteTree 'onload' function, called whenever a SiteTree page is
	 * opened or reloaded in the CMS.
	 */
	Behaviour.register({
		'#Form_EditForm' : {
			initialize : function() {
				this.observeMethod('PageLoaded', this.adminPageHandler);
				this.observeMethod('BeforeSave', this.beforeSave);
				this.adminPageHandler();
			},
                
			adminPageHandler : function() {
				if (!timerID && ($('Form_EditForm_ID') && $('SiteTree_Alert'))) {
					timerID = setInterval(pingFunction, getSetting('pagePingInterval')*1000);
				}
			},

			beforeSave: function() {
				saveHasBeenClicked = true;
			}
		} // #Form_EditForm
	});

})(); // end of Namespace
