# WebDAM Asset Chooser Wordpress plugin

The WebDAM Asset Chooser for Wordpress allows you to embed assets from your WebDAM account directly into Wordpress pages or posts. Simply enter the domain that you integrate with in the WebDAM settings and you are all set.

## Install
1. Download the repository as a zip file.
2. From the WordPress admin console, navigate to Plugins and choose Add New.
3. Click Upload Plugin at the top of the page.
4. Select the webdam.wordpress.assetchooser.zip file and then select Install Now.
5. Activate the plugin.

## Configure
1. Go to the new 'WebDAM' link under admin 'Settings'.
2. Enter the WebDAM domain that you wish to link to.
3. Click Save Changes.

## PREPARE Cross-Domain communication for Module
1. Add the following directives into Apache conf.  Install mod_headers.c if not installed already.

        <IfModule mod_headers.c>
	        # Allowing remote sources to access resources
	        # The origin can be filtered OR use * to allow for all
	        SetEnvIfNoCase Origin "http?://(.*\.)?(webdamdb\.com)(:\d+)?$" ACAO=$0
	        Header set Access-Control-Allow-Origin %{ACAO}e env=ACAO
        </IfModule>


## Adding Asset from WebDAM to a Post
When adding a new post or editing an existing post, you will see a WebDAM icon in the toolbar that appears above the post:

1. To insert an asset, click on the WebDAM icon.
2. Login using your WebDAM credentials.
3. Select the asset you would like to insert.
4. Choose which size thumbnail you would like to embed (550, 310, 220, or 100px).
5. Click Insert.

Note: The following asset types are not embeddable through the plugin: .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pdf, .indd., .swf, .ogg, .qxd, .qxp, .svg, .svgz, .otf, .sit, .sitx, .rar, .txt, .zip, .html, .htm.

## LICENSE
Copyright 2016 WebDAM

Licensed under the Wordpress License (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

https://wordpress.org/about/gpl/

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.