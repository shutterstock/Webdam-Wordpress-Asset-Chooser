/* global ajaxurl, post_id, webdam_sideload_nonce */
( function( $ ) {
	tinymce.create('tinymce.plugins.WebDAMAssetChooser', {
		init: function(ed, assetsUrl) {

			var pluginUrl = assetsUrl.replace(/assets/i, '');

			ed.addButton('btnWebDAMAssetChooser',
			{
				title: 'Asset Chooser',
				image: assetsUrl + '/webdam.png',
				cmd: 'showAssetChooser',
				classes: 'widget btn btnWebDAMAssetChooser',
				onclick: function() { }
			});

			ed.addCommand('showAssetChooser', function() {
				var params = [{label:"Embed the link", action:"getAssetId", showEmbedLink:"true", showAddLink:"false"}];
				var returnPath = pluginUrl + '/includes/set-cookie.php';

				var windowReference = ed.windowManager.open({
					title: 'WebDAM Asset Chooser',
					url: asset_chooser_domain + '/assetpicker/assetpicker.plugin.php?returnUrl=' + encodeURIComponent(returnPath) + '&tokenpath=' + encodeURIComponent(webdam_get_current_api_response_url) + '&params=' + encodeURIComponent(JSON.stringify(params)),
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
							if (returnedImage.embedType == 'preview') {

								// Display waiting animation
								$( '.webdam-asset-chooser-status' ).addClass( 'visible' );

								// POST the image URL to the server via AJAX
								// Server side—sideload the image into our media library
								// embed the copied version of the image (from our ML)
								$.post(
									ajaxurl,
									{
										action: 'pmc-webdam-sideload-image',
										nonce: webdam_sideload_nonce,
										post_id: post_id,
										webdam_asset_id: returnedImage.id,
										webdam_asset_url: returnedImage.url,
										webdam_asset_filename: returnedImage.filename
									},
									function( response ) {

										if ( response.success ) {

											var image_template = _.template( $( 'script#webdam-insert-image-template' ).html() );

											ed.execCommand( 'mceInsertContent', 0, image_template( response.data ) );

										}

										// Hide waiting animation
										$( '.webdam-asset-chooser-status' ).removeClass( 'visible' );

										// Close the WebDAM modal window
										windowReference.close();
									}
								);
							} else {
								var textLink = prompt('Please enter the label of your link', returnedImage.filename);

								var elem_anchor = jQuery( '<a></a>' ).attr( 'href', webDAMHTMLPath + '/download.php?id=' + returnedImage.id ).text( textLink );

								ed.execCommand( 'mceInsertContent', 0, elem_anchor.prop( 'outerHTML' ) );
								// Close the WebDAM modal window
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
} )( jQuery );