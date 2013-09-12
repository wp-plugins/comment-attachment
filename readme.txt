=== Comment Attachment ===
Contributors: latorante
Donate link: http://donate.latorante.name/
Tags: comments, comment, image, attachment, images, files
Requires at least: 3.0
Tested up to: 3.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.0

Allow your visitors to attach files with their comments!

== Description ==

This plugin allows your visitors to attach files with their comments, such as documents and images. It uses only built in wordpress hooks, and provides you with multiple settings in you admin *(using native WP_Settings API)*. With these you can:

* Select if the upload field is before or after the default comment fields.
* Make attachment a required field.
* Select a label of upload field *(default is 'Upload Attachment')*
* Select which file types are allowed to be attached.
* Select if attachment is visible in the the actual comment.
* Select if attachment should be attached to post your visitor comments on, or not.
* Select position of attachement in comment, either before the main comment, or after it.
* Decide whether attachaments can be downloaded.
* Decide if attachment image should be displayed in comment and select image size (it automatically loads all image sizes set up in your installation and by your theme using 'add_image_size')

All attachments are inserted in your main wordpress media gallery, and are attached (if set in settings) to current post. After comment deletition is the attachment removed from wordpress media gallery (if set in settings).

If an error occurs, like required attachment, or visitor trying to upload not allowed file type, plugin uses native `wp_die()` to handle the error, which can play nicely, if you use some other plugin for handeling comment form errors in a different way, [like this one](http://wordpress.org/plugins/comment-form-inline-errors/ "Comment form inline errors"). 

To control the output, in your css, you can use these classes and id's. For form elements:

`.comment-form-attachment {}
.comment-form-attachement label .attachmentRules {}
.comment-form-attachement input#attachment {}`

and for inner comment elements:

`.attachementFile {}
.attachementFile p {}
.attachementLink {}
.attachementLink img {}`

It should be easy peasy for you to style it! :)

== Installation ==

1. Go to your admin area and select Plugins -> Add new from the menu.
2. Search for "Comment Attachement".
3. Click install.
4. Click activate.

== Screenshots ==

1. Settings page in wp admin. Settings > Discussion > Comment Attachment
2. Attachment field in comment form.
3. Attachments in comments with links and image thumbnails.

== Changelog ==
= 1.0 =
* Fixed small typos.
* Plugin released.