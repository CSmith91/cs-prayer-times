<?php
/**
 * Render for the Prayer Times Widget
 * This file uses shortcodes registered in the Prayer Times Manager plugin.
 */
?>

<div <?php echo get_block_wrapper_attributes(); ?> class="prayer-times-widget">

	<!-- Date Section -->
	<div class="prayer-times-dates">
		<p><strong><?php echo do_shortcode('[prayer_date]'); ?></strong></p>
	</div>

	<!-- Countdown & Next Prayer -->
	<div class="prayer-times-next">
		<p id="prayer-next">Loading next prayer...</p>
		<p id="prayer-countdown">Loading countdown...</p>
	</div>

	<!-- Prayer Times List -->
	<div class="prayer-times-table">
		<div class="prayer-grid">
			<?php 
                // Define the prayers with corresponding shortcode attributes.
                $prayers = array(
                    'Fajr'     => array('fajr_start', 'fajr_iqamah'),
                    'Sunrise'  => array('sunrise'),
                    'Zuhr'     => array('zuhr_start', 'zuhr_iqamah'),
                    'Asr'      => array('asr_start', 'asr_iqamah'),
                    'Maghrib'  => array('maghrib_start', 'maghrib_iqamah'),
                    'Isha'     => array('isha_start', 'isha_iqamah'),
                );
                $labels = array(
                    'start'  => 'Begins',
                    'iqamah' => 'Iqamah'
                );
                
                foreach ($prayers as $prayerName => $fields) :
            ?>

				<div class="prayer-cell" id="<?php echo esc_attr($prayerName); ?>-cell">
                    <span class="prayer-name"><?php echo esc_html($prayerName); ?></span>
                    <span class="prayer-time">
                        <?php echo esc_html($labels['start']); ?><br>
                        <?php echo do_shortcode('[prayer_time prayer="' . $fields[0] . '"]'); ?>
                    </span>
                    <?php if (count($fields) > 1) : ?>
                        <span class="prayer-iqamah">
                            <?php echo esc_html($labels['iqamah']); ?><br>
                            <?php echo do_shortcode('[prayer_time prayer="' . $fields[1] . '"]'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
		</div>
	</div>
</div>
