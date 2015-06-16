<?php

if( !class_exists('camera_Metabox') ):

class camera_Metabox {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_head', array( $this, 'admin_style') );

		add_filter( 'manage_edit-camera_columns', array ($this, 'columns_head') );
		add_action( 'manage_camera_posts_custom_column', array ($this, 'columns_content') );
	}

	public function admin_style(){
		global $post_type;
		if( $post_type != 'camera' )
			return;

		$style  ='<style type="text/css">';
		$style .='#postimagediv {display: none;}';
		$style .='#slider-thumbs li {display: inline;margin-right: 6px;margin-bottom: 6px;}';
		$style .='</style>';

		echo $style;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function available_img_size(){
	    $cameratools_img_size = get_intermediate_image_sizes();
	    array_push($cameratools_img_size, 'full');

	    $singleArray = array();

	    foreach ($cameratools_img_size as $key => $value){

	        $singleArray[$value] = $value;
	    }

	    return $singleArray;
	}

	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box() {
		$meta_box = array(
		    'id' => 'camera-metabox-slide',
		    'title' => __('Slide Settings', 'camera-slideshows'),
		    'description' => __('To use this slider in your posts or pages use the following shortcode:<pre><code>[camera_slideshow id="'.get_the_ID().'"]</code></pre><br>', 'camera-slideshows'),
		    'page' => 'camera',
		    'context' => 'normal',
		    'priority' => 'high',
		    'fields' => array(
		        array(
		            'name' => __('Slider Images', 'camera-slideshows'),
		            'desc' => __('Choose slider images.', 'camera-slideshows'),
		            'id' => '_camera_slide_images',
		            'type' => 'images',
		            'std' => __('Upload Images', 'camera-slideshows')
		        ),
		        array(
		            'name' => __('Slider Image Size', 'camera-slideshows'),
		            'desc' => __('Select image size from available image size. Use full for original image size.', 'camera-slideshows'),
		            'id' => '_camera_slide_img_size',
		            'type' => 'select',
		            'std' => 'full',
		            'options' => $this->available_img_size()
		        ),
		        array(
		            'name' => __('Loder', 'camera-slideshows'),
		            'desc' => __('even if you choose "pie", old browsers like IE8- can\'t display it... they will display always a loading bar.', 'camera-slideshows'),
		            'id' => '_camera_loader',
		            'type' => 'select',
		            'std' => 'bar',
		            'options' => array(
		            	'bar' 		=> __('bar', 'camera-slideshows'),
		            	'pie' 	=> __('pie', 'camera-slideshows'),
		            	'none ' 	=> __('none ', 'camera-slideshows'),
		            )
		        ),
		        array(
		            'name' => __('Loader Color', 'camera-slideshows'),
		            'desc' => __('', 'camera-slideshows'),
		            'id' => '_camera_loader_color',
		            'type' => 'color',
		            'std' => '#eeeeee',
		        ),
		        array(
		            'name' => __('Loader Background Color', 'camera-slideshows'),
		            'desc' => __('', 'camera-slideshows'),
		            'id' => '_camera_loader_bg_color',
		            'type' => 'color',
		            'std' => '#222222',
		        ),
		        array(
		            'name' => __('Bar Position', 'camera-slideshows'),
		            'desc' => __('', 'camera-slideshows'),
		            'id' => '_camera_bar_position',
		            'type' => 'select',
		            'std' => 'top',
		            'options' => array(
		            	'top' 		=> __('top', 'camera-slideshows'),
		            	'bottom' 	=> __('bottom', 'camera-slideshows'),
		            	'left ' 	=> __('left ', 'camera-slideshows'),
		            	'right ' 	=> __('right ', 'camera-slideshows'),
		            )
		        ),
		        array(
		            'name' => __('Effect', 'camera-slideshows'),
		            'desc' => __(" you can also use more than one effect, just separate them with commas: 'simpleFade, scrollRight, scrollBottom'", 'camera-slideshows'),
		            'id' => '_camera_effect',
		            'type' => 'text',
		            'std' => 'random'
		        ),
		        array(
		            'name' => __('Transition Time', 'camera-slideshows'),
		            'desc' => __('milliseconds between the end of the sliding effect and the start of the nex one', 'camera-slideshows'),
		            'id' => '_camera_time',
		            'type' => 'text',
		            'std' => '7000'
		        ),
		        array(
		            'name' => __('Transition Period', 'camera-slideshows'),
		            'desc' => __('length of the sliding effect in milliseconds', 'camera-slideshows'),
		            'id' => '_camera_transperiod',
		            'type' => 'text',
		            'std' => '1500'
		        ),
		        array(
		            'name' => __('Slider Hight', 'camera-slideshows'),
		            'desc' => __('here you can type pixels (for instance \'300px\'), a percentage (relative to the width of the slideshow, for instance \'50%\') or auto', 'camera-slideshows'),
		            'id' => '_camera_height',
		            'type' => 'text',
		            'std' => '50%'
		        ),
		        array(
		            'name' => __('Navigation', 'camera-slideshows'),
		            'desc' => __('if checked the navigation button (prev, next and play/stop buttons) will be visible, if unchecked they will be always hidden', 'camera-slideshows'),
		            'id' => '_camera_navigation',
		            'type' => 'checkbox',
		            'std' => 'on'
		        ),
		        array(
		            'name' => __('Pagination', 'camera-slideshows'),
		            'desc' => __('if checked the pagination will be visible, if unchecked they will be always hidden', 'camera-slideshows'),
		            'id' => '_camera_pagination',
		            'type' => 'checkbox',
		            'std' => 'on'
		        ),
		        array(
		            'name' => __('Play Pause Button', 'camera-slideshows'),
		            'desc' => __('to display or not the play/pause buttons', 'camera-slideshows'),
		            'id' => '_camera_play_pause',
		            'type' => 'checkbox',
		            'std' => 'on'
		        ),
		        array(
		            'name' => __('Pause on hover', 'camera-slideshows'),
		            'desc' => __(' Pause on state hover. Not available for mobile devices', 'camera-slideshows'),
		            'id' => '_camera_hover',
		            'type' => 'checkbox',
		            'std' => 'on'
		        ),
		        array(
		            'name' => __('Thumbnails', 'camera-slideshows'),
		            'desc' => __(' Check to enable thumbnails', 'camera-slideshows'),
		            'id' => '_camera_thumbnails',
		            'type' => 'checkbox',
		            'std' => ''
		        ),
		        array(
		            'name' => __('Image Crop', 'camera-slideshows'),
		            'desc' => __(' Check  if you don\'t want that your images are cropped', 'camera-slideshows'),
		            'id' => '_camera_portrait',
		            'type' => 'checkbox',
		            'std' => ''
		        ),
		    )
		);
		$cameraTools_Metaboxs = new ShaplaTools_Metaboxs();
		$cameraTools_Metaboxs->shapla_add_meta_box($meta_box);
	}

	public function columns_head( $defaults ) {
		unset( $defaults['date'] );

		$defaults['id'] 		= __( 'Slide ID', 'camera' );
		$defaults['shortcode'] 	= __( 'Shortcode', 'camera' );
		$defaults['images'] 	= __( 'Images', 'camera' );

		return $defaults;
	}

	public function columns_content( $column_name ) {

		$image_ids 	= explode(',', get_post_meta( get_the_ID(), '_shapla_image_ids', true) );

		if ( 'id' == $column_name ) {
			echo get_the_ID();
		}

		if ( 'shortcode' == $column_name ) {
			echo '<pre><code>[camera_slideshow id="'.get_the_ID().'"]</pre></code>';
		}

		if ( 'images' == $column_name ) {
			?>
			<ul id="slider-thumbs" class="slider-thumbs">
				<?php

				foreach ( $image_ids as $image ) {
					if(!$image) continue;
					$src = wp_get_attachment_image_src( $image, array(50,50) );
					echo "<li><img src='{$src[0]}' width='{$src[1]}' height='{$src[2]}'></li>";
				}

				?>
			</ul>
			<?php
		}
	}
}

function run_camera_meta(){
	if (is_admin())
		camera_Metabox::get_instance();
}
run_camera_meta();
endif;