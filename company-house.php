<?php
/**
 * Plugin Name:       Company House Search
 * Plugin URI:        https://github.com/pawansharma06/company-house
 * Description:       Search UK Companies House records including companies, company officers, and disqualified officers via shortcode [company_house].
 * Version:           2.0.1
 * Requires at least: 5.0
 * Tested up to:      6.7
 * Requires PHP:      7.2
 * Author:            Pawan Sharma
 * Author URI:        https://planetarymarketing.in
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       company-house
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CHSP_PLUGIN_VERSION', '2.0.1' );
define( 'CHSP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CHSP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! class_exists( 'Company_House_Search_Plugin' ) ) {

	/**
	 * Main plugin class for Company House Search.
	 */
	class Company_House_Search_Plugin {

		/**
		 * Live Base URL for Companies House Public Data API.
		 *
		 * @var string
		 */
		private $api_base_url = 'https://api.company-information.service.gov.uk';

		/**
		 * Sandbox Base URL for Companies House Public Data API.
		 *
		 * @var string
		 */
		private $sandbox_base_url = 'https://api-sandbox.company-information.service.gov.uk';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
			add_action( 'admin_init', array( $this, 'setup_sections' ) );
			add_action( 'admin_init', array( $this, 'setup_fields' ) );
			add_shortcode( 'company_house', array( $this, 'company_house_shortcode_function' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

			// Server-side AJAX endpoints.
			add_action( 'wp_ajax_chsp_api_request', array( $this, 'handle_ajax_request' ) );
			add_action( 'wp_ajax_nopriv_chsp_api_request', array( $this, 'handle_ajax_request' ) );
		}

		/**
		 * Retrieve and normalize the stored API key with backward-compatible fallback.
		 *
		 * According to https://developer-specs.company-information.service.gov.uk/guides/authorisation
		 * Basic Auth takes the API key as the username and leaves the password blank (api_key:).
		 *
		 * @return string
		 */
		private function get_api_key() {
			$key = get_option( 'chsp_api_key', '' );
			if ( '' === trim( (string) $key ) ) {
				$key = get_option( 'ch_api_key', '' );
			}
			if ( '' === trim( (string) $key ) ) {
				// Fallback to legacy option name from v1.0.
				$key = get_option( 'api_key', '' );
			}

			$key = trim( (string) $key );

			// Strip accidental "Basic " prefix if user pasted a full Authorization header.
			if ( 0 === stripos( $key, 'basic ' ) ) {
				$key = trim( substr( $key, 6 ) );
			}

			// Strip any trailing colon in case the user entered "my_api_key:" in the settings box.
			$key = rtrim( $key, ':' );

			return trim( $key );
		}

		/**
		 * Register frontend scripts and styles (enqueued on demand when shortcode runs).
		 */
		public function register_assets() {
			wp_register_style(
				'company-house-plug-css',
				CHSP_PLUGIN_URL . 'css/ch.css',
				array(),
				CHSP_PLUGIN_VERSION
			);

			wp_register_script(
				'company-house-plug-js',
				CHSP_PLUGIN_URL . 'js/ch.js',
				array( 'jquery' ),
				CHSP_PLUGIN_VERSION,
				true
			);

			wp_localize_script(
				'company-house-plug-js',
				'chsp_ajax_obj',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'chsp_search_nonce' ),
					'loading_img' => esc_url( CHSP_PLUGIN_URL . 'images/loading.gif' ),
				)
			);
		}

		/**
		 * Register top-level admin settings menu.
		 */
		public function create_plugin_settings_page() {
			add_menu_page(
				__( 'Company House Search', 'company-house' ),
				__( 'Company House', 'company-house' ),
				'manage_options',
				'company_house_options',
				array( $this, 'company_house_options_page_html' ),
				'dashicons-building'
			);
		}

		/**
		 * Render admin settings page HTML.
		 */
		public function company_house_options_page_html() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			?>
			<div class="wrap company_house">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'company_house_options' );
					do_settings_sections( 'company_house_options' );
					submit_button( __( 'Save Settings', 'company-house' ) );
					?>
				</form>
				<hr />
				<h2><?php esc_html_e( 'API Key Setup Checklist (Avoiding HTTP 400 / 401 Errors)', 'company-house' ); ?></h2>
				<ol>
					<li>
						<?php
						printf(
							/* translators: %s: Companies House manage applications URL */
							wp_kses_post( __( 'Go to <a href="%s" target="_blank" rel="noopener noreferrer">Companies House Developer Hub &rarr; Your applications</a>.', 'company-house' ) ),
							'https://developer.company-information.service.gov.uk/manage-applications'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Create an API key of type "REST" (recommended for server-side requests) or "Web".', 'company-house' ); ?></li>
					<li><?php esc_html_e( 'If using a REST key locally (e.g., XAMPP / localhost), leave "Restricted IPs" blank in the Companies House dashboard so your local IP is not blocked with a 400 error.', 'company-house' ); ?></li>
					<li><?php esc_html_e( 'If using a Web key, ensure your site domain (e.g., http://localhost) is added to "JavaScript domains".', 'company-house' ); ?></li>
				</ol>
				<h2><?php esc_html_e( 'Shortcode Usage', 'company-house' ); ?></h2>
				<p><?php esc_html_e( 'Add the following shortcode to any page, post, or widget to display the Companies House search interface:', 'company-house' ); ?></p>
				<code>[company_house]</code>
			</div>
			<?php
		}

		/**
		 * Register settings sections.
		 */
		public function setup_sections() {
			add_settings_section(
				'company_house_api',
				__( 'Companies House API Configuration', 'company-house' ),
				array( $this, 'render_section_intro' ),
				'company_house_options'
			);
		}

		/**
		 * Render section description text.
		 */
		public function render_section_intro() {
			echo '<p>' . esc_html__( 'Enter your UK Companies House API key below. The plugin automatically encodes it using HTTP Basic Authentication (api_key:) per the Companies House Authorisation specification.', 'company-house' ) . '</p>';
		}

		/**
		 * Register settings fields.
		 */
		public function setup_fields() {
			$fields = array(
				array(
					'uid'          => 'chsp_api_key',
					'label'        => __( 'API Key', 'company-house' ),
					'section'      => 'company_house_api',
					'type'         => 'text',
					'default'      => '',
					'placeholder'  => 'e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
					'helper'       => __( 'Paste your raw API key (without trailing colon).', 'company-house' ),
					'supplemental' => sprintf(
						/* translators: %s: Companies House developer hub URL */
						__( 'Get a free API key at the <a href="%s" target="_blank" rel="noopener noreferrer">Companies House Developer Hub</a>.', 'company-house' ),
						'https://developer.company-information.service.gov.uk/manage-applications'
					),
				),
			);

			foreach ( $fields as $field ) {
				add_settings_field(
					$field['uid'],
					$field['label'],
					array( $this, 'field_callback' ),
					'company_house_options',
					$field['section'],
					$field
				);

				register_setting(
					'company_house_options',
					$field['uid'],
					array(
						'type'              => 'string',
						'sanitize_callback' => array( $this, 'sanitize_api_key_setting' ),
						'default'           => '',
					)
				);
			}
		}

		/**
		 * Sanitize API key when saved.
		 *
		 * @param string $value Raw input value.
		 * @return string
		 */
		public function sanitize_api_key_setting( $value ) {
			$clean = sanitize_text_field( (string) $value );
			$clean = trim( $clean );
			if ( 0 === stripos( $clean, 'basic ' ) ) {
				$clean = trim( substr( $clean, 6 ) );
			}
			$clean = rtrim( $clean, ':' );
			return trim( $clean );
		}

		/**
		 * Render individual setting field.
		 *
		 * @param array $arguments Field arguments.
		 */
		public function field_callback( $arguments ) {
			$value = $this->get_api_key();
			if ( empty( $value ) && isset( $arguments['default'] ) ) {
				$value = $arguments['default'];
			}

			$type        = isset( $arguments['type'] ) ? $arguments['type'] : 'text';
			$placeholder = isset( $arguments['placeholder'] ) ? $arguments['placeholder'] : '';

			switch ( $type ) {
				case 'text':
				case 'password':
					printf(
						'<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" class="regular-text" autocomplete="off" />',
						esc_attr( $arguments['uid'] ),
						esc_attr( $type ),
						esc_attr( $placeholder ),
						esc_attr( $value )
					);
					break;
			}

			if ( ! empty( $arguments['helper'] ) ) {
				printf( '<span class="helper"> %s</span>', esc_html( $arguments['helper'] ) );
			}

			if ( ! empty( $arguments['supplemental'] ) ) {
				printf( '<p class="description">%s</p>', wp_kses_post( $arguments['supplemental'] ) );
			}
		}

		/**
		 * Execute HTTP GET against Companies House API with automatic fallback
		 * for Web keys (Origin header) and Sandbox keys.
		 *
		 * @param string $path_and_query Relative path and query string (e.g., '/search/companies?q=tesco').
		 * @param string $api_key        Normalized API key.
		 * @return array|\WP_Error       WP HTTP response array or WP_Error.
		 */
		private function request_companies_house( $path_and_query, $api_key ) {
			// RFC 2617 HTTP Basic Authentication: API key as username, blank password ("api_key:").
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$auth_header = 'Basic ' . base64_encode( $api_key . ':' );

			$base_headers = array(
				'Authorization' => $auth_header,
				'Accept'        => 'application/json',
			);

			// Attempt 1: Standard REST key request against Live API (no Origin header).
			$response    = wp_remote_get(
				$this->api_base_url . $path_and_query,
				array(
					'timeout' => 15,
					'headers' => $base_headers,
				)
			);
			$status_code = ! is_wp_error( $response ) ? (int) wp_remote_retrieve_response_code( $response ) : 0;

			if ( $status_code >= 200 && $status_code < 300 ) {
				return $response;
			}

			// Attempt 2: If 400/401, the key may be a "Web" (JavaScript) application key that requires an Origin header.
			if ( 400 === $status_code || 401 === $status_code ) {
				$origin = '';
				if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
					$origin = esc_url_raw( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
				} else {
					$parsed = wp_parse_url( home_url() );
					if ( ! empty( $parsed['scheme'] ) && ! empty( $parsed['host'] ) ) {
						$origin = $parsed['scheme'] . '://' . $parsed['host'] . ( ! empty( $parsed['port'] ) ? ':' . $parsed['port'] : '' );
					}
				}

				if ( ! empty( $origin ) ) {
					$web_headers            = $base_headers;
					$web_headers['Origin']  = $origin;
					$web_headers['Referer'] = trailingslashit( $origin );

					$retry_web    = wp_remote_get(
						$this->api_base_url . $path_and_query,
						array(
							'timeout' => 15,
							'headers' => $web_headers,
						)
					);
					$retry_status = ! is_wp_error( $retry_web ) ? (int) wp_remote_retrieve_response_code( $retry_web ) : 0;
					if ( $retry_status >= 200 && $retry_status < 300 ) {
						return $retry_web;
					}
				}

				// Attempt 3: Try Sandbox API in case the user generated a Test/Sandbox key.
				$retry_sandbox  = wp_remote_get(
					$this->sandbox_base_url . $path_and_query,
					array(
						'timeout' => 15,
						'headers' => $base_headers,
					)
				);
				$sandbox_status = ! is_wp_error( $retry_sandbox ) ? (int) wp_remote_retrieve_response_code( $retry_sandbox ) : 0;
				if ( $sandbox_status >= 200 && $sandbox_status < 300 ) {
					return $retry_sandbox;
				}
			}

			return $response;
		}

		/**
		 * Extract a human-readable error description from Companies House JSON error payload.
		 *
		 * @param mixed $data        Decoded JSON body.
		 * @param int   $status_code HTTP status code.
		 * @return string
		 */
		private function parse_api_error_message( $data, $status_code ) {
			$api_reason = '';
			if ( is_array( $data ) ) {
				if ( ! empty( $data['error'] ) && is_string( $data['error'] ) ) {
					$api_reason = $data['error'];
				} elseif ( ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
					$first = reset( $data['errors'] );
					if ( is_array( $first ) && ! empty( $first['error'] ) ) {
						$api_reason = $first['error'];
					}
				}
			}

			if ( 400 === $status_code || 401 === $status_code ) {
				$hint = sprintf(
					/* translators: %d: HTTP status code */
					__( 'Companies House rejected the API key or request origin/IP (HTTP %d). ', 'company-house' ),
					$status_code
				);
				if ( $api_reason ) {
					/* translators: %s: Error message from Companies House API */
					$hint .= sprintf( __( 'API message: "%s". ', 'company-house' ), $api_reason );
				}
				$hint .= __( 'Please verify in the Companies House Developer Hub that: (1) your API key is a Live REST key, (2) "Restricted IPs" is left blank if testing on localhost/XAMPP, and (3) the key in WP Admin -> Company House has no extra characters.', 'company-house' );
				return $hint;
			}

			if ( 429 === $status_code ) {
				return __( 'Rate limit exceeded (600 requests per 5 minutes). Please wait a moment and try again.', 'company-house' );
			}

			if ( $api_reason ) {
				return sprintf(
					/* translators: 1: HTTP status code, 2: API error string */
					__( 'Companies House API error (HTTP %1$d): %2$s', 'company-house' ),
					$status_code,
					$api_reason
				);
			}

			return sprintf(
				/* translators: %d: HTTP status code */
				__( 'Companies House API returned HTTP status %d.', 'company-house' ),
				$status_code
			);
		}

		/**
		 * Handle AJAX requests to the Companies House Public Data API.
		 *
		 * Specification reference:
		 * https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference
		 */
		public function handle_ajax_request() {
			check_ajax_referer( 'chsp_search_nonce', 'nonce' );

			$api_key = $this->get_api_key();
			if ( empty( $api_key ) || 'XXXXXXXXXX' === $api_key ) {
				wp_send_json_error(
					array(
						'message' => __( 'Companies House API key is not configured. Please enter a valid API key in WP Admin -> Company House.', 'company-house' ),
					),
					400
				);
			}

			$request_type   = isset( $_GET['request_type'] ) ? sanitize_key( wp_unslash( $_GET['request_type'] ) ) : 'search_companies';
			$query          = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
			$page           = isset( $_GET['page'] ) ? max( 0, absint( $_GET['page'] ) ) : 0;
			$items_per_page = 10;
			$start_index    = $page * $items_per_page;

			$path_and_query = '';

			switch ( $request_type ) {
				case 'search_companies':
					if ( '' === $query ) {
						wp_send_json_error( array( 'message' => __( 'Please enter a search term.', 'company-house' ) ), 400 );
					}
					$path_and_query = '/search/companies?' . http_build_query(
						array(
							'q'              => $query,
							'items_per_page' => $items_per_page,
							'start_index'    => $start_index,
						),
						'',
						'&',
						PHP_QUERY_RFC3986
					);
					break;

				case 'search_officers':
					if ( '' === $query ) {
						wp_send_json_error( array( 'message' => __( 'Please enter an officer name to search.', 'company-house' ) ), 400 );
					}
					$path_and_query = '/search/officers?' . http_build_query(
						array(
							'q'              => $query,
							'items_per_page' => $items_per_page,
							'start_index'    => $start_index,
						),
						'',
						'&',
						PHP_QUERY_RFC3986
					);
					break;

				case 'search_disqualified':
					if ( '' === $query ) {
						wp_send_json_error( array( 'message' => __( 'Please enter a name to search disqualified officers.', 'company-house' ) ), 400 );
					}
					$path_and_query = '/search/disqualified-officers?' . http_build_query(
						array(
							'q'              => $query,
							'items_per_page' => $items_per_page,
							'start_index'    => $start_index,
						),
						'',
						'&',
						PHP_QUERY_RFC3986
					);
					break;

				case 'company_detail':
					$company_number = isset( $_GET['company_number'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['company_number'] ) ) ) : '';
					if ( empty( $company_number ) ) {
						wp_send_json_error( array( 'message' => __( 'Invalid company number.', 'company-house' ) ), 400 );
					}
					$path_and_query = '/company/' . rawurlencode( $company_number );
					break;

				case 'company_officers':
					$company_number = isset( $_GET['company_number'] ) ? preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_GET['company_number'] ) ) ) : '';
					if ( empty( $company_number ) ) {
						wp_send_json_error( array( 'message' => __( 'Invalid company number.', 'company-house' ) ), 400 );
					}
					$path_and_query = '/company/' . rawurlencode( $company_number ) . '/officers?' . http_build_query(
						array(
							'items_per_page' => 50,
							'start_index'    => 0,
						),
						'',
						'&',
						PHP_QUERY_RFC3986
					);
					break;

				case 'disqualified_detail':
					$resource_path = isset( $_GET['resource_path'] ) ? sanitize_text_field( wp_unslash( $_GET['resource_path'] ) ) : '';
					if ( ! preg_match( '#^/disqualified-officers/(natural|corporate)/[A-Za-z0-9_-]+$#', $resource_path ) ) {
						wp_send_json_error( array( 'message' => __( 'Invalid disqualified officer resource path.', 'company-house' ) ), 400 );
					}
					$path_and_query = $resource_path;
					break;

				default:
					wp_send_json_error( array( 'message' => __( 'Unsupported request type.', 'company-house' ) ), 400 );
			}

			$response = $this->request_companies_house( $path_and_query, $api_key );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Failed to connect to Companies House API: ', 'company-house' ) . $response->get_error_message(),
					),
					502
				);
			}

			$status_code = (int) wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );
			$data        = json_decode( $body, true );

			if ( $status_code < 200 || $status_code >= 300 ) {
				wp_send_json_error(
					array(
						'message'      => $this->parse_api_error_message( $data, $status_code ),
						'status'       => $status_code,
						'api_response' => $data ? $data : $body,
					),
					200
				);
			}

			wp_send_json_success( $data );
		}

		/**
		 * Render the [company_house] shortcode.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public function company_house_shortcode_function( $atts = array() ) {
			wp_enqueue_style( 'company-house-plug-css' );
			wp_enqueue_script( 'company-house-plug-js' );

			ob_start();
			?>
			<div class="ch_wrapper">
				<div class="ch_search_form">
					<div class="ch_search_tabs" role="tablist" aria-label="<?php esc_attr_e( 'Search Category', 'company-house' ); ?>">
						<button type="button" class="ch_tab_btn active" data-type="search_companies" data-placeholder="<?php esc_attr_e( 'Search companies by name or number...', 'company-house' ); ?>">
							<?php esc_html_e( 'Companies', 'company-house' ); ?>
						</button>
						<button type="button" class="ch_tab_btn" data-type="search_officers" data-placeholder="<?php esc_attr_e( 'Search officers by name...', 'company-house' ); ?>">
							<?php esc_html_e( 'Officers', 'company-house' ); ?>
						</button>
						<button type="button" class="ch_tab_btn" data-type="search_disqualified" data-placeholder="<?php esc_attr_e( 'Search disqualified officers...', 'company-house' ); ?>">
							<?php esc_html_e( 'Disqualified Officers', 'company-house' ); ?>
						</button>
					</div>

					<form id="ch_companysearch" method="post">
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Search Companies House', 'company-house' ); ?></legend>
							<input type="hidden" name="ch_search_type" id="ch_search_type" value="search_companies" />
							<div class="ch_input_group">
								<input
									type="text"
									name="company_name"
									placeholder="<?php esc_attr_e( 'Search companies by name or number...', 'company-house' ); ?>"
									id="company_name"
									class="input-control"
									required
								/>
								<span class="ch_input_group_btn">
									<button type="submit" class="ch-btn ch-btn-default"><?php esc_html_e( 'Search', 'company-house' ); ?></button>
								</span>
							</div>
						</fieldset>
					</form>
				</div>

				<div class="ch_search_data_container">
					<div class="ch_loading" id="loading"></div>
					<div id="ch_error_msg" class="ch_error_msg" style="display:none;"></div>
					<div class="ch_top_headings">
						<div class="ch_meta_info">
							<span id="ch_total_result"></span>
							<span id="ch_page"></span>
						</div>
						<ul id="ch-pagination"></ul>
					</div>
					<table id="ch_search_data" class="ch_results_table"></table>
				</div>

				<!-- Modal Overlay & Popup -->
				<div class="ch_popup_overlay" id="ch_popup_overlay">
					<div class="ch_popup_info" role="dialog" aria-modal="true" aria-labelledby="ch_company_name">
						<div class="ch_popup_header">
							<h2 id="ch_company_name"></h2>
							<button type="button" id="ch_popup_info_close" class="ch_popup_close_btn" aria-label="<?php esc_attr_e( 'Close modal', 'company-house' ); ?>">&times;</button>
						</div>

						<div class="ch_loading" id="floatloading"></div>

						<div id="ch_popup_info_data">
							<!-- Company Detail Tabs -->
							<div class="ch_modal_tabs" id="ch_modal_tabs">
								<button type="button" class="ch_modal_tab active" data-target="#ch_modal_overview"><?php esc_html_e( 'Overview', 'company-house' ); ?></button>
								<button type="button" class="ch_modal_tab" data-target="#ch_modal_officers" id="ch_load_officers_tab"><?php esc_html_e( 'Officers', 'company-house' ); ?></button>
							</div>

							<!-- Overview Pane -->
							<div id="ch_modal_overview" class="ch_modal_pane active">
								<p class="ch_company_num_line">
									<strong><?php esc_html_e( 'Company Number:', 'company-house' ); ?></strong>
									<span id="ch_company_number"></span>
								</p>
								<hr />
								<div class="ch_detail_grid">
									<div><strong><?php esc_html_e( 'Registered Address:', 'company-house' ); ?></strong> <span id="ch_company_address"></span></div>
									<div><strong><?php esc_html_e( 'Company Status:', 'company-house' ); ?></strong> <span id="ch_company_status" class="ch_status_badge"></span></div>
									<div><strong><?php esc_html_e( 'Company Type:', 'company-house' ); ?></strong> <span id="ch_company_type"></span></div>
									<div><strong><?php esc_html_e( 'Incorporated On:', 'company-house' ); ?></strong> <span id="ch_company_incorporation_on"></span></div>
								</div>
								<hr />
								<div class="ch_detail_columns">
									<div class="ch_detail_box">
										<strong><?php esc_html_e( 'Accounts', 'company-house' ); ?></strong>
										<p>
											<?php esc_html_e( 'Next accounts made up to:', 'company-house' ); ?> <span id="period_end_on"></span><br />
											<?php esc_html_e( 'Due by:', 'company-house' ); ?> <span id="due_on"></span><br />
											<?php esc_html_e( 'Last accounts made up to:', 'company-house' ); ?> <span id="made_up_to"></span>
										</p>
									</div>
									<div class="ch_detail_box">
										<strong><?php esc_html_e( 'Confirmation Statement', 'company-house' ); ?></strong>
										<p>
											<?php esc_html_e( 'Next statement date:', 'company-house' ); ?> <span id="next_made_up_to"></span><br />
											<?php esc_html_e( 'Due by:', 'company-house' ); ?> <span id="next_due"></span><br />
											<?php esc_html_e( 'Last statement dated:', 'company-house' ); ?> <span id="last_made_up_to"></span>
										</p>
									</div>
								</div>
								<p>
									<strong><?php esc_html_e( 'Nature of Business (SIC):', 'company-house' ); ?></strong><br />
									<span id="sic_codes"></span>
								</p>
								<div id="ch_previous_names_wrap">
									<p><strong><?php esc_html_e( 'Previous Company Names', 'company-house' ); ?></strong></p>
									<table id="previous_company" class="ch_sub_table"></table>
								</div>
							</div>

							<!-- Company Officers Pane -->
							<div id="ch_modal_officers" class="ch_modal_pane" style="display:none;">
								<div id="ch_officers_list_content"></div>
							</div>

							<!-- Disqualified Officer Detail Pane -->
							<div id="ch_modal_disqualified" class="ch_modal_pane" style="display:none;">
								<div id="ch_disqualified_detail_content"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}

	new Company_House_Search_Plugin();
}
