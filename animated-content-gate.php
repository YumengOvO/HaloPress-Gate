<?php
/**
 * Plugin Name: Animated Content Gate
 * Description: 可配置的成人内容确认层与图片预加载开场动画。
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License:     GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: animated-content-gate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ACG_Animated_Content_Gate {
	const VERSION     = '1.0.0';
	const OPTION_NAME = 'acg_settings';
	const COOKIE_NAME = 'acg_age_confirmed';

	private static $rendered = false;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_head', array( __CLASS__, 'print_early_boot_script' ), 1 );
		add_action( 'wp_body_open', array( __CLASS__, 'render_gate' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'render_gate' ), 1 );
	}

	public static function defaults() {
		return array(
			'enabled'             => 1,
			'title'               => '本网站包含仅限成人的内容。',
			'message'             => '您是否已满 18 周岁？',
			'yes_label'           => '是，我已满 18 岁',
			'no_label'            => '否，离开网站',
			'no_url'              => 'https://www.google.com/',
			'icon_url'            => '',
			'background_color'    => '#f5f2e9',
			'accent_color'        => '#f595bb',
			'text_color'          => '#fffdf8',
			'cookie_days'         => 1,
			'returning_animation' => 1,
		);
	}

	public static function activate() {
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			add_option( self::OPTION_NAME, self::defaults() );
		}
	}

	private static function settings() {
		$value = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $value ) ? $value : array(), self::defaults() );
	}

	private static function should_run() {
		$settings = self::settings();

		if ( empty( $settings['enabled'] ) || is_admin() || is_feed() || wp_doing_ajax() ) {
			return false;
		}

		return (bool) apply_filters( 'acg_should_show_gate', true, $settings );
	}

	private static function is_preview() {
		if ( ! isset( $_GET['acg-preview'] ) ) {
			return false;
		}

		return '1' === sanitize_text_field( wp_unslash( $_GET['acg-preview'] ) );
	}

	public static function enqueue_assets() {
		if ( ! self::should_run() ) {
			return;
		}

		$settings = self::settings();
		$base_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style(
			'acg-content-gate',
			$base_url . 'assets/css/content-gate.css',
			array(),
			self::VERSION
		);

		$colors = sprintf(
			':root{--acg-background:%1$s;--acg-accent:%2$s;--acg-text:%3$s;}',
			esc_html( $settings['background_color'] ),
			esc_html( $settings['accent_color'] ),
			esc_html( $settings['text_color'] )
		);
		wp_add_inline_style( 'acg-content-gate', $colors );

		wp_enqueue_script(
			'acg-content-gate',
			$base_url . 'assets/js/content-gate.js',
			array(),
			self::VERSION,
			true
		);

		wp_localize_script(
			'acg-content-gate',
			'ACG_CONFIG',
			array(
				'cookieName'         => self::COOKIE_NAME,
				'cookieDays'         => absint( $settings['cookie_days'] ),
				'returningAnimation' => ! empty( $settings['returning_animation'] ),
				'forcePreview'       => self::is_preview(),
				'enteredClass'       => 'acg-entered',
			)
		);
	}

	public static function print_early_boot_script() {
		if ( ! self::should_run() ) {
			return;
		}

		$settings = self::settings();
		$config   = array(
			'cookieName'         => self::COOKIE_NAME,
			'returningAnimation' => ! empty( $settings['returning_animation'] ),
			'forcePreview'       => self::is_preview(),
		);
		?>
		<script id="acg-early-boot">
		(function (config) {
			var cookie = encodeURIComponent(config.cookieName) + '=yes';
			var remembered = document.cookie.split('; ').indexOf(cookie) !== -1;
			if (config.forcePreview || !remembered || config.returningAnimation) {
				document.documentElement.classList.add('acg-active');
			}
		})(<?php echo wp_json_encode( $config ); ?>);
		</script>
		<?php
	}

	public static function render_gate() {
		if ( self::$rendered || ! self::should_run() ) {
			return;
		}

		self::$rendered = true;
		$settings       = self::settings();
		?>
		<div id="acg-content-gate" class="acg-gate" aria-hidden="true" hidden>
			<div class="acg-gate__base" aria-hidden="true"></div>
			<div class="acg-gate__progress" aria-hidden="true"></div>

			<section class="acg-gate__dialog" role="dialog" aria-modal="true" aria-labelledby="acg-title" aria-describedby="acg-message">
				<div class="acg-gate__icon" aria-hidden="true">
					<?php if ( ! empty( $settings['icon_url'] ) ) : ?>
						<img src="<?php echo esc_url( $settings['icon_url'] ); ?>" alt="" width="88" height="88">
					<?php else : ?>
						<svg viewBox="0 0 64 64" width="88" height="88" focusable="false" aria-hidden="true">
							<circle cx="32" cy="32" r="28"></circle>
							<path d="M32 17v20"></path>
							<circle cx="32" cy="46" r="2"></circle>
						</svg>
					<?php endif; ?>
				</div>

				<h2 id="acg-title" class="acg-gate__title"><?php echo esc_html( $settings['title'] ); ?></h2>
				<p id="acg-message" class="acg-gate__message"><?php echo esc_html( $settings['message'] ); ?></p>

				<div class="acg-gate__actions">
					<button type="button" class="acg-gate__button acg-gate__button--yes" data-acg-yes>
						<?php echo esc_html( $settings['yes_label'] ); ?>
					</button>
					<button type="button" class="acg-gate__button acg-gate__button--no" data-acg-no data-leave-url="<?php echo esc_url( $settings['no_url'] ); ?>">
						<?php echo esc_html( $settings['no_label'] ); ?>
					</button>
				</div>
			</section>
		</div>
		<?php
	}

	public static function add_settings_page() {
		add_options_page(
			'Animated Content Gate',
			'内容确认动画',
			'manage_options',
			'animated-content-gate',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function register_settings() {
		register_setting(
			'acg_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'enabled'             => empty( $input['enabled'] ) ? 0 : 1,
			'title'               => sanitize_text_field( $input['title'] ?? $defaults['title'] ),
			'message'             => sanitize_text_field( $input['message'] ?? $defaults['message'] ),
			'yes_label'           => sanitize_text_field( $input['yes_label'] ?? $defaults['yes_label'] ),
			'no_label'            => sanitize_text_field( $input['no_label'] ?? $defaults['no_label'] ),
			'no_url'              => esc_url_raw( $input['no_url'] ?? $defaults['no_url'] ),
			'icon_url'            => esc_url_raw( $input['icon_url'] ?? '' ),
			'background_color'    => sanitize_hex_color( $input['background_color'] ?? '' ) ?: $defaults['background_color'],
			'accent_color'        => sanitize_hex_color( $input['accent_color'] ?? '' ) ?: $defaults['accent_color'],
			'text_color'          => sanitize_hex_color( $input['text_color'] ?? '' ) ?: $defaults['text_color'],
			'cookie_days'         => min( 365, max( 1, absint( $input['cookie_days'] ?? 1 ) ) ),
			'returning_animation' => empty( $input['returning_animation'] ) ? 0 : 1,
		);
	}

	private static function field_name( $key ) {
		return self::OPTION_NAME . '[' . $key . ']';
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$s = self::settings();
		?>
		<div class="wrap">
			<h1>内容确认与开场动画</h1>
			<p>在访客进入网站前显示全屏内容警告，并用双色收幕动画展示页面。</p>

			<form method="post" action="options.php">
				<?php settings_fields( 'acg_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">启用插件</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::field_name( 'enabled' ) ); ?>" value="1" <?php checked( $s['enabled'], 1 ); ?>> 在网站前台显示</label></td>
					</tr>
					<?php self::text_row( 'title', '提示标题', $s['title'] ); ?>
					<?php self::text_row( 'message', '确认问题', $s['message'] ); ?>
					<?php self::text_row( 'yes_label', '确认按钮文字', $s['yes_label'] ); ?>
					<?php self::text_row( 'no_label', '离开按钮文字', $s['no_label'] ); ?>
					<?php self::text_row( 'no_url', '离开后的网址', $s['no_url'], 'url' ); ?>
					<?php self::text_row( 'icon_url', '自定义图标网址', $s['icon_url'], 'url', '留空时使用内置的线框提示图标。' ); ?>
					<?php self::color_row( 'background_color', '底层颜色', $s['background_color'] ); ?>
					<?php self::color_row( 'accent_color', '动画层颜色', $s['accent_color'] ); ?>
					<?php self::color_row( 'text_color', '文字颜色', $s['text_color'] ); ?>
					<tr>
						<th scope="row"><label for="acg-cookie-days">确认记忆天数</label></th>
						<td><input id="acg-cookie-days" class="small-text" type="number" min="1" max="365" name="<?php echo esc_attr( self::field_name( 'cookie_days' ) ); ?>" value="<?php echo esc_attr( $s['cookie_days'] ); ?>"> 天</td>
					</tr>
					<tr>
						<th scope="row">回访动画</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::field_name( 'returning_animation' ) ); ?>" value="1" <?php checked( $s['returning_animation'], 1 ); ?>> 已确认的访客仍播放收幕动画，但不再显示问题</label></td>
					</tr>
				</table>
				<?php submit_button( '保存设置' ); ?>
			</form>

			<?php if ( ! empty( $s['enabled'] ) ) : ?>
				<p><a class="button" href="<?php echo esc_url( add_query_arg( 'acg-preview', '1', home_url( '/' ) ) ); ?>" target="_blank" rel="noopener">强制预览确认层</a></p>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function text_row( $key, $label, $value, $type = 'text', $description = '' ) {
		?>
		<tr>
			<th scope="row"><label for="acg-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input id="acg-<?php echo esc_attr( $key ); ?>" class="regular-text" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( self::field_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<?php if ( $description ) : ?><p class="description"><?php echo esc_html( $description ); ?></p><?php endif; ?>
			</td>
		</tr>
		<?php
	}

	private static function color_row( $key, $label, $value ) {
		?>
		<tr>
			<th scope="row"><label for="acg-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input id="acg-<?php echo esc_attr( $key ); ?>" type="color" name="<?php echo esc_attr( self::field_name( $key ) ); ?>" value="<?php echo esc_attr( $value ); ?>"></td>
		</tr>
		<?php
	}
}

register_activation_hook( __FILE__, array( 'ACG_Animated_Content_Gate', 'activate' ) );
ACG_Animated_Content_Gate::init();
