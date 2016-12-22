<?php

/**
 * Plugin Name: Read Me Later
 * Plugin URI: https://github.com/CorinneResoclick/Plugin-ReadMeLater
 * Description: This plugin allow you to add blog posts in read me later lists using Ajax
 * Version: 1.0.0
 * Author: Corinne
 * Author URI: https://github.com/CorinneResoclick
 * License: GPL3
 */

define( 'RML_DIR', plugin_dir_path( __FILE__ ) );
require(RML_DIR.'widget.php');
/**
 * Description of read-me-later
 *
 * @author Corinne
 */
class ReadMeLater {
        /*
         * Action hooks
         */
        public function run() {     
            
            // Enqueue plugin styles and scripts
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_rml_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_rml_styles' ) );  
            
            // Setup filter hook to show Read Me Later link
            add_filter( 'the_excerpt', array( $this, 'rml_button' ) );
            add_filter( 'the_content', array( $this, 'rml_button' ) );
            
            
            // Setup Ajax action hook
            add_action( 'wp_ajax_read_me_later', array( $this, 'read_me_later' ) );
            
        }  
        
        /**
         * Enqueues plugin-specific scripts.
         */
        public function enqueue_rml_scripts() {        
            
            wp_enqueue_script( 'rml-script', plugins_url( 'js/read-me-later.js', __FILE__ ), array('jquery'), null, true );
            //initial code : wp_localize_script( 'rml-script', 'readmelater_ajax', array( 'ajax_url' => admin_url('admin-ajax.php')) );
            //to ensure security
            wp_localize_script( 'rml-script', 'readmelater_ajax', array( 'ajax_url' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('rml-nonce') ) );
            
        }   
        
        /**
         * Enqueues plugin-specific styles.
         */
        public function enqueue_rml_styles() {    
            
            wp_enqueue_style( 'rml-style', plugins_url( 'css/read-me-later.css' , __FILE__ )); 
            
        }
        
        /**
         * Adds a read me later button at the bottom of each post excerpt that allows logged in users to save those posts in their read me later list.
         *
         * @param string $content
         * @returns string
         */
        public function rml_button( $content ) {   
            
            $html = null;
            // Show read me later link only when the user is logged in
            if( is_user_logged_in() && get_post_type() == 'post' ) {
                $html .= '<a href="#" class="rml_bttn" data-id="' . get_the_id() . '">Read Me Later</a>';
                $content .= $html;
            }
            return $content;      
            
        }
        
        public function read_me_later() {
            
            check_ajax_referer( 'rml-nonce', 'security' );
            
            $rml_post_id = $_POST['post_id']; 
            $echo = array();       
            if(get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true ) !== null ) {
                $value = get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true );
            }

            if( $value ) {
                $echo = $value;
                array_push( $echo, $rml_post_id );
            }
            else {
                $echo = array( $rml_post_id );
            }

            update_user_meta( wp_get_current_user()->ID, 'rml_post_ids', $echo );
            $ids = get_user_meta( wp_get_current_user()->ID, 'rml_post_ids', true );
            
            // Query read me later posts
            $args = array( 
                'post_type' => 'post',
                'orderby' => 'DESC', 
                'posts_per_page' => -1, 
                'numberposts' => -1,
                'post__in' => $ids
            );

            $rmlposts = get_posts( $args );
            if( $ids ) :
                global $post;
                foreach ( $rmlposts as $post ) :
                    setup_postdata( $post );
                    $img = wp_get_attachment_image_src( get_post_thumbnail_id() ); 
                ?>          
                    <div class="rml_posts">                 
                        <div class="rml_post_content">
                            <h5><a href="<?php echo get_the_permalink(); ?>"><?php the_title(); ?></a></h5>
                            <p><?php the_excerpt(); ?></p>
                        </div>
                        <img src="<?php echo $img[0]; ?>" alt="<?php echo get_the_title(); ?>" class="rml_img">                    
                    </div>
                <?php 
                endforeach; 
                wp_reset_postdata(); 
            endif;      

            // Always die in functions echoing Ajax content
            die();
            
        }
  
}

$rml = new ReadMeLater();
$rml->run();
?>
