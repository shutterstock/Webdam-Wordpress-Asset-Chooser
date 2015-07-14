(function() {
	tinymce.create('tinymce.plugins.WebDAMAssetPicker', {
		init: function(ed, mainUrl) {
			ed.addButton('btnWebDAMAssetPicker',
			{
				title: 'Asset Picker',
				image: mainUrl + '/webdam.png',
				cmd: 'showAssetPicker',
				classes: 'widget btn btnWebDAMAssetPicker',
				onclick: function() { }
			});

			ed.addCommand('showAssetPicker', function() {
				var params = [{label:"Embed the link", action:"getAssetId", showAddLink:"true", sessionMode:"session"}];
				var returnPath = mainUrl + '/setvariable.html';
				window.open(asset_picker_domain + '/assetpicker/assetpicker.plugin.php?returnUrl=' + encodeURIComponent(returnPath) + 
						'&params=' + encodeURIComponent(JSON.stringify(params)));

				// also initiate the method that checks cookie and inserts the image when set
				window.setInterval(function() {
					var webDAMHTMLPath = asset_picker_domain;
					var re = new RegExp("widgetEmbedValue=([^;]+)");
					var value = re.exec(document.cookie);
					var currentCookieValue = (value != null) ? unescape(value[1]) : null;
					
					if (currentCookieValue != '' && currentCookieValue != null) {
						// clear the cookie value
						document.cookie = "widgetEmbedValue=;path=/;";
						var returnedImage = JSON.parse(currentCookieValue);
						if (returnedImage.embedType != 'dismiss') {
							if (returnedImage.embedType == 'preview') {
								ed.execCommand('mceInsertContent', 0, '<img src="' + returnedImage.url + '" alt="' + returnedImage.filename + '" />');
							} else {
								var textLink = prompt('Please enter the label of your link', returnedImage.filename);
								ed.execCommand('mceInsertContent', 0, '<a href="' + webDAMHTMLPath + '/download.php?id=' + returnedImage.id + '">' + textLink + '</a>');
							}
						}
						
						currentCookieValue = null;
					}
				}, 500);
			});
		},
		
		getInfo: function() {
			return {
				longname: "WebDAM Asset Picker",
				author: 'WebDAM',
				authorurl: 'http://webdam.com',
				infourl: 'http://webdam.com',
				version: "1.0"
			};
		}
	});
	// Register plugin
	tinymce.PluginManager.add('webdam_asset_picker', tinymce.plugins.WebDAMAssetPicker);
})();