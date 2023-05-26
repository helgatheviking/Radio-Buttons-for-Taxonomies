<div class="wrap">
	<!-- Display Header, and Description -->
	<style scoped="scoped">
		fieldset {
			font-size: 1.5em;
			margin: 1em 0 .5em;
		}
	</style>

	<h2><?php _e('Radio Buttons for Taxonomies', 'radio-buttons-for-taxonomies'); ?></h2>

	<!-- Beginning of the Plugin Options Form -->
	<form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
		<?php settings_fields('radio_button_for_taxonomies_options'); ?>
		<?php $options = Radio_Buttons_For_Taxonomies()->get_options(); ?>

		<fieldset>
			<legend><?php esc_html_e('Select taxonomies to convert to radio buttons', 'radio-buttons-for-taxonomies'); ?></legend>
			<table>
				<tbody>

					<?php
					$taxonomies = Radio_Buttons_for_Taxonomies()->get_all_taxonomies();

					if (!empty($taxonomies)) {

						foreach ($taxonomies as $i => $taxonomy) {
							$is_checked = isset($options['taxonomies']) && is_array($options['taxonomies']) && Radio_Buttons_For_Taxonomies()->in_array_r($i, $options['taxonomies']);
							$id = "rbt_$i";
					?>
							<tr>
								<td>
									<label for="<?php echo esc_attr($id); ?>">
										<input type="checkbox" name="radio_button_for_taxonomies_options[taxonomies][]" value="<?php echo esc_attr($i); ?>" <?php checked($is_checked, 1); ?> id="<?php echo esc_attr($id); ?>" />
										<?php printf('%s <small>(%s)</small>', esc_html($taxonomy->labels->name), esc_html($i)); ?>
									</label>
								</td>
								<?php
								if (!empty($taxonomy->object_type)) {
									foreach ($taxonomy->object_type as $j => $post_type) {
										$is_checked = isset($options['taxonomies'][$i]) && is_array($options['taxonomies'][$i]) && Radio_Buttons_For_Taxonomies()->in_array_r($post_type, $options['taxonomies'][$i]);
										$id = "rbt_$i-$post_type";
								?>
										<td>
											<label for="<?php echo esc_attr($id); ?>">
												<input type="checkbox" name="radio_button_for_taxonomies_options[taxonomies][<?php echo esc_attr($i); ?>][]" value="<?php echo esc_attr($post_type); ?>" <?php checked($is_checked, 1); ?> id="<?php echo esc_attr($id); ?>" />
												<?php printf(esc_html($post_type), esc_html($i)); ?>
											</label>
										</td>
								<?php
									}
								}
								?>
							</tr>
					<?php
						}
					}
					?>
				</tbody>
			</table>
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e('Options', 'radio-buttons-for-taxonomies'); ?></legend>
			<p>
				<label for="rbt_delete">
					<input type="checkbox" name="radio_button_for_taxonomies_options[delete]" id="rbt_delete" value="1" <?php checked(!empty($options['delete']), 1); ?> />
					<?php esc_html_e('Completely remove options on plugin removal', 'radio-buttons-for-taxonomies'); ?>
				</label>
			</p>
		</fieldset>

		<p class="submit">
			<input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'radio-buttons-for-taxonomies') ?>" />
		</p>
	</form>
</div>