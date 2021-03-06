<?php
/**
 * Plugin Name:       Oscar Minc
 * Plugin URI:        https://github.com/culturagovbr/
 * Description:       @TODO
 * Version:           1.1.0
 * Author:            Ricardo Carvalho
 * Author URI:        https://github.com/darciro/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!class_exists('OscarMinC')) :

    class OscarMinC
    {
        public function __construct()
        {
            require_once dirname( __FILE__ ) . '/inc/options-page.php';

            register_activation_hook(__FILE__, array($this, 'activate_oscar_minc'));
            add_action('init', array($this, 'inscricao_cpt'));
            add_filter('manage_inscricao_posts_columns', array($this, 'add_inscricao_columns'));
			add_action( 'add_meta_boxes_inscricao', array($this, 'oscar_minc_meta_boxes') );
			add_action( 'save_post_inscricao', array($this, 'oscar_video_save_post_meta_box') );
            add_action('manage_posts_custom_column', array($this, 'inscricao_custom_columns'), 10, 2);
            add_action('init', array($this, 'oscar_shortcodes'));
            add_action('acf/pre_save_post', array($this, 'preprocess_main_form'));
            add_action('acf/save_post', array($this, 'postprocess_main_form'));
            add_action('get_header', 'acf_form_head');
            add_action('wp_enqueue_scripts', array($this, 'register_oscar_minc_styles'));
            add_action('admin_enqueue_scripts', array($this, 'register_oscar_minc_admin_styles'));
            add_action('wp_enqueue_scripts', array($this, 'register_oscar_minc_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'register_oscar_minc_admin_scripts'));
            add_filter('wp_mail_content_type', array($this, 'set_email_content_type'));
            add_filter('wp_mail_from', array($this, 'oscar_minc_wp_mail_from'));
            add_filter('wp_mail_from_name', array($this, 'oscar_minc_wp_mail_from_name'));
            add_action('wp_ajax_upload_oscar_video', array($this, 'upload_oscar_video'));
            add_action('wp_ajax_nopriv_upload_oscar_video', array($this, 'upload_oscar_video'));
            add_action('show_user_profile', array($this, 'oscar_user_cnpj_field'));
            add_action('edit_user_profile', array($this, 'oscar_user_cnpj_field'));
            add_action('personal_options_update', array($this, 'update_user_cnpj'));
            add_action('edit_user_profile_update', array($this, 'update_user_cnpj'));
        }

        /**
         * Fired during plugin activation, check for dependency
         *
         */
        public static function activate_oscar_minc()
        {
            if (!is_plugin_active('advanced-custom-fields-pro/acf.php') && !is_plugin_active('advanced-custom-fields/acf.php')) {
                echo 'Para que este plugin funcione corretamente, é necessário a instalação e ativação do plugin ACF - <a href="http://advancedcustomfields.com/" target="_blank">Advanced custom fields</a>.';
                die;
            }
        }

        /**
         * Create a custom post type to manage indications
         *
         */
        public function inscricao_cpt()
        {
            register_post_type('inscricao', array(
                    'labels' => array(
                        'name' => 'Inscrições Oscar',
                        'singular_name' => 'Inscrição',
                        'add_new' => 'Nova inscrição',
                        'add_new_item' => 'Nova inscrição',
                    ),
                    'description' => 'Inscrições OscarMinC',
                    'public' => true,
                    'exclude_from_search' => false,
                    'publicly_queryable' => false,
                    'supports' => array('title'),
                    'menu_icon' => 'dashicons-clipboard')
            );
        }

		/**
         * Add's a meta box for showing movie data
         *
		 * @param $post
		 */
		public function oscar_minc_meta_boxes( $post ) {
			add_meta_box(
				'oscar-video-post',
				'Dados do filme',
				array($this, 'oscar_video_post_meta_box'),
				'inscricao',
				'side',
				'high'
			);
		}

		/**
         * Render a meta box for showing movie data
         *
		 * @param $post
		 */
		public function oscar_video_post_meta_box( $post ) {
			$oscar_movie_id = get_post_meta($post->ID, 'movie_attachment_id', true);
			$movie_enabled_to_comission = get_post_meta($post->ID, 'movie_enabled_to_comission', true);
			$post_author_id = get_post_field('post_author', $post->ID);
			$post_author = get_user_by('id', $post_author_id);
			add_thickbox(); ?>

            <div id="oscar-movie-id-<?php echo $post->ID; ?>" class="oscar-thickbox-modal">
                <div class="oscar-thickbox-modal-body">
					<?php echo do_shortcode('[video src="'. wp_get_attachment_url( $oscar_movie_id ) .'"]'); ?>
                    <h4><b>Filme: </b><?php echo get_field('titulo_do_filme', $post->ID); ?></h4>
                    <p><b>Proponente: <?php echo $post_author->display_name; ?></b></p>
                </div>
            </div>

            <div class="misc-pub-section">
                Filme: <b><?php echo $oscar_movie_id ? '<a href="#TB_inline?width=600&height=400&inlineId=oscar-movie-id-'. $post->ID .'" class="thickbox oscar-thickbox-link" target="_blank">' . get_field('titulo_do_filme', $post->ID) . '</a>' : get_field('titulo_do_filme', $post->ID) .' (Filme não enviado)'; ?></b>
            </div>
            <div class="misc-pub-section">
                <label for="enable-movie-to-comission">
                    <input id="enable-movie-to-comission" name="enable-movie-to-comission" type="checkbox" value="1" <?php echo $oscar_movie_id ? '' : 'disabled'; ?> <?php echo $movie_enabled_to_comission ? 'checked="true"' : ''; ?>>
                    Habilitar filme para a comissão.
                </label>
            </div>
            <div class="misc-pub-section">
                <label for="detach-movie-id">
                    <input id="detach-movie-id" name="detach-movie-id" type="checkbox" value="1" onclick="confirmDetach()" <?php echo $oscar_movie_id ? '' : 'disabled'; ?>>
                    Desvincular vídeo da inscrição
                </label>
                <p class="description">Isso permite que o proponente possa reenviar o filme para esta inscrição.</p>
            </div>
            <script type="text/javascript">
                function confirmDetach() {
                    var check = window.document.getElementById('detach-movie-id').checked,
                        str = 'Tem certeza que deseja desvincular o filme para esta inscrição? Isso não poderá ser desfeito.',
                        detachInput = document.getElementById('detach-movie-id');

                    if (detachInput.checked === true) {
                        if (window.confirm(str)) {
                            detachInput.checked = check;
                            window.document.getElementById('enable-movie-to-comission').checked = false;
                            jQuery('#enable-movie-to-comission').attr('disabled', true);
                        } else {
                            detachInput.checked = (!check);
                            jQuery('#enable-movie-to-comission').removeAttr('disabled');
                        }
                    }
                }
            </script>
		<?php }

		/**
         * Handle data process for meta box
         *
		 * @param $post_id
		 * @return mixed
		 */
		public function oscar_video_save_post_meta_box( $post_id )
        {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}

			if ( isset( $_POST['post_type'] ) && 'inscricao' === $_POST['post_type'] ) {
				update_post_meta($post_id, 'movie_enabled_to_comission', $_POST['enable-movie-to-comission']);

				if( isset( $_POST['detach-movie-id'] ) ){
					delete_post_meta( $post_id, 'movie_enabled_to_comission');
					delete_post_meta( $post_id, 'movie_attachment_id');
                }

			}
        }

        /**
         * Add new columns to our custom post type
         *
         * @param $columns
         * @return array
         */
        public function add_inscricao_columns($columns)
        {
            unset($columns['author']);
            return array_merge($columns, array(
                'responsible' => 'Proponente',
                'user_cnpj' => 'CNPJ',
                'movie' => 'Filme'
            ));
        }

        /**
         * Fill custom columns with data
         *
         * @param $column
         * @param $post_id
         */
        public function inscricao_custom_columns($column, $post_id)
        {
            $post_author_id = get_post_field('post_author', $post_id);
            $post_author = get_user_by('id', $post_author_id);
			add_thickbox();

            switch ($column) {
                case 'responsible':
                    echo $post_author->display_name;
                    break;
                case 'user_cnpj':
                    echo $this->mask(get_user_meta($post_author_id, '_user_cnpj', true), '##.###.###/####-##');
                    break;
                case 'movie':
					$oscar_movie_id = get_post_meta( $post_id, 'movie_attachment_id', true ); ?>

                    <div id="oscar-movie-id-<?php echo $post_id; ?>" class="oscar-thickbox-modal">
                        <div class="oscar-thickbox-modal-body">
                            <?php echo do_shortcode('[video src="'. wp_get_attachment_url( $oscar_movie_id ) .'"]'); ?>
                            <h4><b>Filme: </b><?php echo get_field('titulo_do_filme', $post_id); ?></h4>
                            <p><b>Proponente: <?php echo $post_author->display_name; ?></b></p>
                        </div>
                    </div>

                    <?php
                    echo $oscar_movie_id ? '<a href="#TB_inline?width=600&height=400&inlineId=oscar-movie-id-'. $post_id .'" class="thickbox oscar-thickbox-link">' . get_field('titulo_do_filme', $post_id) . '<br><small style="color: green;">Filme enviado</small></a>' : get_field('titulo_do_filme', $post_id) . '<br><small style="color: red;">Filme não enviado</small>';
                    break;
            }
        }

        /**
         * Shortcode to show ACF form
         *
         * @param $atts
         * @return string
         */
        public function oscar_shortcodes($atts)
        {
            require_once plugin_dir_path( __FILE__ ) . 'inc/shortcodes.php';
            $oscar_minc_shortcodes = new Oscar_Minc_Shortcodes();
        }

        /**
         * Process data before save indication post
         *
         * @param $post_id
         * @return int|void|WP_Error
         */
        public function preprocess_main_form($post_id)
        {
            if ($post_id != 'new_inscricao') {
                return $post_id;
            }

            if (is_admin()) {
                return;
            }

            $post = get_post($post_id);
            $post = array('post_type' => 'inscricao', 'post_status' => 'publish');
            $post_id = wp_insert_post($post);

            $inscricao = array('ID' => $post_id, 'post_title' => 'Inscrição - (ID #' . $post_id . ')');
            wp_update_post($inscricao);

            // Return the new ID
            return $post_id;
        }

        /**
         * Notify the monitors about a new indication
         *
         * @param $post_id
         */
        public function postprocess_main_form($post_id)
        {
			$update = get_post_meta( $post_id, '_inscription_validated', true );
			if ( $update ) {
				return;
			}

			$user = wp_get_current_user();
			$user_cnpj = get_user_meta( $user->ID, '_user_cnpj', true );
			$oscar_minc_options = get_option('oscar_minc_options');
            $monitoring_emails = explode(',', $oscar_minc_options['oscar_minc_monitoring_emails']);
            $to = array_map('trim', $monitoring_emails);
            $headers[] = 'From: ' . bloginfo('name') . ' <automatico@cultura.gov.br>';
            $headers[] = 'Reply-To: ' . $oscar_minc_options['oscar_minc_email_from_name'] . ' <' . $oscar_minc_options['oscar_minc_email_from'] . '>';
            $subject = 'Nova inscrição ao Oscar.';

            $body = '<h1>Olá,</h1>';
            $body .= '<p>Uma nova inscrição foi recebida em Oscar.</p><br>';
            $body .= '<p>Proponente: <b>' . $user->display_name . '</b></p>';
            $body .= '<p>CNPJ: <b>' . $this->mask($user_cnpj, '##.###.###/####-##') . '</b></p>';
            $body .= '<p>Filme: <b>' . get_field('titulo_do_filme', $post_id) . '</b></p>';
            $body .= '<p><br>Para visualiza-la, clique <a href="' . admin_url('post.php?post=' . $post_id . '&action=edit') . '">aqui</a>.<p>';
            $body .= '<br><br><p><small>Você recebeu este email pois está cadastrado para monitorar as inscrições ao Oscar. Para deixar de monitorar, remova seu email das configurações, em: <a href="' . admin_url('edit.php?post_type=inscricao&page=inscricao-options-page') . '">Configurações Oscar</a></small><p>';

            if (!wp_mail($to, $subject, $body, $headers)) {
                error_log("ERRO: O envio de email de monitoramento para: " . $to . ', Falhou!', 0);
            }

			add_post_meta($post_id, '_inscription_validated', true, true);

        }

        /**
         * Register stylesheet for our plugin
         *
         */
        public function register_oscar_minc_styles()
        {
            wp_register_style('oscar-minc-styles', plugin_dir_url(__FILE__) . 'assets/oscar-minc.css');
            wp_enqueue_style('oscar-minc-styles');
        }

        /**
         * Register stylesheet admin pages
         *
         */
        public function register_oscar_minc_admin_styles()
        {
            wp_register_style('oscar-minc-admin-styles', plugin_dir_url(__FILE__) . 'assets/oscar-minc-admin.css');
            wp_enqueue_style('oscar-minc-admin-styles');
        }

        /**
         * Register JS for our plugin
         *
         */
        public function register_oscar_minc_scripts()
        {
            wp_enqueue_script('oscar-minc-scripts', plugin_dir_url(__FILE__) . 'assets/oscar-minc.js', array('jquery'), false, true);
            wp_localize_script( 'oscar-minc-scripts', 'oscar_minc_vars', array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'upload_file_nonce' => wp_create_nonce( 'oscar-video' ),
                )
            );
        }

        /**
         * Register JS for admin pages
         *
         */
        public function register_oscar_minc_admin_scripts()
        {
            wp_enqueue_script('oscar-minc-admin-scripts', plugin_dir_url(__FILE__) . 'assets/oscar-minc-admin.js', array('jquery'), false, true);
        }

        /**
         * Set the mail content to accept HTML
         *
         * @param $content_type
         * @return string
         */
        public function set_email_content_type($content_type)
        {
            return 'text/html';
        }

        /**
         * Set email sender
         *
         * @param $content_type
         * @return mixed
         */
        public function oscar_minc_wp_mail_from($content_type)
        {
            $oscar_minc_options = get_option('oscar_minc_options');
            return $oscar_minc_options['oscar_minc_email_from'];
        }

        /**
         * Set sender name for emails
         *
         * @param $name
         * @return mixed
         */
        public function oscar_minc_wp_mail_from_name($name)
        {
            $oscar_minc_options = get_option('oscar_minc_options');
            return $oscar_minc_options['oscar_minc_email_from_name'];
        }

        public function upload_oscar_video()
        {
            check_ajax_referer( 'oscar-video', 'nonce' );

			// error_reporting(0);
			// @ini_set('display_errors',0);
			if (isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
				// if( get_user_meta( $_SESSION['logged_user_id'], '_oscar_video_sent', true ) ){
				if( get_post_meta( $_POST['post_id'], 'movie_attachment_id', true ) ){
					wp_send_json_error( 'Seu vídeo já foi enviado.' );
					exit;
				}
				$oscar_minc_options = get_option('oscar_minc_options');
				// Set the valid file extensions
				// Example: array("jpg", "png", "gif", "bmp", "jpeg", "GIF", "JPG", "PNG", "doc", "txt", "docx", "pdf", "xls", "xlsx");
				$valid_formats =  $oscar_minc_options['oscar_minc_movie_extensions'] ? explode(', ', $oscar_minc_options['oscar_minc_movie_extensions']) : array('mp4');
				$name = $_FILES['oscarVideo']['name']; // Get the name of the file
				$size = $_FILES['oscarVideo']['size']; // Get the size of the file
				list($txt, $ext) = explode(".", $name); // Extract the name and extension of the file
				if (in_array($ext, $valid_formats)) { // If the file is valid go on.
					// Check if the file size is more than defined in options page
					if ( $size < intval($oscar_minc_options['oscar_minc_movie_max_size']) * pow(1024,3) ) {
						$attachment_id = media_handle_upload( 'oscarVideo', $_POST['post_id'] );
						if ( is_wp_error( $attachment_id ) ) {
							// There was an error uploading the image.
							wp_send_json_error( $attachment_id->get_error_message() );
						} else {
							// The file was uploaded successfully!
							update_post_meta($_POST['post_id'], 'movie_attachment_id', $attachment_id);
							wp_send_json_success($oscar_minc_options['oscar_minc_movie_uploaded_message']);
						}
					} else {
						wp_send_json_error( 'O tamanho do arquivo excede o limite de '. $oscar_minc_options['oscar_movie_max_size'] .'Gb.' );
						error_log('O tamanho do arquivo excede o limite definido. User ID: ' . $_SESSION['logged_user_id'], 0);
					}
				} else {
					wp_send_json_error( 'Formato de arquivo inválido.' );
                }

				/*
				$attachment_id = media_handle_upload( 'oscarVideo', $_POST['post_id'] );
				if ( is_wp_error( $attachment_id ) ) {
					// There was an error uploading the image.
					wp_send_json_error( $attachment_id->get_error_message() );
				} else {
					// The image was uploaded successfully!
					wp_send_json_success();
				}
				*/

				die;
				if (strlen($name)) { // Check if the file is selected or cancelled after pressing the browse button.
					list($txt, $ext) = explode(".", $name); // Extract the name and extension of the file
					if (in_array($ext, $valid_formats)) { // If the file is valid go on.
						if ($size < intval($oscar_minc_options['oscar_minc_movie_max_size'])*pow(1024,3) ) { //  Check if the file size is more than defined in options page
							// Notice for admin
							// admin_notice_on_upload_start( $_SESSION['logged_user_id'], $name );
							$file_name = $_FILES['oscarVideo']['name'];
							$tmp = $_FILES['oscarVideo']['tmp_name'];
							// Check if path folder exists and has correct permissions
							if (!is_writeable( $path )) {
								printf('"%s" o diretório não possuir permissão de escrita.', $path);
								error_log("Impossível criar arquivo no destino: " . $path, 0);
							} else {
								$unique_folder_based_on_cnpj = str_replace('.', '',  str_replace('-', '', str_replace('/', '', $_SESSION['logged_user_cnpj']) ) );
								// Creates a unique folder to upload files (based on user CNPJ)
								if (!file_exists( $path . '/' . $unique_folder_based_on_cnpj )) {
									mkdir($path . '/' . $unique_folder_based_on_cnpj, 0777, true);
								}
								// Check if it the file move successfully.
								if (move_uploaded_file($tmp, $path . '/' . $unique_folder_based_on_cnpj .'/'. $name)) {
									// update_user_meta( $_SESSION['logged_user_id'], '_oscar_minc_movie_name', $name );
									// update_user_meta( $_SESSION['logged_user_id'], '_oscar_minc_movie_path', $uploads['baseurl'] . '/oscar-videos/filmesoscar2018' . '/' . $unique_folder_based_on_cnpj .'/'. $name );
									// update_user_meta( $_SESSION['logged_user_id'], '_oscar_minc_video_sent', true );
									echo $oscar_minc_options['oscar_minc_movie_uploaded_message'];
									// oscar_minc_video_sent_confirmation_email( $_SESSION['logged_user_id'] );
								} else {
									echo 'Falha ao mover arquivo para pasta destino';
									error_log('Falha ao mover arquivo para pasta destino: ' . $path . '/' . $unique_folder_based_on_cnpj .'/'. $name, 0);
								}
							}
						} else {
							echo 'O tamanho do arquivo excede o limite de '. $oscar_minc_options['oscar_minc_movie_max_size'] .'Gb.';
							error_log('O tamanho do arquivo excede o limite definido. User ID: ' . $_SESSION['logged_user_id'], 0);
						}
					} else {
						echo 'Formato de arquivo inválido.';
					}
				} else {
					echo 'Selecione um arquivo para realizar o upload';
				}
			}
			die;

            /*
            $wp_upload_dir = wp_upload_dir();
            $file_path     = trailingslashit( $wp_upload_dir['path'] ) . $_POST['file'];
            $file_data     = $this->decode_chunk( $_POST['file_data'] );
            if ( false === $file_data ) {
                wp_send_json_error();
            }
            // file_put_contents( $file_path, $file_data, FILE_APPEND );


			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );

			// Let WordPress handle the upload.
			// Remember, 'my_image_upload' is the name of our file input in our form above.
			$attachment_id = media_handle_upload( $_POST['file'], $_POST['post_id'] );

			if ( is_wp_error( $attachment_id ) ) {
				// There was an error uploading the image.
				wp_send_json_error( $attachment_id->get_error_message() );
			} else {
				// The image was uploaded successfully!
				wp_send_json_success();
			}

            */
        }

        public function decode_chunk( $data ) {
            $data = explode( ';base64,', $data );
            if ( ! is_array( $data ) || ! isset( $data[1] ) ) {
                return false;
            }
            $data = base64_decode( $data[1] );
            if ( ! $data ) {
                return false;
            }
            return $data;
        }

		public static function oscar_user_cnpj_field( $user )
		{
			if( !current_user_can( 'manage_options' )  ){
				return;
			}
			?>
			<h3>Informações complementares</h3>

			<table class="form-table">
				<tr>
					<th>CNPJ do usuário</th>
					<td>
						<label for="user_cnpj">
							<input name="user_cnpj" type="text" id="user_cnpj" value="<?php echo get_user_meta( $user->ID, '_user_cnpj', true ); ?>">
						</label>
					</td>
				</tr>
			</table>
		<?php }

		public static function update_user_cnpj( $user_id )
		{
			if ( !current_user_can( 'edit_user', $user_id ) ) {
				return false;
			} else {
				if( isset($_POST['user_cnpj']) ){
					update_user_meta( $user_id, '_user_cnpj', $_POST['user_cnpj']);
				}
			}
		}

		/**
         *  mask($cnpj,'##.###.###/####-##'); // 11.222.333/0001-99
            mask($cpf,'###.###.###-##'); // 001.002.003-00
            mask($cep,'#####-###'); // 08665-110
            mask($data,'##/##/####'); // 10/10/2010
		 *
		 * @param $val
		 * @param $mask
		 * @return string
		 */
		public function mask ($val, $mask)
		{
			$maskared = '';
			$k = 0;
			for ($i = 0; $i <= strlen($mask) - 1; $i++) {
				if ($mask[$i] == '#') {
					if (isset($val[$k]))
						$maskared .= $val[$k++];
				} else {
					if (isset($mask[$i]))
						$maskared .= $mask[$i];
				}
			}
			return $maskared;
		}


	}

    // Initialize our plugin
    $oscar_minc = new OscarMinC();

endif;