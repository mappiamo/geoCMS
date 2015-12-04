<?php

// no direct access to this file
defined( 'DACCESS' ) or die;

class M_Template extends M_Extension {

		private $content = '';

		private $head = '';
		private $body = '';
		private $foot = '';

		private $ajaxrun = false;

		public function __construct() {
				$this->set_type();
		}

		public function instance( $module ) {
				if ( ! $module || ! is_object( $module ) ) die( 'M_ERROR (00216): Cannot load template: No module object found' );

				if ( $module->is_ajax() ) $this->ajaxrun = true;

				$ajaxrunners = array( 'ajax', 'majax', 'api' );

				if ( $this->ajaxrun || ( $module->get_name() && in_array( $module->get_name(), $ajaxrunners ) ) ) {
						$this->ajax_instance( $module );
				} else {
						if ( 'manager' == ENV ) $name = 'manager';
						else {
								$name = $this->get_default();
								if ( ! $name ) die( 'M_ERROR (00217): Cannot load template: Default template not set' );
						}

						if ( $module->get_output() && strlen( $module->get_output() ) > 0 ) $this->content = $module->get_output() . "\n";
						if ( $module->get_page_meta() ) $this->page_meta = $module->get_page_meta();
						if ( $module->get_page_assets() ) $this->page_assets = $module->get_page_assets();
						if ( $module->get_page_js() ) $this->page_js = $module->get_page_js();

						if ( $this->get_details( $name ) ) $this->render( $module );
						else die( 'M_ERROR (00211): Cannot load template: Template not found' );
				}
		}

		public function output_assets( $type ) {
				switch ( $type ) {
						case 'js':
								if ( is_array( $this->page_assets['js'] ) && sizeof( $this->page_assets['js'] ) > 0 ) {
										foreach ( $this->page_assets['js'] as $asset ) {
												
												if ( 'http' != substr( $asset , 0, 4 ) ) {
														$source = 'assets/js/' . $asset . '.js';
														if ( 'manager' == ENV ) $source = '../' . $source;
												} else {
														$source = $asset;
												}
												
												echo "<script type=\"text/javascript\" src=\"$source\"></script>";
												echo "\n\t\t";
										}
										echo "\n";
								}
						break;
						case 'css':
								if ( is_array( $this->page_assets['css'] ) && sizeof( $this->page_assets['css'] ) > 0 ) {
										foreach ( $this->page_assets['css'] as $asset ) {
												$source = 'assets/css/' . $asset . '.css';

												if ( 'manager' == ENV ) $source = '../' . $source;
												
												echo "<link rel=\"stylesheet\" href=\"$source\" />";
												echo "\n\t\t";
										}
										echo "\n";
								}
						break;
				}
		}

		public function meta() {
				if ( is_array( $this->page_meta ) && sizeof( $this->page_meta ) > 0 ) {
						foreach ( $this->page_meta as $key => $value ) if ($key != 'description') {
								echo '<meta name="' . MPut::html_attr( $key ) . '" content="' . MPut::html_attr( $value ) . '" />' . "\n\t";
						}
				}

			if ((isset($_GET['object'])) && ($_GET['module'] == 'content')) {
				$ContentDesc = MObject::get('content', $_GET['object']);

				$pref = new M_Preference("website_title");
				$SiteName = $pref->get_value();

				$ExtID = $ContentDesc->get_id();
				$image_url = ORM::for_table('content_media')->select_many('title', 'url')->where('external_id', $ExtID)->where('default_media', 1)->limit(1)->find_one();
				$FullURL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; //rtrim(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/\\');

				$TemplateSQL = ORM::for_table('templates')->select('name')->where('manager', 0)->where('enabled', 1)->where('default_template', 1)->find_one();
				$TemplateName = $TemplateSQL['name'];

				echo '<meta name="description" content="'.mb_substr(str_replace('"', '', strip_tags($ContentDesc->get_text())), 0, 155, 'UTF-8') . '" />'."\n\t"; ?>
				<meta property="og:title" content="<?PHP echo $ContentDesc->get_title(); ?>" />
				<meta property="og:type" content="<?PHP echo $ContentDesc->get_type(); ?>" />
				<meta property="og:url" content="<?PHP echo $FullURL; ?>" />
				<?PHP if ($image_url) { ?>
					<meta property="og:image" content="<?PHP echo $image_url['url']; ?>" />
				<?PHP } else { ?>
					<meta property="og:image" content="templates/<?PHP echo $TemplateName; ?>/images/logo.png" />
				<?PHP } ?>
				<meta property="og:site_name" content="<?PHP echo $SiteName; ?>" />
				<meta property="og:description" content="<?PHP echo mb_substr(str_replace('"', '', strip_tags($ContentDesc->get_text())), 0, 155, 'UTF-8'); ?>" />
				<meta property="og:locale" content="<?PHP echo strtolower($ContentDesc->get_language()).'_'.strtoupper($ContentDesc->get_language()); ?>" />

				<meta name="twitter:card" content="summary" />
				<meta name="twitter:site" content="<?PHP echo $FullURL; ?>" />
				<meta name="twitter:title" content="<?PHP echo $ContentDesc->get_title(); ?>" />
				<meta name="twitter:description" content="<?PHP echo mb_substr(str_replace('"', '', strip_tags($ContentDesc->get_text())), 0, 155, 'UTF-8'); ?>" />
				<?PHP if ($image_url) { ?>
					<meta name="twitter:image" content="<?PHP echo $image_url['url']; ?>" />
				<?PHP } else { ?>
					<meta property="og:image" content="templates/<?PHP echo $TemplateName; ?>/images/logo.png" />
				<?PHP } ?>

				<?PHP
				//echo '<base href="' . rtrim(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) ? 'https://' : 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/\\') . '/">';
			}
		}

		public function js() {
				if ( is_array( $this->page_js ) && sizeof( $this->page_js ) > 0 ) {
						foreach ( $this->page_js as $js ) {
								echo "<script>\n" . json_encode( $js ) . "\n</script>";
								echo "\n\t\t";
						}
				}
		}

		public function output_js() {
				$this->js();
		}

		private function render( $module = null ) {
				if ( $module && is_object( $module ) ) $this->set_page_title( $module->get_page_title() );

				if ( ! $this->get_wdir() ) die( 'M_ERROR (00212): Cannot load template: Template details are missing' );

				$this->render_head();

				if ( $this->head && strlen( $this->head ) > 0 ) echo $this->head;

				$this->render_body( $module );

				if ( $this->body && strlen( $this->body ) > 0 ) echo $this->body;

				$this->render_foot();

				if ( $this->foot && strlen( $this->foot ) > 0 ) echo $this->foot;
		}

		private function render_head() {
				$headfile = mapi_abs_path( 'head', $this->get_wdir() );

				if ( is_file( $headfile ) && is_readable( $headfile ) ) {
						ob_start();
				
						include( $headfile );

						$this->head = ob_get_contents();

						ob_end_clean();
				} else {
						die( 'M_ERROR (00213): Cannot load template: Template head file is missing' );
				}
		}

		private function render_body( $module ) {
				$bodyfile = null;

				if ( $module->get_name() && strlen( $module->get_name() ) > 0 ) $bodyfile = mapi_abs_path( $module->get_name(), $this->get_wdir() );

				if ( ! $bodyfile ) $bodyfile = mapi_abs_path( 'body', $this->get_wdir() );

				if ( is_file( $bodyfile ) && is_readable( $bodyfile ) ) {
						ob_start();

						include( $bodyfile );

						$this->body = ob_get_contents();

						ob_end_clean();
				} else {
						die( 'M_ERROR (00214): Cannot load template: Template body file is missing' );
				}
		}

		private function render_foot() {
				$footfile = mapi_abs_path( 'foot', $this->get_wdir() );

				if ( is_file( $footfile ) && is_readable( $footfile ) ) {
						ob_start();

						include( $footfile );

						$this->foot = ob_get_contents();

						ob_end_clean();
				} else {
						die( 'M_ERROR (00215): Cannot load template: Template foot file is missing' );
				}
		}

		private function ajax_instance( $module ) {
				if ( $module->get_output() && strlen( $module->get_output() ) > 0 ) $this->content = $module->get_output() . "\n";
				else $this->content = "\n";

				echo $this->content;
		}

}

?>
