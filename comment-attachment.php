<?php
/*
    Plugin Name: Comment Attachment
    Plugin URI: http://latorante.name
    Description: Wordpress out-of-the-box comment attachment functionality. Offer your visitors ability to attach images, or documents to their comments that automatically attach to your Wordpress media gallery. Make the attachments visible, downloadable as you wish.
    Author: latorante
    Author URI: http://latorante.name
    Author Email: martin@latorante.name
    Version: 1.0
    License: GPLv2
*/
/*
    Copyright 2013  Martin Picha  (email : martin@latorante.name)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('ABSPATH')) { exit; }

if (!class_exists('wpCommentAttachment')){
    class wpCommentAttachment
    {
        /* minimum required wp version */
        public $wpVer           = "3.0";
        /* minimum required php version */
        public $phpVer          = "5.3";
        /* admin settings */
        private $adminPage      = 'discussion';
        private $adminCheckboxes;
        private $adminPrefix    = 'commentAttachment';
        private $key            = 'commentAttachment';
        private $settings;

        public function __construct()
        {
            if(!get_option($this->key)){ $this->initializeSettings(); }
            $this->settings = $this->getSavedSettings();
            $this->defineConstants();
            add_action('init', array($this, 'init'));
            add_action('admin_init', array($this, 'adminInit'));
        }


        /******************* Inits, innit :D *******************/


        public function init()
        {
            if(!$this->checkRequirements()){ return; }
            add_filter('preprocess_comment',        array($this, 'checkAttachment'));
            add_action('comment_form_top',          array($this, 'displayBeforeForm'));
            add_action('comment_form_before_fields',array($this, 'displayFormAttBefore'));
            add_action('comment_form_after_fields', array($this, 'displayFormAttAfter'));
            add_action('comment_form_logged_in_after',array($this, 'displayFormAtt'));
            add_filter('comment_text',              array($this, 'displayAttachment'));
            add_action('comment_post',              array($this, 'saveAttachment'));
            add_action('delete_comment',            array($this, 'deleteAttachment'));
        }

        public function adminInit()
        {
            $this->setUserNag();
            add_filter('plugin_action_links', array($this, 'displayPluginActionLink'), 10, 2);
            register_setting($this->adminPage, $this->key, array($this, 'validateSettings'));
            add_settings_section($this->adminPrefix,           'Comment Attachment', '', $this->adminPage);
            add_settings_section($this->adminPrefix . 'Types', 'Allowed File Types', '', $this->adminPage);
            foreach ($this->getSettings() as $id => $setting){
                $setting['id'] = $id;
                $this->createSetting($setting);
            }
        }


        /*************** Plugins admin settings ****************/

        /**
         * Get's admin settings page variables
         *
         * @return mixed
         */

        public function getSettings() {
            $setts[$this->adminPrefix . 'Position'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Display attachment field',
                'desc'    => '',
                'type'    => 'select',
                'std'     => '',
                'choices' => array(
                    'before' => 'Before default comment form fields.',
                    'after' => 'After default comment form fields.')
            );
            $setts[$this->adminPrefix . 'Title'] = array(
                'title'   => 'Attachment field title',
                'desc'    => '',
                'std'     => 'Upload Attachment',
                'type'    => 'text',
                'section' => $this->adminPrefix
            );
            $setts[$this->adminPrefix . 'Required'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Is attachment required?',
                'desc'    => '',
                'type'    => 'checkbox',
                'std'     => 0
            );
            $setts[$this->adminPrefix . 'Bind'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Attach attachment with current post?',
                'desc'    => '',
                'type'    => 'checkbox',
                'std'     => 1
            );
            $setts[$this->adminPrefix . 'APosition'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Position of attachment in comment text',
                'desc'    => '',
                'type'    => 'select',
                'std'     => '',
                'choices' => array(
                    'before' => 'Before comment.',
                    'after' => 'After comment.',
                    'none' => 'Don\'t display attachment. (really?)')
            );
            $setts[$this->adminPrefix . 'Link'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Make attachment in comment a link?',
                'desc'    => '',
                'type'    => 'checkbox',
                'std'     => 0
            );
            $setts[$this->adminPrefix . 'Thumb'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Show image thumbnail?',
                'desc'    => ' (if attachment is image)',
                'type'    => 'checkbox',
                'std'     => 1
            );
            $setts[$this->adminPrefix . 'ThumbSize'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Image attachment size in comment',
                'desc'    => ' (if thumbnail is set to visible, and is image)',
                'type'    => 'select',
                'std'     => '',
                'choices' => $this->getRegisteredImageSizes()
            );
            $setts[$this->adminPrefix . 'Delete'] = array(
                'section' => $this->adminPrefix,
                'title'   => 'Delete attachment upon comment deletition?',
                'desc'    => '',
                'type'    => 'checkbox',
                'std'     => 1
            );
            $setts[$this->adminPrefix . 'JPG'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'JPG', 'desc' => '', 'type' => 'checkbox', 'std' => 1 );
            $setts[$this->adminPrefix . 'GIF'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'GIF', 'desc' => '', 'type' => 'checkbox', 'std' => 1 );
            $setts[$this->adminPrefix . 'PNG'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'PNG', 'desc' => '', 'type' => 'checkbox', 'std' => 1 );
            $setts[$this->adminPrefix . 'PDF'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'PDF', 'desc' => '', 'type' => 'checkbox', 'std' => 0 );
            $setts[$this->adminPrefix . 'DOC'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'DOC', 'desc' => '', 'type' => 'checkbox', 'std' => 0 );
            $setts[$this->adminPrefix . 'DOCX'] = array('section' => $this->adminPrefix . 'Types', 'title' => 'DOCX', 'desc' => '', 'type' => 'checkbox', 'std' => 0 );
            return $setts;
        }


        /********* Let's do this, plugin functionality *********/

        /**
         * Does what it says.
         *
         * @return object
         */

        private function getSavedSettings(){ return get_option($this->key); }


        /**
         * Define plugin constatns
         */

        private function defineConstants()
        {
            define('ATT_REQ',   ($this->settings[$this->adminPrefix . 'Required'] == '1' ? TRUE : FALSE));
            define('ATT_BIND',  ($this->settings[$this->adminPrefix . 'Bind'] == '1' ? TRUE : FALSE));
            define('ATT_DEL',   ($this->settings[$this->adminPrefix . 'Delete'] == '1' ? TRUE : FALSE));
            define('ATT_LINK',  ($this->settings[$this->adminPrefix . 'Link'] == '1' ? TRUE : FALSE));
            define('ATT_THUMB', ($this->settings[$this->adminPrefix . 'Thumb'] == '1' ? TRUE : FALSE));
            define('ATT_POS',   ($this->settings[$this->adminPrefix . 'Position']));
            define('ATT_APOS',  ($this->settings[$this->adminPrefix . 'APosition']));
            define('ATT_TITLE', ($this->settings[$this->adminPrefix . 'Title']));
            define('ATT_TSIZE', ($this->settings[$this->adminPrefix . 'ThumbSize']));
        }


        /**
         * For image thumb dropdown.
         *
         * @return mixed
         */

        private function getRegisteredImageSizes()
        {
            foreach(get_intermediate_image_sizes() as $size){
                $arr[$size] = ucfirst($size);
            };
            return $arr;
        }


        /**
         * If there's a place to set up those mime types,
         * it's here.
         *
         * @return array
         */

        private function getPluginFileTypes()
        {
            return array(
                $this->adminPrefix . 'JPG' => 'image/jpeg',
                $this->adminPrefix . 'GIF' => 'image/gif',
                $this->adminPrefix . 'PNG' => 'image/png',
                $this->adminPrefix . 'DOCX'=> 'application/vnd.openxmlformats-officedocument.wordprocessingml. document',
                $this->adminPrefix . 'DOC' => 'application/msword',
                $this->adminPrefix . 'PDF' => 'application/pdf',
            );
        }


        /**
         * Gets allowed file types for attachment check.
         *
         * @return array
         */

        private function getAllowedFileTypes()
        {
            $return = array();
            $pluginFileTypes = $this->getPluginFileTypes();
            foreach($this->settings as $key => $value){
                if(array_key_exists($key, $pluginFileTypes)){
                    if(($key == $this->adminPrefix . 'DOCX') || ($key == $this->adminPrefix . 'DOC') || ($key == $this->adminPrefix . 'PDF')){
                        $return[] = 'application/octet-stream';
                    }
                    $return[] = $pluginFileTypes[$key];
                }
            }
            return $return;
        }


        /*
         * For error info, and form upload info.
         */

        public function displayAllowedFileTypes()
        {
            $pluginFileTypes = $this->getPluginFileTypes();
            $fileTypesString = '';
            foreach($this->settings as $key => $value){
                if(array_key_exists($key, $pluginFileTypes)){
                    $fileTypesString .= str_replace($this->adminPrefix, '', $key) . ', ';
                }
            }
            return substr($fileTypesString, 0, -2);
        }


        /**
         * For attachment display.
         *
         * @return array
         */

        private function getImageMimeTypes(){ return array('image/jpeg', 'image/gif', 'image/png'); }


        /**
         * This way we sort of fake our "enctype" in, since there's not ohter hook
         * that would allow us to put it there naturally, and no, we won't use JS for that
         * since that's rubbish and not bullet-proof. Yes, this creates empty form on page,
         * but who cares, it works and does the trick.
         */

        public function displayBeforeForm()
        {
            echo '</form><form action="'. get_home_url() .'/wp-comments-post.php" method="POST" enctype="multipart/form-data" id="attachmentForm" class="comment-form" novalidate>';
        }


        /*
         * Display form upload field.
         */

        public function displayFormAttBefore()  { if(ATT_POS == 'before'){ $this->displayFormAtt(); } }
        public function displayFormAttAfter()   { if(ATT_POS == 'after'){ $this->displayFormAtt(); } }
        public function displayFormAtt()
        {
            $required = ATT_REQ ? ' <span class="required">*</span>' : '';
            echo '<p class="comment-form-url comment-form-attachment">'.
                '<label for="attachment">' . ATT_TITLE . $required .'<small class="attachmentRules">&nbsp;&nbsp;(Allowed file types: <strong>'. $this->displayAllowedFileTypes() .'</strong>)</small></label>'.
                '</p>'.
                '<p class="comment-form-url comment-form-attachment"><input id="attachment" name="attachment" type="file" /></p>';
        }


        /**
         * Checks attachment, size, and type and throws error if something goes wrong.
         *
         * @param $data
         * @return mixed
         */

        public function checkAttachment($data)
        {
            if($_FILES['attachment']['size'] > 0 && $_FILES['attachment']['error'] == 0){
                if(function_exists('finfo_file')){
                    $fileType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES['attachment']['tmp_name']);
                } elseif(function_exists('mime_content_type')) {
                    $fileType = mime_content_type($_FILES['attachment']['tmp_name']);
                } else {
                    $fileType = $_FILES['attachment']['type'];
                }
                if (!in_array($fileType, $this->getAllowedFileTypes()) || $_FILES['attachment']['size'] > (5 * 1048576)) { // under 5 megs?
                    wp_die('<strong>ERROR:</strong> File you upload must be valid file type <strong>('. $this->displayAllowedFileTypes() .')</strong>, and under 5MB!');
                }
            // error 4 is actually empty file mate
            } elseif (ATT_REQ && $_FILES['attachment']['error'] == 4) {
                wp_die('<strong>ERROR:</strong> Attachment is a required field!');
            } elseif($_FILES['attachment']['error'] == 1) {
                wp_die('<strong>ERROR:</strong> The uploaded file exceeds the upload_max_filesize directive in php.ini.');
            } elseif($_FILES['attachment']['error'] == 2) {
                wp_die('<strong>ERROR:</strong> The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
            } elseif($_FILES['attachment']['error'] == 3) {
                wp_die('<strong>ERROR:</strong> The uploaded file was only partially uploaded. Please try again later.');
            } elseif($_FILES['attachment']['error'] == 6) {
                wp_die('<strong>ERROR:</strong> Missing a temporary folder.');
            } elseif($_FILES['attachment']['error'] == 7) {
                wp_die('<strong>ERROR:</strong> Failed to write file to disk.');
            } elseif($_FILES['attachment']['error'] == 7) {
                wp_die('<strong>ERROR:</strong> A PHP extension stopped the file upload.');
            }
            return $data;
        }


        /**
         * Inserts file attachment from your comment to wordpress
         * media library, assigned to post.
         *
         * @param $fileHandler
         * @param $postId
         * @return mixed
         */

        public function insertAttachment($fileHandler, $postId)
        {
            require_once(ABSPATH . "wp-admin" . '/includes/image.php');
            require_once(ABSPATH . "wp-admin" . '/includes/file.php');
            require_once(ABSPATH . "wp-admin" . '/includes/media.php');
            return media_handle_upload($fileHandler, $postId);
        }


        /**
         * Save attachment to db, with all sizes etc. Assigned
         * to post, or not.
         *
         * @param $commentId
         */

        public function saveAttachment($commentId)
        {
            if($_FILES['attachment']['size'] > 0){
                $bindId = ATT_BIND ? $_POST['comment_post_ID'] : 0;
                $attachId = $this->insertAttachment('attachment', $bindId);
                add_comment_meta($commentId, 'attachmentId', $attachId);
                unset($_FILES);
            }
        }


        /**
         * Displays attachment in comment, according to
         * position selected in settings, and according to way selected in admin.
         *
         * @param $comment
         * @return string
         */

        public function displayAttachment($comment)
        {
            $attachmentId = get_comment_meta(get_comment_ID(), 'attachmentId', TRUE);
            if(is_numeric($attachmentId) && !empty($attachmentId)){
                // atachement info
                $attachmentLink = wp_get_attachment_url($attachmentId);
                $attachmentMeta = wp_get_attachment_metadata($attachmentId);
                $attachmentName = basename(get_attached_file($attachmentId));
                $attachmentType = get_post_mime_type($attachmentId);
                // let's do wrapper html
                $contentBefore  = '<div class="attachmentFile"><p>Attachment: ';
                $contentAfter   = '</p><div class="clear clearfix"></div></div>';
                // shall we do image thumbnail or not?
                if(ATT_THUMB && in_array($attachmentType, $this->getImageMimeTypes()) && !is_admin()){
                    $contentInner = wp_get_attachment_image($attachmentId, ATT_TSIZE);
                } else {
                    $contentInner .= '&nbsp;<strong>' . $attachmentName . '</strong>';
                }
                // attachment
                if(ATT_LINK || is_admin()){
                    $contentInnerFinal = '<a class="attachmentLink" target="_blank" href="'. $attachmentLink .'" title="Download: '. $attachmentName .'">';
                        $contentInnerFinal .= $contentInner;
                    $contentInnerFinal .= '</a>';
                } else {
                    $contentInnerFinal = $contentInner;
                }
                // bring a sellotape, this needs taping together
                $contentInsert = $contentBefore . $contentInnerFinal . $contentAfter;
                // attachment comment position
                if(ATT_APOS == 'before' && !is_admin()){
                    $comment = $contentInsert . $comment;
                } elseif(ATT_APOS == 'after' || is_admin()) {
                    $comment .= $contentInsert;
                }
            }
            return $comment;
        }


        /**
         * This deletes attachment after comment deletition.
         *
         * @param $commentId
         */

        public function deleteAttachment($commentId)
        {
            $attachmentId = get_comment_meta($commentId, 'attachmentId', TRUE);
            if(is_numeric($attachmentId) && !empty($attachmentId) && ATT_DEL){
                wp_delete_attachment($attachmentId, TRUE);
            }
        }


        /*************** Admin Settings Functions **************/


        public function displayPluginActionLink($links, $file)
        {
            static $thisPlugin;
            if (!$thisPlugin){ $thisPlugin = plugin_basename(__FILE__); }
            if ($file == $thisPlugin){
                $settingsLink = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-discussion.php" title="Settings > Discussion > Comment Attachment">Settings</a>';
                array_push($links, $settingsLink);
            }
            return $links;
        }


        /**
         * Validates settings
         *
         * @param $input
         * @return bool
         */

        public function validateSettings($input)
        {
            if (!isset($input['reset_theme'])){
                $options = get_option($this->key);
                foreach ($this->adminCheckboxes as $id){
                    if (isset($options[$id]) && ! isset($input[$id]))
                        unset($options[$id]);
                }
                return $input;
            }
            return FALSE;
        }


        /**
         * Does what it says, better believe it at 4:33AM,
         * am I right? :D
         */

        public function initializeSettings()
        {
            $default = array();
            foreach ($this->getSettings() as $id => $setting){
                if ($setting['type'] != 'heading')
                    $default[$id] = $setting['std'];
            }
            update_option($this->key, $default);
        }


        /**
         * Displays settings in admin.
         *
         * @param array $args
         */

        public function displaySetting($args = array())
        {
            extract($args);
            $options = get_option($this->key);
            if (! isset($options[$id]) && $type != 'checkbox')
                $options[$id] = $std;
            elseif (! isset($options[$id]))
                $options[$id] = 0;
            $field_class = '';
            if ($class != '')
                $field_class = ' ' . $class;
            switch ($type){
                case 'heading':
                    echo '</td></tr><tr valign="top"><td colspan="2"><h4>' . $desc . '</h4>';
                    break;
                case 'checkbox':
                    echo '<input class="checkbox' . $field_class . '" type="checkbox" id="' . $id . '" name="' . $this->key . '[' . $id . ']" value="1" ' . checked($options[$id], 1, false) . ' /> <label for="' . $id . '">' . $desc . '</label>';
                    break;
                case 'select':
                    echo '<select class="select' . $field_class . '" name="' . $this->key . '[' . $id . ']">';
                    foreach ($choices as $value => $label)
                        echo '<option value="' . esc_attr($value) . '"' . selected($options[$id], $value, false) . '>' . $label . '</option>';
                    echo '</select>';
                    if ($desc != '')
                        echo '<br /><span class="description">' . $desc . '</span>';
                    break;
                case 'radio':
                    $i = 0;
                    foreach ($choices as $value => $label){
                        echo '<input class="radio' . $field_class . '" type="radio" name="' . $this->key . '[' . $id . ']" id="' . $id . $i . '" value="' . esc_attr($value) . '" ' . checked($options[$id], $value, false) . '> <label for="' . $id . $i . '">' . $label . '</label>';
                        if ($i < count($options) - 1)
                            echo '<br />';
                        $i++;
                    }
                    if ($desc != '')
                        echo '<br /><span class="description">' . $desc . '</span>';
                    break;
                case 'textarea':
                    echo '<textarea class="' . $field_class . '" id="' . $id . '" name="' . $this->key . '[' . $id . ']" placeholder="' . $std . '" rows="5" cols="30">' . wp_htmledit_pre($options[$id]) . '</textarea>';
                    if ($desc != '')
                        echo '<br /><span class="description">' . $desc . '</span>';
                    break;
                case 'password':
                    echo '<input class="regular-text' . $field_class . '" type="password" id="' . $id . '" name="' . $this->key . '[' . $id . ']" value="' . esc_attr($options[$id]) . '" />';
                    if ($desc != '')
                        echo '<br /><span class="description">' . $desc . '</span>';
                    break;
                case 'text':
                default:
                    echo '<input class="regular-text' . $field_class . '" type="text" id="' . $id . '" name="' . $this->key . '[' . $id . ']" placeholder="' . $std . '" value="' . esc_attr($options[$id]) . '" />';
                    if ($desc != '')
                        echo '<br /><span class="description">' . $desc . '</span>';
                    break;
            }
        }


        /**
         * Simple helper for Wordpress Settings API
         *
         * @param array $args
         */

        public function createSetting($args = array())
        {
            $defaults = array(
                'id'      => 'default_field',
                'title'   => 'Default Field',
                'desc'    => 'This is a default description.',
                'std'     => '',
                'type'    => 'text',
                'section' => 'general',
                'choices' => array(),
                'class'   => ''
            );
            extract(wp_parse_args($args, $defaults));
            $field_args = array(
                'type'      => $type,
                'id'        => $id,
                'desc'      => $desc,
                'std'       => $std,
                'choices'   => $choices,
                'label_for' => $id,
                'class'     => $class
            );
            if ($type == 'checkbox'){ $this->adminCheckboxes[] = $id; }
            add_settings_field($id, $title, array($this, 'displaySetting'), $this->adminPage, $section, $field_args);
        }


        /***************** Plugin basic weapons ****************/

        /**
         * Let's check Wordpress version, and PHP version and tell those
         * guys whats needed to upgrade, if anything.
         *
         * @return bool
         */

        private function checkRequirements()
        {
            global $wp_version;
            if (!version_compare($wp_version, $this->wpVer, '>=')){
                $this->pluginDeactivate();
                add_action('admin_notices', array($this, 'displayVersionNotice'));
                return FALSE;
            } elseif (!version_compare(PHP_VERSION, $this->phpVer, '>=')){
                $this->pluginDeactivate();
                add_action('admin_notices', array($this, 'displayPHPNotice'));
                return FALSE;
            } elseif (!function_exists('mime_content_type') && !function_exists('finfo_file')){
                add_action('admin_notices', array($this, 'displayFunctionMissingNotice'));
                return TRUE;
            }
            return TRUE;
        }


        /**
         * Deactivates our plugin if anything goes wrong. Also, removes the
         * "Plugin activated" message, if we don't pass requriments check.
         */

        private function pluginDeactivate()
        {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            deactivate_plugins(plugin_basename(__FILE__));
            unset($_GET['activate']);
        }


        /**
         * Displays outdated wordpress messsage.
         */

        public function displayVersionNotice()
        {
            global $wp_version;
            $this->displayAdminError(
                'Sorry mate, this plugin requires at least WordPress varsion ' . $this->wpVer . ' or higher.
                You are currently using ' . $wp_version . '. Please upgrade your WordPress.');
        }


        /**
         * Displays outdated php message.
         */

        public function displayPHPNotice()
        {
            $this->displayAdminError(
                'You need PHP version at least '. $this->phpVer .' to run this plugin. You are currently using PHP version ' . PHP_VERSION . '.');
        }


        /**
         * Notify use about missing needed functions, and less security caused by that, let them hide nag of course.
         */

        public function displayFunctionMissingNotice()
        {
            $currentUser = wp_get_current_user();
            if (!get_user_meta($currentUser->ID, 'wpCommentAttachmentIgnoreNag') && current_user_can('install_plugins')){
                $this->displayAdminError((sprintf(
                    'It seems like your PHP installation is missing "mime_content_type" or "finfo_file" functions which are crucial '.
                    'for detecting file types of uploaded attachments. Please update your PHP installation OR be very careful with allowed file types, so '.
                    'intruders won\'t be able to upload dangerous code to your website! | <a href="%1$s">Hide Notice</a>', '?wpCommentAttachmentIgnoreNag=1')), 'updated');
            }
        }


        /**
         * Save user nag if set, if they want to hide the message above.
         */

        private function setUserNag()
        {
            $currentUser = wp_get_current_user();
            if (isset($_GET['wpCommentAttachmentIgnoreNag']) && '1' == $_GET['wpCommentAttachmentIgnoreNag'] && current_user_can('install_plugins')){
                add_user_meta($currentUser->ID, 'wpCommentAttachmentIgnoreNag', 'true', true);
            }
        }


        /**
         * Admin error helper
         *
         * @param $error
         */

        private function displayAdminError($error, $class="error") { echo '<div id="message" class="'. $class .'"><p><strong>' . $error . '</strong></p></div>';  }

    }
}

new wpCommentAttachment();