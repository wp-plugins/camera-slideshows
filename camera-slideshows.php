<?php
/**
 * Plugin Name:       Camera Slideshows
 * Plugin URI:        https://wordpress.org/plugins/camera-slideshows/
 * Description:       A jQuery slideshow with a responsive layout, easy to use with an extended admin panel
 * Version:           1.0.0
 * Author:            Sayful Islam, Sayful-IT
 * Author URI:        https://profiles.wordpress.org/sayfulit/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( !class_exists('Camera_Slideshows')):

class Camera_Slideshows {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	public function __construct(){
		add_action( 'init', array( $this, 'post_type'), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts') );

		add_shortcode('camera_slideshow', array( $this, 'get_new_slider') );

		$this->includes();
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

	public function includes(){
		require_once 'ShaplaTools_Metaboxs.php';
		require_once 'Camera_Slideshow_Metabox.php';
	}

	public function enqueue_scripts(){
		global $post;
		if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'camera_slideshow') ) {
			wp_enqueue_style('camera',plugins_url( '/css/camera.css' , __FILE__ ));

			wp_enqueue_script('camera-easing',plugins_url( '/js/jquery.easing.1.3.js' , __FILE__ ),array( 'jquery' ));
			wp_enqueue_script('camera',plugins_url( '/js/camera.min.js' , __FILE__ ),array( 'jquery' ));
		}
	}

	public function admin_scripts( $hook_suffix ){
		global $post_type;
		if( $post_type != 'camera' )
			return;

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style('camera-slideshows-admin',plugins_url( '/css/admin.css' , __FILE__ ));
		wp_enqueue_script( 'camera-color', plugins_url('/js/metabox-color.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	}

	public function post_type(){

		$labels = array(
			'name'                => _x( 'Slides', 'Post Type General Name', 'camera-slideshows' ),
			'singular_name'       => _x( 'Slide', 'Post Type Singular Name', 'camera-slideshows' ),
			'menu_name'           => __( 'Camera Slide', 'camera-slideshows' ),
			'parent_item_colon'   => __( 'Parent Slide:', 'camera-slideshows' ),
			'all_items'           => __( 'All Slides', 'camera-slideshows' ),
			'view_item'           => __( 'View Slide', 'camera-slideshows' ),
			'add_new_item'        => __( 'Add New Slide', 'camera-slideshows' ),
			'add_new'             => __( 'Add New', 'camera-slideshows' ),
			'edit_item'           => __( 'Edit Slide', 'camera-slideshows' ),
			'update_item'         => __( 'Update Slide', 'camera-slideshows' ),
			'search_items'        => __( 'Search Slide', 'camera-slideshows' ),
			'not_found'           => __( 'Not found', 'camera-slideshows' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'camera-slideshows' ),
		);
		$args = array(
			'label'               => __( 'slider', 'camera-slideshows' ),
			'description'         => __( 'Post Type Description', 'camera-slideshows' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'thumbnail', ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_icon'           => plugins_url('/images/camera_icon.png', __FILE__ ),
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		);
		register_post_type( 'camera', $args );
	}

	public function get_new_slider( $atts, $content=null ){
	    extract(shortcode_atts(array(
	        'id' =>'',
	    ), $atts));

		$image_ids 		= array_filter(explode(',', get_post_meta( $id, '_shapla_image_ids', true) ));

		$img_size 		= get_post_meta( $id, '_camera-slideshows_slide_img_size', true);
		$loader 		= get_post_meta( $id, '_camera_loader', true);
		$loader_color	= get_post_meta( $id, '_camera_loader_color', true);
		$loader_bg_color= get_post_meta( $id, '_camera_loader_bg_color', true);
		$time 			= get_post_meta( $id, '_camera_time', true);
		$transperiod 	= get_post_meta( $id, '_camera_transperiod', true);
		$navigation 	= get_post_meta( $id, '_camera_navigation', true);
		$pagination 	= get_post_meta( $id, '_camera_pagination', true);
		$play_pause 	= get_post_meta( $id, '_camera_play_pause', true);
		$hover 			= get_post_meta( $id, '_camera_hover', true);
		$thumbnails 	= get_post_meta( $id, '_camera_thumbnails', true);
		$bar_position 	= get_post_meta( $id, '_camera_bar_position', true);
		$effect 		= get_post_meta( $id, '_camera_effect', true);
		$portrait 		= get_post_meta( $id, '_camera_portrait', true);
		$height 		= get_post_meta( $id, '_camera_height', true);
		ob_start();
		?>
		<div id="camera">
			<div class="camera_wrap" id="camera_wrap-<?php echo $id; ?>">
				<?php
					foreach ($image_ids as $image_id) {
						$src = wp_get_attachment_image_src( $image_id, $img_size );
						$thumb = wp_get_attachment_image_src( $image_id, 'thumbnail' );
						$caption = get_post( $image_id )->post_excerpt ? get_post( $image_id )->post_excerpt : '';

						?>
						<div data-thumb="<?php echo $thumb[0]; ?>" data-src="<?php echo $src[0]; ?>" >
							<?php if( trim($caption) != '' ): ?>
								<div class="camera_caption fadeFromBottom"><?php echo $caption; ?></div>
							<?php endif; ?>
                		</div>
                		<?php
					}
				?>
			</div>
			<script type="text/javascript">
				jQuery(function(){
				  	jQuery('#camera_wrap-<?php echo $id; ?>').camera({
				  		navigation: <?php echo ($navigation == 'on') ? 'true' : 'false' ;?>,
				  		pagination: <?php echo ($pagination == 'on') ? 'true' : 'false' ;?>,
						thumbnails: <?php echo ($thumbnails == 'on') ? 'true' : 'false' ;?>,
						playPause: <?php echo ($play_pause == 'on') ? 'true' : 'false' ;?>,
						hover: <?php echo ($hover == 'on') ? 'true' : 'false' ;?>,
						loader: '<?php echo $loader;?>',
						time: <?php echo $time;?>,
						transPeriod: <?php echo $transperiod;?>,
						barPosition: '<?php echo $bar_position;?>',
						fx: '<?php echo $effect;?>',
						loaderColor: '<?php echo $loader_color;?>',
						loaderBgColor: '<?php echo $loader_bg_color;?>',
						portrait: <?php echo ($portrait == 'on') ? 'true' : 'false' ;?>,
						height: '<?php echo $height; ?>'
					});
				});
			</script>
		</div>
		<?php
		return ob_get_clean();
	}
}

add_action( 'plugins_loaded', array( 'Camera_Slideshows', 'get_instance' ) );
endif;