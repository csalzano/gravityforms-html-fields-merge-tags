<?php
/**
 * Plugin Name: Gravity Forms Merge Tags in HTML Fields
 * Plugin URI: https://coreysalzano.com
 * Description: An add-on for Gravity Forms to enable merge tags in HTML fields
 * Version: 0.1.0
 * Author: Corey Salzano
 * Author URI: https://coreysalzano.com
 * Text Domain: gravityforms-html-merge-tags
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


class Breakfast_HTML_Fields_Merge_Tags{

	function hooks() {
		/**
		 * Allow HTML field contents to use merge tags to insert field values
		 * from previous pages. This makes it easy to create a final page with a
		 * "Please Review Your Entry" HTML field that shows the user critical
		 * values they entered on previous pages.
		 */
		add_filter( 'gform_field_content', array( $this, 'enable_merge_tags_in_html_fields' ), 10, 5 );
	}

	function enable_merge_tags_in_html_fields( $field_content, $field, $value, $entry_id, $form_id ) {

		if( 'html' != $field->type || false === strpos( $field_content, '{' ) ) {
			return $field_content;
		}

		//Borrowing this regex from GF_Common
		// Replacing field variables: {FIELD_LABEL:FIELD_ID} {My Field:2}.
		preg_match_all( '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $field_content, $matches, PREG_SET_ORDER );
		if ( ! is_array( $matches ) ) {
			return $field_content;
		}

		foreach ( $matches as $match ) {
			$input_id = $match[1];

			//Easy, fields with one input element
			if( ! empty( rgpost( 'input_' . $input_id ) ) ) {
				$field_content = str_replace( $match[0], rgpost( 'input_' . $input_id ), $field_content );
				continue;
			} else {
				// No value? Don't output anything. Needed for optional fields.
				$field_content = str_replace( $match[0], '', $field_content );
			}

			/**
			 * Perhaps the field has multiple inputs, like the name field or
			 * a list of strings
			 */
			$tag_field = RGFormsModel::get_field( $form_id, $input_id );
			if( ! is_array( $tag_field->inputs ) ) {
				//nope
				continue;
			}
			$merge_tag_values = array();
			foreach( $tag_field->inputs as $key => $input ) {
				if( isset( $input['isHidden'] ) && $input['isHidden'] ) {
					continue;
				}

				//this ID is one of the ones you want
				$input_id = str_replace( '.', '_', $input['id'] );
				if( empty( rgpost( 'input_' . $input_id ) ) ) {
					continue;
				}
				$merge_tag_values[] = rgpost( 'input_' . $input_id );
			}

			/**
			 * The glue character in this implode() should be a space for
			 * the Name type field, but probably a line break or comma and
			 * space for the checkbox and list fields.
			 */
			$glue_characters = ' ';
			switch( $tag_field->type ) {
				case 'checkbox':
				case 'list':
					$glue_characters = ', ';
					break;
			}

			$field_content = str_replace( $match[0], implode( $glue_characters, $merge_tag_values ), $field_content );
		}
		return $field_content;
	}
}
$breakfast_html_fields_merge_tags = new Breakfast_HTML_Fields_Merge_Tags();
$breakfast_html_fields_merge_tags->hooks();
