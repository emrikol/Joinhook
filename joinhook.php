<?php
/**
 * Plugin Name: Joinhook
 * Plugin URI: https://github.com/emrikol/Joinhook
 * Description: Creates a webhook endpoint to connect Join (Android App) to Sonaar.
 * Version: 1.0.0
 * Author: Derrick Tennant
 * Author URI: https://emrikol.com/
 * License: GPL3
 * GitHub Plugin URI: https://github.com/emrikol/Joinhook/
 */

function joinhook_register_route() {
	register_rest_route( 'webhooks/v1', '/join', array(
		'methods' => array( 'POST' ),
		'callback' => 'joinhook_rest_api_callback',
	) );
}
add_action( 'rest_api_init', 'joinhook_register_route' );

function joinhook_rest_api_callback( WP_REST_Request $request ) {
	$options = get_option( 'joinhook_settings' );
	$icon_url = ( isset( $options['join_app_icon'] ) && ! empty( $options['join_app_icon'] ) ) ? $options['join_app_icon'] : 'https://avatars3.githubusercontent.com/u/1082903';

	if ( ! isset( $options['join_device_id'] ) || empty( $options['join_device_id'] ) ) {
		wp_send_json_error( 'Join Device or Group ID not set!' );
	}

	$device_id = sanitize_key( $options['join_device_id'] );

	if ( function_exists( 'jetpack_photon_url' ) ) {
		$icon_url = jetpack_photon_url( $icon_url );
	}

	$body = json_decode( $request->get_body() );

	$event_type = $body->EventType;
	$media_title = $body->Series->Title;
	$episodes = array();

	// Let's merge all episodes, CSV them, and clean them up
	foreach ( $body->Episodes as $episode ) {
		$episodes[] = $episode->Title . ' [S' . $episode->SeasonNumber . 'E' . $episode->EpisodeNumber . ']';
	}

	$text = implode( ', ', $episodes );
	$text = rtrim( trim( $text ), ',' );
	$title = $event_type . ': ' . $media_title;

	$join_url = add_query_arg( array(
		'title' => $title,
		'icon' => esc_url_raw( $icon_url ),
		'text' => $text,
		'deviceId' => $device_id,
	), 'https://joinjoaomgcd.appspot.com/_ah/api/messaging/v1/sendPush' );

	// Boom!
	wp_remote_get( $join_url, array( 'timeout' => 3 ) );
}


// Admin Settings
add_action( 'admin_menu', 'joinhook_add_admin_menu' );
add_action( 'admin_init', 'joinhook_settings_init' );

function joinhook_add_admin_menu() {
	add_submenu_page( 'tools.php', 'Joinhook', 'Joinhook', 'manage_options', 'joinhook', 'joinhook_options_page' );
}

function joinhook_settings_init() {
	register_setting( 'joinhook_settings_page', 'joinhook_settings' );

	add_settings_section(
		'joinhook_settings',
		esc_html__( 'Join Settings', 'joinhook' ),
		'joinhook_settings_section_callback',
		'joinhook_settings_page'
	);

	add_settings_field(
		'join_device_id',
		esc_html__( 'Join Device or Group ID', 'joinhook' ),
		'joinhook_device_id_render',
		'joinhook_settings_page',
		'joinhook_settings'
	);

	add_settings_field(
		'join_app_icon',
		esc_html__( 'URL to use as a notification icon', 'joinhook' ),
		'join_app_icon_render',
		'joinhook_settings_page',
		'joinhook_settings'
	);
}

function joinhook_device_id_render() {
	$options = get_option( 'joinhook_settings' );
	?>
	<input type='text' name='joinhook_settings[join_device_id]' value='<?php echo esc_attr( $options['join_device_id'] ); ?>'>
	<?php
}

function join_app_icon_render() {
	$options = get_option( 'joinhook_settings' );
	$icon_url = ( isset( $options['join_app_icon'] ) && ! empty( $options['join_app_icon'] ) ) ? $options['join_app_icon'] : 'https://avatars3.githubusercontent.com/u/1082903';
	?>
	<input type='text' name='joinhook_settings[join_app_icon]' value='<?php echo esc_url( $icon_url ); ?>'> (Preview: <img src="<?php echo esc_url( $icon_url ); ?>" width="16" height="16" alt="Preview" />)
	<?php
}

function joinhook_settings_section_callback() {
	echo wp_kses_post( sprintf( esc_html__( 'Settings for the Join App. More information at %s', 'joinhook' ), '<a href="https://joaoapps.com/join/api/" target="_blank">joaoapps.com</a>' ) );
}

function joinhook_options_page() {
	?>
	<form action='options.php' method='post'>

		<h2>joinhook</h2>

		<?php
		settings_fields( 'joinhook_settings_page' );
		do_settings_sections( 'joinhook_settings_page' );
		submit_button();
		?>

	</form>
	<button id="joinhook_test" class='button-secondary'>Send Test</button>
	<p>
		<label for="joinhook_endpoint_url">Endpoint URL:</label>
		<input id="joinhook_endpoint_url" type="text" value="<?php echo esc_url( get_rest_url( null, '/webhooks/v1/join/' ) ); ?>" /><button id="joinhook_endpoint_url_copy" class="button-secondary" data-clipboard-target="#joinhook_endpoint_url">Copy to clipboard</button>
	</p>
	<script type="text/javascript" >
		/*!
		 * clipboard.js v1.6.0
		 * https://zenorocha.github.io/clipboard.js
		 *
		 * Licensed MIT © Zeno Rocha
		 */
		!function(e){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=e();else if("function"==typeof define&&define.amd)define([],e);else{var t;t="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,t.Clipboard=e()}}(function(){var e,t,n;return function e(t,n,o){function i(a,c){if(!n[a]){if(!t[a]){var l="function"==typeof require&&require;if(!c&&l)return l(a,!0);if(r)return r(a,!0);var u=new Error("Cannot find module '"+a+"'");throw u.code="MODULE_NOT_FOUND",u}var s=n[a]={exports:{}};t[a][0].call(s.exports,function(e){var n=t[a][1][e];return i(n?n:e)},s,s.exports,e,t,n,o)}return n[a].exports}for(var r="function"==typeof require&&require,a=0;a<o.length;a++)i(o[a]);return i}({1:[function(e,t,n){function o(e,t){for(;e&&e.nodeType!==i;){if(e.matches(t))return e;e=e.parentNode}}var i=9;if(Element&&!Element.prototype.matches){var r=Element.prototype;r.matches=r.matchesSelector||r.mozMatchesSelector||r.msMatchesSelector||r.oMatchesSelector||r.webkitMatchesSelector}t.exports=o},{}],2:[function(e,t,n){function o(e,t,n,o,r){var a=i.apply(this,arguments);return e.addEventListener(n,a,r),{destroy:function(){e.removeEventListener(n,a,r)}}}function i(e,t,n,o){return function(n){n.delegateTarget=r(n.target,t),n.delegateTarget&&o.call(e,n)}}var r=e("./closest");t.exports=o},{"./closest":1}],3:[function(e,t,n){n.node=function(e){return void 0!==e&&e instanceof HTMLElement&&1===e.nodeType},n.nodeList=function(e){var t=Object.prototype.toString.call(e);return void 0!==e&&("[object NodeList]"===t||"[object HTMLCollection]"===t)&&"length"in e&&(0===e.length||n.node(e[0]))},n.string=function(e){return"string"==typeof e||e instanceof String},n.fn=function(e){var t=Object.prototype.toString.call(e);return"[object Function]"===t}},{}],4:[function(e,t,n){function o(e,t,n){if(!e&&!t&&!n)throw new Error("Missing required arguments");if(!c.string(t))throw new TypeError("Second argument must be a String");if(!c.fn(n))throw new TypeError("Third argument must be a Function");if(c.node(e))return i(e,t,n);if(c.nodeList(e))return r(e,t,n);if(c.string(e))return a(e,t,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function i(e,t,n){return e.addEventListener(t,n),{destroy:function(){e.removeEventListener(t,n)}}}function r(e,t,n){return Array.prototype.forEach.call(e,function(e){e.addEventListener(t,n)}),{destroy:function(){Array.prototype.forEach.call(e,function(e){e.removeEventListener(t,n)})}}}function a(e,t,n){return l(document.body,e,t,n)}var c=e("./is"),l=e("delegate");t.exports=o},{"./is":3,delegate:2}],5:[function(e,t,n){function o(e){var t;if("SELECT"===e.nodeName)e.focus(),t=e.value;else if("INPUT"===e.nodeName||"TEXTAREA"===e.nodeName){var n=e.hasAttribute("readonly");n||e.setAttribute("readonly",""),e.select(),e.setSelectionRange(0,e.value.length),n||e.removeAttribute("readonly"),t=e.value}else{e.hasAttribute("contenteditable")&&e.focus();var o=window.getSelection(),i=document.createRange();i.selectNodeContents(e),o.removeAllRanges(),o.addRange(i),t=o.toString()}return t}t.exports=o},{}],6:[function(e,t,n){function o(){}o.prototype={on:function(e,t,n){var o=this.e||(this.e={});return(o[e]||(o[e]=[])).push({fn:t,ctx:n}),this},once:function(e,t,n){function o(){i.off(e,o),t.apply(n,arguments)}var i=this;return o._=t,this.on(e,o,n)},emit:function(e){var t=[].slice.call(arguments,1),n=((this.e||(this.e={}))[e]||[]).slice(),o=0,i=n.length;for(o;o<i;o++)n[o].fn.apply(n[o].ctx,t);return this},off:function(e,t){var n=this.e||(this.e={}),o=n[e],i=[];if(o&&t)for(var r=0,a=o.length;r<a;r++)o[r].fn!==t&&o[r].fn._!==t&&i.push(o[r]);return i.length?n[e]=i:delete n[e],this}},t.exports=o},{}],7:[function(t,n,o){!function(i,r){if("function"==typeof e&&e.amd)e(["module","select"],r);else if("undefined"!=typeof o)r(n,t("select"));else{var a={exports:{}};r(a,i.select),i.clipboardAction=a.exports}}(this,function(e,t){"use strict";function n(e){return e&&e.__esModule?e:{default:e}}function o(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}var i=n(t),r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},a=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),c=function(){function e(t){o(this,e),this.resolveOptions(t),this.initSelection()}return a(e,[{key:"resolveOptions",value:function e(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.action=t.action,this.emitter=t.emitter,this.target=t.target,this.text=t.text,this.trigger=t.trigger,this.selectedText=""}},{key:"initSelection",value:function e(){this.text?this.selectFake():this.target&&this.selectTarget()}},{key:"selectFake",value:function e(){var t=this,n="rtl"==document.documentElement.getAttribute("dir");this.removeFake(),this.fakeHandlerCallback=function(){return t.removeFake()},this.fakeHandler=document.body.addEventListener("click",this.fakeHandlerCallback)||!0,this.fakeElem=document.createElement("textarea"),this.fakeElem.style.fontSize="12pt",this.fakeElem.style.border="0",this.fakeElem.style.padding="0",this.fakeElem.style.margin="0",this.fakeElem.style.position="absolute",this.fakeElem.style[n?"right":"left"]="-9999px";var o=window.pageYOffset||document.documentElement.scrollTop;this.fakeElem.style.top=o+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,document.body.appendChild(this.fakeElem),this.selectedText=(0,i.default)(this.fakeElem),this.copyText()}},{key:"removeFake",value:function e(){this.fakeHandler&&(document.body.removeEventListener("click",this.fakeHandlerCallback),this.fakeHandler=null,this.fakeHandlerCallback=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)}},{key:"selectTarget",value:function e(){this.selectedText=(0,i.default)(this.target),this.copyText()}},{key:"copyText",value:function e(){var t=void 0;try{t=document.execCommand(this.action)}catch(e){t=!1}this.handleResult(t)}},{key:"handleResult",value:function e(t){this.emitter.emit(t?"success":"error",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})}},{key:"clearSelection",value:function e(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()}},{key:"destroy",value:function e(){this.removeFake()}},{key:"action",set:function e(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"copy";if(this._action=t,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function e(){return this._action}},{key:"target",set:function e(t){if(void 0!==t){if(!t||"object"!==("undefined"==typeof t?"undefined":r(t))||1!==t.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===this.action&&t.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===this.action&&(t.hasAttribute("readonly")||t.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');this._target=t}},get:function e(){return this._target}}]),e}();e.exports=c})},{select:5}],8:[function(t,n,o){!function(i,r){if("function"==typeof e&&e.amd)e(["module","./clipboard-action","tiny-emitter","good-listener"],r);else if("undefined"!=typeof o)r(n,t("./clipboard-action"),t("tiny-emitter"),t("good-listener"));else{var a={exports:{}};r(a,i.clipboardAction,i.tinyEmitter,i.goodListener),i.clipboard=a.exports}}(this,function(e,t,n,o){"use strict";function i(e){return e&&e.__esModule?e:{default:e}}function r(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}function a(e,t){if(!e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!t||"object"!=typeof t&&"function"!=typeof t?e:t}function c(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function, not "+typeof t);e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,enumerable:!1,writable:!0,configurable:!0}}),t&&(Object.setPrototypeOf?Object.setPrototypeOf(e,t):e.__proto__=t)}function l(e,t){var n="data-clipboard-"+e;if(t.hasAttribute(n))return t.getAttribute(n)}var u=i(t),s=i(n),f=i(o),d=function(){function e(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}return function(t,n,o){return n&&e(t.prototype,n),o&&e(t,o),t}}(),h=function(e){function t(e,n){r(this,t);var o=a(this,(t.__proto__||Object.getPrototypeOf(t)).call(this));return o.resolveOptions(n),o.listenClick(e),o}return c(t,e),d(t,[{key:"resolveOptions",value:function e(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.action="function"==typeof t.action?t.action:this.defaultAction,this.target="function"==typeof t.target?t.target:this.defaultTarget,this.text="function"==typeof t.text?t.text:this.defaultText}},{key:"listenClick",value:function e(t){var n=this;this.listener=(0,f.default)(t,"click",function(e){return n.onClick(e)})}},{key:"onClick",value:function e(t){var n=t.delegateTarget||t.currentTarget;this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new u.default({action:this.action(n),target:this.target(n),text:this.text(n),trigger:n,emitter:this})}},{key:"defaultAction",value:function e(t){return l("action",t)}},{key:"defaultTarget",value:function e(t){var n=l("target",t);if(n)return document.querySelector(n)}},{key:"defaultText",value:function e(t){return l("text",t)}},{key:"destroy",value:function e(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)}}],[{key:"isSupported",value:function e(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:["copy","cut"],n="string"==typeof t?[t]:t,o=!!document.queryCommandSupported;return n.forEach(function(e){o=o&&!!document.queryCommandSupported(e)}),o}}]),t}(s.default);e.exports=h})},{"./clipboard-action":7,"good-listener":4,"tiny-emitter":6}]},{},[8])(8)});
		new Clipboard( '#joinhook_endpoint_url_copy' );

		jQuery( document ).ready(function( $ ) {
			$( '#joinhook_test' ).click( function() {
				var test_payload = '{"EventType":"Download","Series":{"Id":99,"Title":"The Sampsorns","Path":"/download/Videos/The Sampsorns","TvdbId":12345},"Episodes":[{"Id":6001,"EpisodeNumber":10,"SeasonNumber":22,"Title":"Who killed Mr. Buns (1)","AirDate":"2010-01-10","AirDateUtc":"2010-01-10T01:00:00Z","Quality":"HDTV-720p","QualityVersion":2,"ReleaseGroup":"BEETS","SceneName":"The Sampsorns S22E12-E10 PROPER 720p HDTV x264-BEETS"},{"Id":5088,"EpisodeNumber":11,"SeasonNumber":22,"Title":"Who killed Mr. Buns (2)","AirDate":"2010-01-10","AirDateUtc":"2010-01-10T01:25:00Z","Quality":"HDTV-720p","QualityVersion":2,"ReleaseGroup":"BEETS","SceneName":"The Sampsorns S28E12-E13 PROPER 720p HDTV x264-BEETS"}]}';

					jQuery.post( '<?php echo esc_url_raw( get_rest_url( null, '/webhooks/v1/join/' ) ); ?>', test_payload, function( response ) {
						console.log( response );
					} );
			} );

		});
	</script>
	<?php
}
