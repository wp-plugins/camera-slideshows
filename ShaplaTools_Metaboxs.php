<?php

if ( !class_exists('ShaplaTools_Metaboxs')):

class ShaplaTools_Metaboxs {


	public function __construct(){
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
		add_action( 'wp_ajax_save_images', array( $this, 'save_images' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_scripts' ) );
	}

	/**
	 * Add a custom meta box
	 * 
	 * @package ShaplaTools
	 * @param array $meta_box Meta box input data
	 */
	public function shapla_add_meta_box( $meta_box ) {
		if( !is_array( $meta_box) ) return false;

		add_meta_box( $meta_box['id'], $meta_box['title'], array( $this, 'meta_box_callback'), $meta_box['page'], $meta_box['context'], $meta_box['priority'], $meta_box );
	}

	public function meta_box_callback( $post, $meta_box ){
		return $this->shapla_create_meta_box( $post, $meta_box["args"] );
	}

	/**
	 * Create content for the custom meta box
	 * 
	 * @param array $meta_box Meta box input data
	 */
	public function shapla_create_meta_box( $post, $meta_box ) {
		if( !is_array( $meta_box) ) return false;

		if( isset($meta_box['description']) && $meta_box['description'] != '' ){
			echo '<p>'. $meta_box['description'] .'</p>';
		}

		wp_nonce_field( basename(__FILE__), 'shapla_meta_box_nonce' );

		echo '<table class="form-table shapla-metabox-table">';

		foreach( $meta_box['fields'] as $field ){

			$meta = get_post_meta( $post->ID, $field['id'], true );

			echo '<tr><th><label for="'. $field['id'] .'"><strong>'. $field['name'] .'</strong>
				 <span>'. $field['desc'] .'</span></label></th>';

			switch( $field['type'] ){
				case 'text':
					echo '<td><input type="text" name="shapla_meta['. $field['id'] .']" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['std']) .'" size="30" /></td>';
				break;

				case 'textarea':
					echo '<td><textarea name="shapla_meta['. $field['id'] .']" id="'. $field['id'] .'" rows="8" cols="5">'. ($meta ? $meta : $field['std']) .'</textarea></td>';
				break;

				case 'file':

					$multiple = ( isset( $field['multiple'] ) ) ? true : false;

					?>

					<script>
					jQuery(function($){
						var frame,
							isMultiple = "<?php echo $multiple; ?>";

						$('#<?php echo $field['id']; ?>_button').on('click', function(e) {
							e.preventDefault();

							var options = {
								state: 'insert',
								frame: 'post',
								multiple: isMultiple
							};

							frame = wp.media(options).open();

							frame.menu.get('view').unset('gallery');
							frame.menu.get('view').unset('featured-image');

							frame.toolbar.get('view').set({
								insert: {
									style: 'primary',
									text: '<?php _e("Insert", "shapla"); ?>',

									click: function() {
										var models = frame.state().get('selection'),
											url = models.first().attributes.url,
											files = [];

										if( isMultiple ) {
											models.map (function( attachment ) {
												attachment = attachment.toJSON();
												files.push(attachment.url);
												url = files;
											});
										}

										$('#<?php echo $field['id']; ?>').val( url );

										frame.close();
									}
								}
							});
						});
					});
					</script>

					<?php
					echo '<td><input type="text" name="shapla_meta['. $field['id'] .']" id="'. $field['id'] .'" value="'. ($meta ? $meta : $field['std']) .'" size="30" class="file" /> <input type="button" class="button" name="'. $field['id'] .'_button" id="'. $field['id'] .'_button" value="Browse" /></td>';
				break;

				case 'images':
				    ?>
				    <script>
				    jQuery(function($){
				        var frame,
				            images = '<?php echo get_post_meta( $post->ID, '_shapla_image_ids', true ); ?>',
				            selection = loadImages(images);

				        $('#shapla_images_upload').on('click', function(e) {
				            e.preventDefault();
				            var options = {
				                title: '<?php _e("Create Featured Gallery", "shapla"); ?>',
				                state: 'gallery-edit',
				                frame: 'post',
				                selection: selection
				            };

				            if( frame || selection ) {
				                options['title'] = '<?php _e("Edit Featured Gallery", "shapla"); ?>';
				            }

				            frame = wp.media(options).open();
				            
				            // Tweak Views
				            frame.menu.get('view').unset('cancel');
				            frame.menu.get('view').unset('separateCancel');
				            frame.menu.get('view').get('gallery-edit').el.innerHTML = '<?php _e("Edit Featured Gallery", "shapla"); ?>';
				            frame.content.get('view').sidebar.unset('gallery'); // Hide Gallery Settings in sidebar

				            // when editing a gallery
				            overrideGalleryInsert();
				            frame.on( 'toolbar:render:gallery-edit', function() {
				                overrideGalleryInsert();
				            });

				            frame.on( 'content:render:browse', function( browser ) {
				                if ( !browser ) return;
				                // Hide Gallery Settings in sidebar
				                browser.sidebar.on('ready', function(){
				                    browser.sidebar.unset('gallery');
				                });
				                // Hide filter/search as they don't work
				                browser.toolbar.on('ready', function(){
				                    if(browser.toolbar.controller._state == 'gallery-library'){
				                        browser.toolbar.$el.hide();
				                    }
				                });
				            });

				            // All images removed
				            frame.state().get('library').on( 'remove', function() {
				                var models = frame.state().get('library');
				                if(models.length == 0){
				                    selection = false;
				                    $.post(ajaxurl, { ids: '', action: 'save_images', post_id: shapla_ajax.post_id, nonce: shapla_ajax.nonce });
				                }
				            });

				            function overrideGalleryInsert(){
				                frame.toolbar.get('view').set({
				                    insert: {
				                        style: 'primary',
				                        text: '<?php _e("Save Featured Gallery", "shapla"); ?>',
				                        click: function(){
				                            var models = frame.state().get('library'),
				                                ids = '';

				                            models.each( function( attachment ) {
				                                ids += attachment.id + ','
				                            });

				                            this.el.innerHTML = '<?php _e("Saving...", "shapla"); ?>';

				                            $.ajax({
				                                type: 'POST',
				                                url: ajaxurl,
				                                data: { 
				                                    ids: ids, 
				                                    action: 'save_images', 
				                                    post_id: shapla_ajax.post_id, 
				                                    nonce: shapla_ajax.nonce 
				                                },
				                                success: function(){
				                                    selection = loadImages(ids);
				                                    $('#_shapla_image_ids').val( ids );
				                                    frame.close();
				                                },
				                                dataType: 'html'
				                            }).done( function( data ) {
				                                $('.shapla-gallery-thumbs').html( data );
				                                console.log(data);
				                            });
				                        }
				                    }
				                });
				            }

				        });

				        function loadImages(images){
				            if (images){
				                var shortcode = new wp.shortcode({
				                    tag:      'gallery',
				                    attrs:    { ids: images },
				                    type:     'single'
				                });

				                var attachments = wp.media.gallery.attachments( shortcode );

				                var selection = new wp.media.model.Selection( attachments.models, {
				                    props:    attachments.props.toJSON(),
				                    multiple: true
				                });

				                selection.gallery = attachments.gallery;

				                selection.more().done( function() {
				                    // Break ties with the query.
				                    selection.props.set({ query: false });
				                    selection.unmirror();
				                    selection.props.unset('orderby');
				                });
				                
				                return selection;
				            }
				            return false;
				        }
				    });
				    </script>

				    <?php

				    $meta = get_post_meta( $post->ID, '_shapla_image_ids', true );
				    $thumbs_output = '';
				    $button_text = ($meta) ? __('Edit Gallery', 'shapla') : $field['std'];
				    if( $meta ) {
				        $field['std'] = __('Edit Gallery', 'shapla');
				        $thumbs = explode(',', $meta);
				        $thumbs_output = '';
				        foreach( $thumbs as $thumb ) {
				            $thumbs_output .= '<li>' . wp_get_attachment_image( $thumb, array(75,75) ) . '</li>';
				        }
				    }

				    echo '<td class="shapla-box-'.$field['type'].'">
				            <input type="button" class="button" name="' . $field['id'] . '" id="shapla_images_upload" value="' . $button_text .'" />
				            <input type="hidden" name="shapla_meta[_shapla_image_ids]" id="_shapla_image_ids" value="' . ($meta ? $meta : 'false') . '" />
				            <ul class="shapla-gallery-thumbs">' . $thumbs_output . '</ul>
				        </td>';
			    break;

				case 'select':
					echo'<td><select name="shapla_meta['. $field['id'] .']" id="'. $field['id'] .'">';
					foreach( $field['options'] as $key => $option ){
						echo '<option value="' . $key . '"';
						if( $meta ){ 
							if( $meta == $key ) echo ' selected="selected"'; 
						} else {
							if( $field['std'] == $key ) echo ' selected="selected"'; 
						}
						echo'>'. $option .'</option>';
					}
					echo'</select></td>';
				break;

				case 'radio':
					echo '<td>';
					foreach( $field['options'] as $key => $option ){
						echo '<label class="radio-label"><input type="radio" name="shapla_meta['. $field['id'] .']" value="'. $key .'" class="radio"';
						if( $meta ){ 
							if( $meta == $key ) echo ' checked="checked"'; 
						} else {
							if( $field['std'] == $key ) echo ' checked="checked"';
						}
						echo ' /> '. $option .'</label> ';
					}
					echo '</td>';
				break;

				case 'color':
					echo '<td class="shapla-box-'.$field['type'].'"><input data-default-color="'.@$field['std'].'" type="text" id="'. $field['id'] .'" name="shapla_meta[' . $field['id'] .']" value="'. ($meta ? $meta : $field['std']) .'" class="colorpicker"></td>';
				break;

				case 'checkbox':
				    echo '<td>';
				    $val = '';
		            if( $meta ) {
		                if( $meta == 'on' ) $val = ' checked="checked"';
		            } else {
		                if( $field['std'] == 'on' ) $val = ' checked="checked"';
		            }

		            echo '<input type="hidden" name="shapla_meta['. $field['id'] .']" value="off" />
		            <input type="checkbox" id="'. $field['id'] .'" name="shapla_meta['. $field['id'] .']" value="on"'. $val .' /> ';
				    echo '</td>';
			    break;

			}

			echo '</tr>';

		}

		echo '</table>';
	}

	/**
	 * Save custom meta box
	 *
	 * @param int $post_id The post ID
	 */
	function save_meta_box( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( !isset($_POST['shapla_meta']) || !isset($_POST['shapla_meta_box_nonce']) || !wp_verify_nonce( $_POST['shapla_meta_box_nonce'], basename( __FILE__ ) ) )
			return;

		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) ) return;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ) ) return;
		}

		foreach( $_POST['shapla_meta'] as $key => $val ){
			update_post_meta( $post_id, $key, stripslashes(htmlspecialchars($val)) );
		}
	}
	function save_images(){
	    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
	        return;
	    }

	    if ( !isset($_POST['ids']) || !isset($_POST['nonce']) || !wp_verify_nonce( $_POST['nonce'], 'shapla-ajax' ) ){
	        return;
	    }

	    if ( !current_user_can( 'edit_posts' ) ) return;

	    $ids = strip_tags(rtrim($_POST['ids'], ','));
	    update_post_meta($_POST['post_id'], '_shapla_image_ids', $ids);

	    $thumbs = explode(',', $ids);
	    $thumbs_output = '';
	    foreach( $thumbs as $thumb ) {
	        $thumbs_output .= '<li>' . wp_get_attachment_image( $thumb, array(75,75) ) . '</li>';
	    }

	    echo $thumbs_output;

	    die();
	}

	function metabox_scripts(){
	    global $post;
	    if( isset($post) ) {
	        wp_localize_script( 'jquery', 'shapla_ajax', array(
	            'post_id' => $post->ID,
	            'nonce' => wp_create_nonce( 'shapla-ajax' )
	        ) );
	    }
	}
}

if(is_admin())
	$ShaplaTools_Metaboxs = new ShaplaTools_Metaboxs();

endif;