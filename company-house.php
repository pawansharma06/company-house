<?php
/*
Plugin Name: Company House Search
Plugin URI: https://planetarymarketing.in
Description: Plugin to search company,Officers,Search disqualified officers.
Version: 1.0
Author: Pawan Sharma
Author URI: facebook.com/pawan6161
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: https://planetarymarketing.in
*/

Class Company_house {
	
	public function __construct() {
    // Hook into the admin menu
    add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
	add_action('admin_init', array( $this, 'setup_sections' ) );
	add_action( 'admin_init', array( $this, 'setup_fields' ) );
	add_shortcode('company_house', array( $this,'company_house_shortcode_function'));
	add_action( 'wp_enqueue_scripts', array( $this,'add_ch_scripts'));
	
	}
	function add_ch_scripts()
	{
		wp_enqueue_script( 'company-house-plug-js', plugins_url( '/js/ch.js', __FILE__ ), array('jquery'),false,true);
		wp_enqueue_style( 'company-house-plug-css', plugins_url( '/css/ch.css', __FILE__ ));
	}
	
	
	function create_plugin_settings_page() {
		add_menu_page(
			'Company House',
			'Company House',
			'manage_options',
			'company_house_options',
			array( $this, 'company_house_options_page_html'),
			'dashicons-admin-plugins'
		);
	}
	
	Public function company_house_options_page_html() {
    ?>
		<div class="company_house">
		  <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		  <form action="options.php" method="post">
			<?php
                settings_fields( 'company_house_options' );
                do_settings_sections( 'company_house_options' );
                submit_button();
            ?>
		  </form>
		</div>
		<?php
	}
	
	
	public function setup_sections() {
    add_settings_section( 'company_house_api', 'Options', false, 'company_house_options' );
	}
	
	public function setup_fields() {
		$fields = array(
        array(
            'uid' => 'api_key',
            'label' => 'API KEY',
            'section' => 'company_house_api',
            'type' => 'text',
            'options' => false,
            'placeholder' => 'XXXXXXXXXX',
            'helper' => 'Does this help?',
            'supplemental' => 'Get it here!'
			)
		);
		foreach( $fields as $field ){
			add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'company_house_options', $field['section'], $field );
			register_setting( 'company_house_options', $field['uid']);
		}
	}
	public function field_callback( $arguments ) {
        $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
			if( ! $value ) { // If no value exists
				$value = $arguments['default']; // Set to our default
			}

			// Check which type of field we want
			switch( $arguments['type'] ){
				case 'text': // If it is a text field
					printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
					break;
			}

			// If there is help text
			if( $helper = $arguments['helper'] ){
				printf( '<span class="helper"> %s</span>', $helper ); // Show it
			}

			// If there is supplemental text
			if( $supplimental = $arguments['supplemental'] ){
				printf( '<p class="description">%s</p>', $supplimental ); // Show it
			}
	}
	
	public function company_house_shortcode_function() {
		$value = get_option( 'api_key' );
		?>
		<div class="ch_search_form">
		  <form id="ch_companysearch" method="post">
			  <fieldset>
				  <legend>Search Company</legend>				   <input type="hidden" name="key" id="key" value="<?php echo $value; ?> "/>				   <div class="ch_input_group">
					  <input type="text" name="company_name" placeholder="Search here..." id="company_name" class="input-control"/>					  <span class="ch_input_group_btn">					  <button type="submit" class="btn btn-default">Search</button>					  </span>
				   </div>
				  
			  </fieldset>
		  </form>
		</div>
		<div class="ch_search_data">		    <div class="ch_loading" id="loading"></div>			<div class="ch_top_headings"><span id="ch_total_result"></span><span id="ch_page"></span> <ul id="ch-pagination"></ul></div>			<table id="ch_search_data">			</table>
		</div>		<div class="ch_popup_info">		<div class="ch_loading" id="floatloading"></div>		<div class="ch_popup_info_close"> <a href="#" id="ch_popup_info_close">X </a></div>		<div id="ch_popup_info_data">		    <h2 id="ch_company_name">Company Name</h2>			<p>Company Number <span id="ch_company_number"></span></p>			<hr/>			<b>Address :</b> </strong><span id="ch_company_address"></span><br/>			<b>Company status :</b> </strong><span id="ch_company_status"></span><br/>			<b>Company type:</b> </strong><span id="ch_company_type"></span><br/>			<b>Incorporated on:</b> </strong><span id="ch_company_incorporation_on"></span><br/>			<hr/>			<p><strong>Accounts</strong><br/>			 Next accounts made up to <span id="period_end_on"></span> due by <span id="due_on"></span><br/>             Last accounts made up to <span id="made_up_to"> </span></p>			<p><strong>Confirmation statement</strong><br/>			    Next statement date <span id="next_made_up_to"></span> due by <span id="next_due"></span><br/>                Last statement dated <span id="last_made_up_to"> </span></p>			<p><strong>Nature of business (SIC)</strong><br/>			<span id="sic_codes"></span></p>			<p><strong>Previous company names</strong></p>			<table id="previous_company">						</table>					</div>		</div>
		
		<?php
		
	}
	
	
}

new Company_house();




