<?php
/**
 * @package Retro Game Emulator
 */
/*
Plugin Name: Retro Game Emulator
Plugin URI: https://wordpress.org/plugins/retro-game-emulator/
Description: Run an NES emulator on your Wordpress site.
Version: 1.2.0
Author: grimmdude
Author URI: http://grimmdude.com
Text Domain: jsnes
License: GPLv2 or later
*/

// Make sure we don't expose any info if called directly
if ( ! function_exists('add_action')) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

if ( ! class_exists('RetroGameEmulator')) {
	class RetroGameEmulator
	{

		public $romsFolder = '/retro-game-emulator/';

		public $romsPath;

		public $romsURL;


		public function __construct()
		{
			$uploads_dir = wp_upload_dir();
			$this->romsPath = $uploads_dir['basedir'] . $this->romsFolder;
			$this->romsURL = $uploads_dir['baseurl'] . $this->romsFolder;

			add_action('wp_enqueue_scripts', function () {
				wp_enqueue_script('jquery');
				wp_enqueue_script('dynamicaudio', plugins_url('lib/dynamicaudio-min.js', __FILE__));
				wp_enqueue_script('jsnes', plugins_url('lib/jsnes.min.js', __FILE__, array('dynamicaudio')));
				wp_enqueue_script('retro-game-emulator-app', plugins_url('lib/app.js', __FILE__, array('jsnes', 'jquery')));
			});

			add_action('wp_head', array($this, 'head'));
			add_shortcode('nes', array($this, 'shortcode'));

			/*
			register_activation_hook(__FILE__, function () {
				// Try to create wp-content/retro-game-emulator directory if it doesn't exist
				if ( ! file_exists(WP_CONTENT_DIR . '/retro-game-emulator')) {
					mkdir(WP_CONTENT_DIR . '/retro-game-emulator');
				}
			});
			*/

			add_action('admin_menu', function () {
				add_options_page('Retro Game Emulator', 'Retro Game Emulator', 'administrator', 'retro-game-emulator', array($this, 'optionsPage'));
			});

			add_action('admin_post_retro_game_upload_rom', array($this, 'handleOptions'));
		}

		public function head()
		{
			$roms = scandir($this->romsPath);
			$romsArray = array();

			foreach ($roms as $rom) {
				if (substr($rom, -3) === 'nes') {
					$romsArray[] = array($rom, $this->romsURL . $rom);
				}
			}
			?>
				<script type="text/javascript">
					var retroGameEmulator = {roms: <?php echo json_encode($romsArray); ?>, swfPath: '<?php echo plugins_url('lib', __FILE__); ?>/'};
				</script>
			<?php
		}


		public function shortcode($atts)
		{
			$return = '<div class="jsnes"></div>';

			$return .= "<h3>" . __("Controls") . "</h3>
    <table>
        <tr>
            <th>" . __("Button") . "</th>
            <th>" . __("Player 1") . "</th>
            <th>" . __("Player 2") . "</th>
        </tr>
        <tr>
            <td>" . __("Left") . "</td>
            <td>" . __("Left") . "</td>
            <td>" . __("Num-4") . "</td>
        <tr>
            <td>" . __("Right") . "</td>
            <td>" . __("Right") . "</td>
            <td>" . __("Num-6") . "</td>
        </tr>
        <tr>
            <td>" . __("Up") . "</td>
            <td>" . __("Up") . "</td>
            <td>" . __("Num-8") . "</td>
        </tr>
        <tr>
            <td>" . __("Down") . "</td>
            <td>" . __("Down") . "</td>
            <td>" . __("Num-2") . "</td>
        </tr>
        <tr>
            <td>" . __("A") . "</td>
            <td>" . __("X") . "</td>
            <td>" . __("Num-7") . "</td>
        </tr>
        <tr>
            <td>" . __("B") . "</td>
            <td>" . __("Z") . "</td>
            <td>" . __("Num-9") . "</td>
        </tr>
        <tr>
            <td>" . __("Start") . "</td>
            <td>" . __("Enter") . "</td>
            <td>" . __("Num-1") . "</td>
        </tr>
        <tr>
            <td>" . __("Select") . "</td>
            <td>" . __("Ctrl") . "</td>
            <td>" . __("Num-3") . "</td>
        </tr>
    </table>";
			return $return;
		}


		public function optionsPage()
		{
			// Handle deleting rom
			if (array_key_exists('action', $_GET) && substr($_GET['action'], 0, 11) === 'delete-rom-') {
				$rom = substr($_GET['action'], 11);
				if (check_admin_referer("delete-rom-$rom")) {
					wp_delete_file($this->romsPath . $rom);
				}
			}

			include plugin_dir_path( __FILE__ ) . 'options.php';
		}


		public function handleOptions()
		{
			if (wp_verify_nonce($_POST['retro-game-emulator-nonce'], 'retro-game-emulator-options')) {
				add_filter('upload_dir', function ($param) {
					$param['path'] = $param['basedir'] . '/retro-game-emulator/';
					$param['url'] = $param['baseurl'] . '/retro-game-emulator';
					return $param;
				});

				add_filter('mime_types', function ($mimes) {
					$mimes['nes'] = 'application/octet-stream';
					return $mimes;
				});

				wp_handle_upload($_FILES['rom_file'], array('action' => 'retro_game_upload_rom'));
				wp_redirect(admin_url('options-general.php?page=retro-game-emulator'));
				exit;
			}
		}


	}


	new RetroGameEmulator;
}