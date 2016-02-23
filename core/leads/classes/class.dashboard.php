<?php/** Leads Dashboard Widget */Class Leads_Dashboard {	function __construct() {		$enable = get_option('wpl-main-enable-dashboard',1);		$disable = get_option('wpl-main-disable-widgets',1);		if ($disable) {			add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ) );		}		if ($enable) {			add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );		}		add_action( 'admin_enqueue_scripts', array( __CLASS__ , 'register_admin_scripts' ) );		add_action( 'admin_head', array( __CLASS__ , 'add_inline_header_scripts' ) );	}	static function remove_dashboard_widgets() {		$remove_defaults_widgets = array(			'dashboard_incoming_links' => array(				'page'	=> 'dashboard',				'context' => 'normal'			),			'dashboard_right_now' => array(				'page'	=> 'dashboard',				'context' => 'normal'			),			'dashboard_recent_drafts' => array(				'page'	=> 'dashboard',				'context' => 'side'			),			'dashboard_quick_press' => array(				'page'	=> 'dashboard',				'context' => 'side'			),			'dashboard_plugins' => array(				'page'	=> 'dashboard',				'context' => 'normal'			),			'dashboard_primary' => array(				'page'	=> 'dashboard',				'context' => 'side'			),			'dashboard_secondary' => array(				'page'	=> 'dashboard',				'context' => 'side'			),			'dashboard_recent_comments' => array(				'page'	=> 'dashboard',				'context' => 'normal'			),			'rg_forms_dashboard' => array(				'page'	=> 'dashboard',				'context' => 'normal'			),		);		foreach ($remove_defaults_widgets as $wigdet_id => $options) {			remove_meta_box($wigdet_id, $options['page'], $options['context']);		}	}	static function add_dashboard_widgets() {		if (!current_user_can('activate_plugins') ) {			return;		}		$custom_dashboard_widgets = array(			'wp-lead-stats' => array(				'title' => 'Lead Stats',				'callback' => array( __CLASS__ , 'display_lead_report_widget')			),			'wp-lead-dashboard-list' => array(				'title' => 'Lead Lists',				'callback' => array( __CLASS__ , 'display_list_widget' )			),		);		foreach ($custom_dashboard_widgets as $widget_id => $options) {			wp_add_dashboard_widget(				$widget_id,				$options['title'],				$options['callback']			);		}	}	public static function register_admin_scripts( $hook ) {		if( 'index.php' == $hook ) {			wp_register_script( 'jquery-cookie', WPL_URLPATH . 'assets/js/jquery.cookie.js' );			wp_enqueue_script( 'jquery-cookie' );			wp_register_script( 'flot', WPL_URLPATH . 'assets/js/jquery.flot.js' );			wp_enqueue_script( 'flot' );			wp_register_script( 'flot-stack', WPL_URLPATH . 'assets/js/jquery.flot.stack.js' );			wp_enqueue_script( 'flot-stack' );			wp_register_script( 'flot-time', WPL_URLPATH . 'assets/js/jquery.flot.time.js' );			wp_enqueue_script( 'flot-time' );			wp_register_script( 'flot-axislabels', WPL_URLPATH . 'assets/js/jquery.flot.axislabels.js' );			wp_enqueue_script( 'flot-axislabels' );			wp_register_script( 'lead-flot-functions', WPL_URLPATH . 'assets/js/lead-flot-functions.js' );			wp_enqueue_script( 'lead-flot-functions' );			//wp_register_script( 'custom-dashboard-js', WPL_URLPATH . '/assets/js/custom-dashboard.js', false, true);			wp_enqueue_script( 'custom-dashboard-js', WPL_URLPATH . 'assets/js/custom-dashboard.js', array(), false, true );			wp_register_script( 'jquery-dropdown', WPL_URLPATH . 'assets/js/jquery.dropdown.js' );			wp_enqueue_script( 'jquery-dropdown' );			wp_enqueue_style('custom-dashboard-css', WPL_URLPATH . '/assets/css/wpl.dashboard.css');		} // end if	}	public static function get_lead_count_from_last_24h() {		global $wpdb;		$numposts = $wpdb->get_var(			$wpdb->prepare(				"SELECT COUNT(ID) ".				"FROM {$wpdb->posts} ".				"WHERE ".					"post_status='publish' ".					"AND post_type= %s ".					"AND post_date> %s",				'wp-lead' , date('Y-m-d H:i:s', strtotime('-24 hours'))			)		);		return $numposts;	}	public static function get_lead_count_from_today() {		global $wpdb;		global $table_prefix;		$wordpress_date_time = $timezone_format = _x('Y-m-d', 'timezone date format');		$wordpress_date_time =	date_i18n($timezone_format);		$wordpress_date = $timezone_day = _x('d', 'timezone date format');		$wordpress_date =	date_i18n($timezone_day);		$today = $wordpress_date_time; // Corrected timezone		$tomorrow = date("Y-m-d",strtotime("+2 day")); // Hack to look 2 days ahead		$numposts = $wpdb->get_var(			$wpdb->prepare(				"SELECT COUNT(ID) ".				"FROM {$wpdb->posts} ".				"WHERE post_status='publish' ".					"AND post_type= %s ".					"AND {$table_prefix}posts.post_date BETWEEN %s AND %s",				'wp-lead' , $today, $tomorrow			)		);		return $numposts;	}	public static function display_lead_report_widget()	{		global $wpdb;		$count_posts = wp_count_posts('wp-lead');		$url = site_url();		$c_month	= date( 'n' ) == 1 ? 12 : date( 'n' ); // GETS INT from EDD		$previous_month	= date( 'n' ) == 1 ? 12 : date( 'n' ) - 1; // GETS INT from EDD		$previous_year	= $previous_month == 12 ? date( 'Y' ) - 1 : date( 'Y' ); // Gets INT year val		$start_current = date("Y-m-01"); // start of current month		$end_current = date("Y-m-t",strtotime('last day of this month')); // end of current month		//getting the previous month		$previous_month_start = date("Y-m-01", strtotime("previous month"));		$previous_month_end = date("Y-m-d", strtotime("-1 month"));		$this_month = self::count_leads_by_time( $start_current, $end_current);		$last_month = self::count_leads_by_time( $previous_month_start, $previous_month_end);		$all_time_leads = $count_posts->publish;		$all_lead_text = ($all_time_leads == 1) ? "Lead" : "Leads";		$leads_today = Leads_Dashboard::get_lead_count_from_today('wp-lead');		$leads_today_text = ($leads_today == 1) ? "Lead" : "Leads";		$month_comparasion = $this_month - $last_month;		if ($month_comparasion < 0)		{			$month_class = 'negative-leads';			$sign = "";			$sign_text = "decrease";		} elseif($month_comparasion === 0) {			$month_class= 'no-change';			$sign = "";			$sign_text = "No Change ";		} else {			$month_class = 'positive-leads';			$sign = "+";			$sign_text = "increase";		}		echo '<div id="lead-before-dashboard">';		do_action('wp_lead_before_dashboard');		echo "</div>";		$clean_dates = date("m", strtotime("first day of previous month") );		$clean_date_two = date("m");		?>		<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="/wp-content/plugins/lead-dashboard-widgets/assets/js/flot/excanvas.min.js"></script><![endif]-->		<div class="wp_leads_dashboard_widget">		<script type="text/javascript">		/* <![CDATA[ */		window.data1 = [ <?php echo self::get_lead_graph_data( $clean_date_two, 'this-month'); ?> ];		window.data2 = [ <?php echo self::get_lead_graph_data( $clean_dates, 'last-month'); ?> ];		/* ]]> */		</script>			<div id="flot-placeholder" style='width: 100%; height: 250px; margin: 10px auto 0px; padding: 0px; position: relative; margin-bottom:10px;'></div>			<div id="wp-leads-stat-boxes">			<div class='wp-leads-today'>				<a class="data-block widget-block" alt='Click to View Todays Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead&current_date";?>">					<section>						<?php echo $leads_today; ?>						<br><?php echo $leads_today_text;?>						<br><strong><?php _e('Today' , 'leads'); ?></strong>					</section>				</a>			</div>			<div class='wp-leads-this-month'>				<a class="data-block widget-block" alt='Click to View This Months Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead&current_month";?>">					<section>						<?php echo $this_month; ?>						<br><?php echo $all_lead_text;?>						<br><strong><?php _e('This Month' , 'leads'); ?></strong>					</section>				</a>			</div>			<div class='wp-leads-all-time'>				<a class="data-block widget-block" title='Click to View All Leads' href="<?php echo $url . "/wp-admin/edit.php?post_type=wp-lead";?>">					<section>						<?php echo $all_time_leads;?>						<br><?php _e('Leads' , 'leads'); ?>						<strong><?php _e('All Time' , 'leads'); ?></strong>					</section>				</a>			</div>			<div class="wp-leads-change-box" style="text-align: center;">				<small class='<?php echo $month_class; ?>'><?php echo "<span>" . $sign . $month_comparasion . "</span> " . $sign_text;?> <?php _e('Since Last Month' , 'leads'); ?></small>			</div>		</div>			<!-- <div class='wp-leads-last-month'>			last month: <?php echo $last_month; ?>			<?php echo $this_month - $last_month;	?>			</div>	-->			<div id='leads-list'>			<?php	$r = new WP_Query( apply_filters( 'widget_posts_args', array(				'posts_per_page' => 20,				'post_type' => 'wp-lead',				'post_status' => 'publish') ) );			if ($r->have_posts()) : ?>			<h4 class='marketing-widget-header'>Latest Leads<span class="toggle-lead-list">-</span></h4>			<ul id='lead-ul'>			<?php while ( $r->have_posts() ) : $r->the_post(); ?>				<li><?php $id = get_the_ID();				$first_name = get_post_meta( $id , 'wpleads_first_name',true );				$last_name = get_post_meta( $id , 'wpleads_last_name', true );				$name = $first_name . " " . $last_name;				if ($name === " ") {					$name = get_the_title( $id );				}				?>					<?php edit_post_link($name);?> on <?php the_time('F jS, Y'); ?> (<?php the_title();?>)				</li>			<?php endwhile; ?>			</ul>			<?php endif; ?>			</div>		</div>		<?php	}	public static function display_list_widget()	{		global $Inbound_Leads;		$admin_url = get_admin_url();		//wplead_list_category		/* Get All Lead Lists */		$lead_lists = $Inbound_Leads->get_lead_lists_as_array();		if (!$lead_lists) {			return;		}		echo "<div id='leads-list'>";		echo "<h4 class='marketing-widget-header'>". __('Lists' , 'leads') ."<span class='toggle-lead-list'>-</span></h4>";			echo "<ul id='lead-ul' class='dashboard-lead-lists'>";				$leads_count = get_transient( 'leads_list_count' );				if ( !$leads_count ) {					foreach ($lead_lists as $id => $label ) {						$leads_count[ $id ]['list_name'] = $label;						$leads_count[ $id ]['count'] =	$Inbound_Leads->get_leads_count_in_list( $id );					}				}				foreach ( $leads_count as $lead_id => $lead )				{					echo '<li>';						echo '<a href="'.$admin_url.'post.php?post='.$lead_id.'&action=edit">'.$lead['list_name'].'</a> <span class="lead-list-count">'.$lead['count'].'</span>';					echo '</li>';				}			echo '</ul>';		echo '</div>';		set_transient( 'leads_list_count' , $leads_count , 60 * 60 );	}	public static function count_leads_by_time( $start_current, $end_current ) {		global $wpdb;		global $table_prefix;		$numposts = $wpdb->get_var(			$wpdb->prepare(				"SELECT COUNT(ID) ".				"FROM {$wpdb->posts} ".				"WHERE post_status='publish' ".					"AND post_type= %s ".					"AND {$table_prefix}posts.post_date BETWEEN %s AND %s",				'wp-lead' , $start_current, $end_current			)		);		return $numposts;	}	public static function get_lead_graph_data($month, $type)	{		global $wpdb;		global $table_prefix;		$wordpress_date_time = $timezone_format = _x('Y-m-d', 'timezone date format');		$wordpress_date_time =	date_i18n($timezone_format);		$wordpress_date = $timezone_day = _x('d', 'timezone date format');		$wordpress_date =	date_i18n($timezone_day);		$this_year = _x('Y', 'timezone date format');		$this_year =	date_i18n($this_year);		$loop_count = date('d',strtotime('last day of this month'));		$final_loop_count = cal_days_in_month(CAL_GREGORIAN, $month, $this_year); // Count of days in month		//echo $final_loop_count; // How many times to run		$lead_increment = 0;		for ($i = 1; $i < $final_loop_count + 1; $i++) {				// echo "hi" . $i;			$year = $this_year;			$day = $i;			$next_day = $i + 1;			$m = $month;			$Date = strtotime($year . "-" . $m . "-" . $day);			$Date_next = strtotime($year . "-" . $m . "-" . $next_day);			$clean_date_one = date('Y-m-d', $Date);			$clean_date_one_formatted = date('Y, n, d', $Date);			if ($type === "last-month"){				$Date = strtotime($year . "-" . $m . "-" . $day . ' +1 months');				$clean_date_one_formatted = date('Y, n, d', $Date);			}			$clean_date_two = date('Y-m-d', $Date_next);			//echo $clean_date_one . "<br>";			$numposts = $wpdb->get_var(			$wpdb->prepare(				"SELECT COUNT(ID) ".				"FROM {$wpdb->posts} ".				"WHERE post_status='publish' ".					"AND post_type= %s ".					"AND {$table_prefix}posts.post_date BETWEEN %s AND %s",				'wp-lead' , $clean_date_one, $clean_date_two			)		);		$lead_increment += $numposts;		//echo "Day is: ". $day . " " . $numposts . " on " . $clean_date_one	.	"<br>";		echo "[gd(". $clean_date_one_formatted . "), "	. $lead_increment . ", ". $numposts ."], ";		}	}	public static function add_inline_header_scripts() {		if (!class_exists('Inbound_Pro_Plugin')) {			return;		}		?>		<style type="text/css">			#wpadminbar .adminbar-leads-search .lead-quick-search {				display:inline !important;			}			#wpadminbar .adminbar-leads-search form {				display:inline !important;				width:auto;				height:auto;			}			#wpadminbar .adminbar-leads-search .lead-quick-search input			, #wpadminbar .adminbar-leads-search .lead-quick-search input			, #wpadminbar .adminbar-leads-search .lead-quick-search input:focus{				width:1px;				-webkit-transition: all .3s cubic-bezier(0,0,.5,1.5);				transition: all .3s cubic-bezier(0,0,.5,1.5);				font-size:9px;				height:25px;				margin-top:3px;				padding-bottom:0px;				padding-top:0px;				padding-left:3px;				color: #eee;				background-color: #444;				margin-right:5px;				border:none;				line-height:30px;				display:none;			}		</style>		<section class="lead-quick-search" style="" method="get">			<form action="<?php echo admin_url('edit.php?s=hudson.atwell%40gmail.com&post_status=all&post_type=wp-lead'); ?>">			<input name="s" type="search" placeholder="<?php _e('Search Leads','leads'); ?>">			<input type="hidden" name="post_type" value="wp-lead">			</form>		</section>		<script type="text/javascript">			jQuery( document).ready( function() {				var on = false;				jQuery('.lead-quick-search ').prependTo('.adminbar-leads-search .ab-item');				jQuery('.adminbar-leads-search').click( function() {					if (!on) {						jQuery('.lead-quick-search input').show().animate({							width: '70%'						} , 200 );						on = true;					} else {						jQuery('.lead-quick-search input').animate({							width: '0px'						} , 200 ).hide();						on = false;					}				});			});		</script>		<?php		/* load fontawesome */		wp_enqueue_style('fontawesome', INBOUNDNOW_SHARED_URLPATH . 'assets/fonts/fontawesome/css/font-awesome.min.css');	}}$wdw = new Leads_Dashboard();