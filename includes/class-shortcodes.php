<?php

namespace Ainsys\Connector\Content;

use Ainsys\Connector\Master\Hooked;

class Shortcodes implements Hooked {

	public function init_hooks() {

		add_shortcode( 'ainsys_changelog', [ $this, 'ainsys_changelog_callback' ] );
	}


	public function ainsys_changelog_callback() {

		$data = get_post_meta( get_the_ID(), '_ainsys_entity_data', true );

		if ( ! $data ) {
			return null;
		}

		$interim_data = [];

		foreach ( $data as $key => $value ) {
			if ( false !== stripos( $key, 'array' ) && false !== stripos( $key, 'changelog' ) ) {
				$interim_data[ $key ] = explode( '|', $value );
			}

		}

		$update_data = [];

		foreach ( $interim_data as $key => $part ) {
			foreach ( $part as $index => $value ) {
				$update_data[ $index ][ str_replace( [ '{', '}', 'array', 'changelog', '_' ], [ '', '', '', '' , '' ], $key ) ] = trim( $value );
			}
		}

		ob_start();
		?>
		<style>
			.ainsys-changelog-wrapper .ainsys-changelog-item {
				margin-bottom: 2.25rem;
			}

			.ainsys-changelog-wrapper .item-meta {
				display: flex;
				align-items: center;
				gap: 1em;
			}

			.ainsys-changelog-wrapper .item-meta h2 {
				margin: 0;
			}
		</style>

		<div class="ainsys-changelog-wrapper">
			<div class="container">
				<?php if ( $update_data ): ?>
					<ul class="ainsys-changelog">
						<?php
						foreach ( $update_data as $item_data ) :

							$descriptions = array_map( 'trim', explode( ';', $item_data['desc'] ) );

							?>
							<li class="ainsys-changelog-item">
								<div class="item-meta">
									<h2 class="item-tag"><?php echo esc_html( $item_data['tag'] ); ?></h2>
									<div class="item-date"><?php echo esc_html( $item_data['date'] ); ?></div>
								</div>
								<?php if ( ! empty( $descriptions ) ): ?>
									<div class="item-description">
										<ul>
											<?php foreach ( $descriptions as $description ): ?>
												<li>
													<?php echo esc_html( $description ); ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

}