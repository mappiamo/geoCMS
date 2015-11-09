<?php
/**
 * Created by PhpStorm.
 * User: Laca
 * Date: 2015.09.24.
 * Time: 16:51
 */

	defined('DACCESS') or die;

	function mwidget_lastcontent($c_num = 5, $c_type = 'place', $c_order = 'created', $c_filter = NULL) {

		$lang = new M_Language;
		$language = $lang->getLanguage();

		if (!$c_filter) {
			$contents = ORM::for_table('contents')->select_many('title', 'id', 'text', 'address', 'start', 'end')->where('type', $c_type)->order_by_desc($c_order)->limit($c_num)->find_array();
		} else {
			if ($c_filter ==  'now') {
				$DateFilter = "`end` >= now() AND `start` <= now()";
				$contents = ORM::for_table('contents')->select_many('title', 'id', 'text', 'address', 'start', 'end')->where('type', $c_type)->where_raw($DateFilter)->order_by_desc($c_order)->limit($c_num)->find_array();
			}
		}


		if ($contents) {
			if ((is_array($contents)) && (count($contents) > 0)) { ?>

				<h3 class="color">Latest contents:</h3>

				<?PHP foreach ($contents as $One_Content) { ?>

					<div class="latest">
						<a href="index.php?module=content&object=<?PHP echo $One_Content['id']; ?>" title="<?PHP echo $One_Content['title']; ?>"><h3 class="color"><?PHP echo $One_Content['title']; ?></h3></a>
						<div>

							<?PHP
								$ExtID = $One_Content['id'];
								$image_url = ORM::for_table('content_media')->select_many('title', 'url')->where('external_id', $ExtID)->where('default_media', 1)->limit(1)->find_one();
								if ($image_url) {
							?>

								<div class="latest-image">
									<img src="<?PHP echo $image_url['url']; ?>" alt="<?PHP echo $image_url['title']; ?>" />
								</div>

							<?PHP } ?>

							<div class="latest-content">
								<?PHP
									if ($c_type == 'event') {
										echo '<span class="glyphicon glyphicon-circle-arrow-right"></span> ' . date('d-m-Y H:i', strtotime($One_Content['start'])) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="glyphicon glyphicon-circle-arrow-left"></span> ' . date('d-m-Y H:i', strtotime($One_Content['end']));
									}
								?>
								<p>
									<?PHP echo mb_substr(strip_tags($One_Content['text']), 0, 150, 'UTF-8'); ?>...<br />
									<a href="index.php?module=content&object=<?PHP echo $One_Content['id']; ?>" title="<?PHP echo $One_Content['title']; ?>" class="readmore">Leggi tutto &gt;</a>
								</p>
							</div>
							<div style="clear: both;"></div>
						</div>
					</div>
					<div class="separator"></div>

				<?PHP }
			}
		} else { ?>
			<div class="latest">
				No running events today.
			</div>
		<?PHP }
	}