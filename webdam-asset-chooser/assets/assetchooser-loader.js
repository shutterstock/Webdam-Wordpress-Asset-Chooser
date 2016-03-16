(function() {
	tinymce.create('tinymce.plugins.WebDAMAssetChooser', {
		init: function(ed, mainUrl) {
			ed.addButton('btnWebDAMAssetChooser',
			{
				title: 'Asset Chooser',
				image: mainUrl + '/webdam.png',
				cmd: 'showAssetChooser',
				classes: 'widget btn btnWebDAMAssetChooser',
				onclick: function() { }
			});

			ed.addCommand('showAssetChooser', function() {
				var params = [{label:"Embed the link", action:"getAssetId", showEmbedLink:"true", showAddLink:"false", sessionMode:"session"}];
				var returnPath = mainUrl + '/setvariable.html';
				
				var windowReference = ed.windowManager.open({
					title: 'WebDAM Asset Chooser',
					url: asset_chooser_domain + '/assetpicker/assetpicker.plugin.php?returnUrl=' + encodeURIComponent(returnPath) + 
						'&params=' + encodeURIComponent(JSON.stringify(params)),
					width: 940,
					height: 600,
					onclose: function() {
						
					}
				});

				// also initiate the method that checks cookie and inserts the image when set
				var mainInterval = window.setInterval(function() {
					var webDAMHTMLPath = asset_chooser_domain;
					var re = new RegExp("widgetEmbedValue=([^;]+)");
					var value = re.exec(document.cookie);
					var currentCookieValue = (value != null) ? unescape(value[1]) : null;
					
					if (currentCookieValue != '' && currentCookieValue != null) {
						// clear the cookie value
						document.cookie = "widgetEmbedValue=;path=/;";
						clearInterval(mainInterval);
						
						var returnedImage = JSON.parse(currentCookieValue);
						if (returnedImage.embedType != 'dismiss') {
							console.log('Reading cookie value');
							if (returnedImage.embedType == 'preview') {
								ed.execCommand('mceInsertContent', 0, '<img src="' + returnedImage.url + '" alt="' + returnedImage.filename + '" />');
								windowReference.close();
							} else {
								var textLink = prompt('Please enter the label of your link', returnedImage.filename);
								ed.execCommand('mceInsertContent', 0, '<a href="' + webDAMHTMLPath + '/download.php?id=' + returnedImage.id + '">' + textLink + '</a>');
								windowReference.close();
							}
						}
						
						currentCookieValue = null;
					}
				}, 500);
			});
		},
		
		getInfo: function() {
			return {
				longname: "WebDAM Asset Chooser",
				author: 'WebDAM',
				authorurl: 'http://webdam.com',
				infourl: 'http://webdam.com',
				version: "1.0"
			};
		}
	});
	// Register plugin
	tinymce.PluginManager.add('webdam_asset_chooser', tinymce.plugins.WebDAMAssetChooser);
})();