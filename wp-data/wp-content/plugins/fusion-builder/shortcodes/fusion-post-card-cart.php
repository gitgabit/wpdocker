<?php
/**
 * Add an element to fusion-builder.
 *
 * @package fusion-builder
 * @since 3.3
 */

if ( fusion_is_element_enabled( 'fusion_post_card_cart' ) ) {

	if ( ! class_exists( 'FusionSC_PostCardCart' ) ) {
		/**
		 * Shortcode class.
		 *
		 * @since 3.3
		 */
		class FusionSC_PostCardCart extends Fusion_Element {

			/**
			 * Whether styles are already generated or not.
			 *
			 * @access protected
			 * @since 3.3
			 * @var bool
			 */
			protected $styles_generated = false;

			/**
			 * Whether we are requesting from editor.
			 *
			 * @access public
			 * @since 3.8
			 * @var string
			 */
			protected $live_request = false;

			/**
			 * Constructor.
			 *
			 * @access public
			 * @since 3.3
			 */
			public function __construct() {
				parent::__construct();
				add_filter( 'fusion_attr_post-card-cart', [ $this, 'attr' ] );
				add_filter( 'fusion_attr_post-card-cart-icon', [ $this, 'icon_attr' ] );
				add_filter( 'fusion_attr_post-card-cart-details-icon', [ $this, 'icon_details_attr' ] );
				add_shortcode( 'fusion_post_card_cart', [ $this, 'render' ] );

				// Ajax mechanism for live editor.
				add_action( 'wp_ajax_get_fusion_post_card_cart', [ $this, 'ajax_render' ] );
			}

			/**
			 * Render for live editor.
			 *
			 * @static
			 * @access public
			 * @since 3.3
			 * @param array $defaults An array of defaults.
			 * @return void
			 */
			public function ajax_render( $defaults ) {
				check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );

				$return_data = [];
				// From Ajax Request.
				if ( isset( $_POST['model'] ) && isset( $_POST['model']['params'] ) && ! apply_filters( 'fusion_builder_live_request', false ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$args               = $_POST['model']['params'];                                                                       // phpcs:ignore WordPress.Security
					$post_id            = isset( $_POST['post_id'] ) ? $_POST['post_id'] : get_the_ID();                                   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					$this->defaults     = self::get_element_defaults();
					$this->args         = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_tb_woo_checkout_tabs' );
					$this->live_request = true;

					// Check if dynamic source is a term and if so emulate.
					if ( isset( $_POST['fusion_meta'] ) ) {
						$meta = fusion_string_to_array( $_POST['fusion_meta'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						if ( isset( $meta['_fusion']['dynamic_content_preview_type'] ) && 'term' === $meta['_fusion']['dynamic_content_preview_type'] && isset( $meta['_fusion']['preview_term'] ) && '' !== $meta['_fusion']['preview_term'] ) {
							$GLOBALS['wp_query']->is_tax         = true;
							$GLOBALS['wp_query']->is_archive     = true;
							$GLOBALS['wp_query']->queried_object = get_term_by( 'id', $post_id, (string) $meta['_fusion']['preview_term'] );
						}
					}

					fusion_set_live_data();
					add_filter( 'fusion_builder_live_request', '__return_true' );

					global $product;

					if ( is_null( $product ) ) {
						$product = wc_get_product( $post_id );
						$this->in_cart();
					}

					if ( ! empty( $product ) ) {
						$return_data['fusion_post_card_cart'] = $this->get_cart_content();
						$return_data['product_type']          = $product->get_type();
					}
				}

				echo wp_json_encode( $return_data );
				wp_die();
			}

			/**
			 * Gets the default values.
			 *
			 * @static
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public static function get_element_defaults() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'hide_on_mobile'                       => fusion_builder_default_visibility( 'string' ),
					'class'                                => '',
					'id'                                   => '',
					'animation_type'                       => '',
					'animation_direction'                  => 'down',
					'animation_speed'                      => '0.1',
					'animation_delay'                      => '',
					'animation_offset'                     => $fusion_settings->get( 'animation_offset' ),
					'animation_color'                      => '',

					'buttons_layout'                       => '',
					'cart_layout'                          => '',
					'justify'                              => '',
					'align'                                => '',
					'buttons_justify'                      => '',
					'buttons_alignment'                    => '',

					'show_product_link_button'             => '',
					'show_add_to_cart_button'              => '',
					'show_variations'                      => 'no',
					'show_quantity_input'                  => 'no',
					'buttons_stretch'                      => 'no',
					'enable_quick_view'                    => $fusion_settings->get( 'woocommerce_enable_quick_view' ),

					'margin_top'                           => '',
					'margin_right'                         => '',
					'margin_bottom'                        => '',
					'margin_left'                          => '',

					'quantity_style'                       => '',
					'button_style'                         => '',
					'product_link_style'                   => '',

					'quantity_width'                       => '',
					'quantity_height'                      => '',
					'quantity_radius_top_left'             => '',
					'quantity_radius_top_right'            => '',
					'quantity_radius_bottom_right'         => '',
					'quantity_radius_bottom_left'          => '',
					'quantity_font_size'                   => '',
					'quantity_color'                       => '',
					'quantity_background'                  => '',
					'quantity_border_sizes_top'            => '',
					'quantity_border_sizes_right'          => '',
					'quantity_border_sizes_bottom'         => '',
					'quantity_border_sizes_left'           => '',
					'quantity_border_color'                => '',
					'qbutton_border_sizes_top'             => '',
					'qbutton_border_sizes_right'           => '',
					'qbutton_border_sizes_bottom'          => '',
					'qbutton_border_sizes_left'            => '',
					'qbutton_color'                        => '',
					'qbutton_background'                   => '',
					'qbutton_border_color'                 => '',
					'qbutton_color_hover'                  => '',
					'qbutton_background_hover'             => '',
					'qbutton_border_color_hover'           => '',
					'quantity_margin_top'                  => '',
					'quantity_margin_right'                => '',
					'quantity_margin_bottom'               => '',
					'quantity_margin_left'                 => '',

					'button_size'                          => '',

					'button_border_top'                    => '',
					'button_border_right'                  => '',
					'button_border_bottom'                 => '',
					'button_border_left'                   => '',
					'button_icon'                          => '',
					'icon_position'                        => 'left',
					'button_color'                         => '',
					'button_gradient_top'                  => $fusion_settings->get( 'button_gradient_top_color' ),
					'button_gradient_bottom'               => $fusion_settings->get( 'button_gradient_bottom_color' ),
					'button_border_color'                  => $fusion_settings->get( 'button_border_color' ),
					'button_color_hover'                   => $fusion_settings->get( 'button_accent_hover_color' ),
					'button_gradient_top_hover'            => $fusion_settings->get( 'button_gradient_top_color_hover' ),
					'button_gradient_bottom_hover'         => $fusion_settings->get( 'button_gradient_bottom_color_hover' ),
					'button_border_color_hover'            => '',
					'button_margin_top'                    => '',
					'button_margin_right'                  => '',
					'button_margin_bottom'                 => '',
					'button_margin_left'                   => '',
					'link_font_size'                       => '',
					'link_color'                           => '',
					'link_hover_color'                     => $fusion_settings->get( 'link_hover_color' ),
					'button_details_size'                  => '',
					'button_details_border_top'            => '',
					'button_details_border_right'          => '',
					'button_details_border_bottom'         => '',
					'button_details_border_left'           => '',
					'button_details_icon'                  => '',
					'icon_details_position'                => 'left',
					'button_details_color'                 => '',
					'button_details_gradient_top'          => $fusion_settings->get( 'button_gradient_top_color' ),
					'button_details_gradient_bottom'       => $fusion_settings->get( 'button_gradient_bottom_color' ),
					'button_details_border_color'          => $fusion_settings->get( 'button_gradient_top_color_hover' ),
					'button_details_color_hover'           => $fusion_settings->get( 'button_gradient_bottom_color_hover' ),
					'button_details_gradient_top_hover'    => '',
					'button_details_gradient_bottom_hover' => '',
					'button_details_border_color_hover'    => '',

					'button_details_margin_top'            => '',
					'button_details_margin_right'          => '',
					'button_details_margin_bottom'         => '',
					'button_details_margin_left'           => '',
					'product_link_font_size'               => '',
					'product_link_color'                   => '',
					'product_link_hover_color'             => '',

					'variation_layout'                     => 'floated',
					'variation_label_area_width'           => '',
					'variation_text_align'                 => '',
					'variation_clear'                      => 'absolute',
					'clear_content'                        => '',
					'clear_icon'                           => '',
					'clear_text'                           => '',

					'show_label'                           => 'yes',
					'fusion_font_family_label_typography'  => '',
					'fusion_font_variant_label_typography' => '400',
					'label_font_size'                      => '',
					'label_text_transform'                 => '',
					'label_line_height'                    => '',
					'label_letter_spacing'                 => '',
					'label_color'                          => $fusion_settings->get( 'body_typography', 'color' ),

					'select_style'                         => '',
					'select_height'                        => '',
					'fusion_font_family_select_typography' => '',
					'fusion_font_variant_select_typography' => '400',
					'select_font_size'                     => '',
					'select_text_transform'                => '',
					'select_line_height'                   => '',
					'select_letter_spacing'                => '',
					'select_color'                         => '',
					'select_background'                    => '',
					'select_border_color'                  => '',
					'select_border_sizes_top'              => '',
					'select_border_sizes_right'            => '',
					'select_border_sizes_bottom'           => '',
					'select_border_sizes_left'             => '',
					'border_radius_top_left'               => '',
					'border_radius_top_right'              => '',
					'border_radius_bottom_right'           => '',
					'border_radius_bottom_left'            => '',

					'swatch_style'                         => '',
					'swatch_margin_top'                    => '',
					'swatch_margin_right'                  => '',
					'swatch_margin_bottom'                 => '',
					'swatch_margin_left'                   => '',
					'swatch_background_color'              => '',
					'swatch_background_color_active'       => '',
					'swatch_border_sizes_top'              => '',
					'swatch_border_sizes_right'            => '',
					'swatch_border_sizes_bottom'           => '',
					'swatch_border_sizes_left'             => '',
					'swatch_border_color'                  => '',
					'swatch_border_color_active'           => '',
					'color_swatch_height'                  => '',
					'color_swatch_width'                   => '',
					'color_swatch_padding_top'             => '',
					'color_swatch_padding_right'           => '',
					'color_swatch_padding_bottom'          => '',
					'color_swatch_padding_left'            => '',
					'color_swatch_border_radius_top_left'  => '',
					'color_swatch_border_radius_top_right' => '',
					'color_swatch_border_radius_bottom_right' => '',
					'color_swatch_border_radius_bottom_left' => '',
					'image_swatch_height'                  => '',
					'image_swatch_width'                   => '',
					'image_swatch_padding_top'             => '',
					'image_swatch_padding_right'           => '',
					'image_swatch_padding_bottom'          => '',
					'image_swatch_padding_left'            => '',
					'image_swatch_border_radius_top_left'  => '',
					'image_swatch_border_radius_top_right' => '',
					'image_swatch_border_radius_bottom_right' => '',
					'image_swatch_border_radius_bottom_left' => '',
					'button_swatch_height'                 => '',
					'button_swatch_width'                  => '',
					'button_swatch_padding_top'            => '',
					'button_swatch_padding_right'          => '',
					'button_swatch_padding_bottom'         => '',
					'button_swatch_padding_left'           => '',
					'button_swatch_border_radius_top_left' => '',
					'button_swatch_border_radius_top_right' => '',
					'button_swatch_border_radius_bottom_right' => '',
					'button_swatch_border_radius_bottom_left' => '',
					'button_swatch_font_size'              => '',
					'button_swatch_color'                  => '',
					'button_swatch_color_active'           => '',
				];
			}

			/**
			 * Render the shortcode.
			 *
			 * @access public
			 * @since 3.3
			 * @param  array  $args   Shortcode parameters.
			 * @param  string $content Content between shortcode.
			 * @return string   HTML output.
			 */
			public function render( $args, $content = '' ) {
				global $product;
				if ( ! class_exists( 'WooCommerce', false ) ) {
					return;
				}
				if ( empty( $product ) || ! $product->is_purchasable() ) {
					return;
				}
				$post_id = apply_filters( 'fusion_dynamic_post_id', get_the_ID() );
				if ( ! function_exists( 'wc_get_product' ) || 'product' !== get_post_type( $post_id ) ) {
					return '';
				}

				$this->defaults = self::get_element_defaults();
				$this->args     = FusionBuilder::set_shortcode_defaults( $this->defaults, $args, 'fusion_post_card_cart' );

				$this->validate_args();

				add_action( 'fusion_post_cards_rendered', [ $this, 'render_variation_js' ] );

				// Check items in cart.
				$this->in_cart();

				$html  = '<div ' . FusionBuilder::attributes( 'post-card-cart' ) . '>';
				$html .= $this->get_cart_content();
				$html .= '</div>';

				$this->on_render();

				return apply_filters( 'fusion_element_post_card_cart_content', $html, $args );
			}

			/**
			 * Validate args to format we want.
			 *
			 * @access public
			 * @since 3.3
			 * @return void
			 */
			public function validate_args() {
				if ( 'yes' === $this->args['enable_quick_view'] ) {
					$this->args['enable_quick_view'] = '1';
				} elseif ( 'no' === $this->args['enable_quick_view'] ) {
					$this->args['enable_quick_view'] = '0';
				}

				// Legacy single border width.
				if ( isset( $this->args['button_border_width'] ) && ! isset( $this->args['button_border_top'] ) ) {
					$this->args['button_border_top']    = $this->args['button_border_width'];
					$this->args['button_border_right']  = $this->args['button_border_top'];
					$this->args['button_border_bottom'] = $this->args['button_border_top'];
					$this->args['button_border_left']   = $this->args['button_border_top'];
				}
				if ( isset( $this->args['button_details_border_width'] ) && ! isset( $this->args['button_details_border_top'] ) ) {
					$this->args['button_details_border_top']    = $this->args['button_details_border_top'];
					$this->args['button_details_border_right']  = $this->args['button_border_top'];
					$this->args['button_details_border_bottom'] = $this->args['button_border_top'];
					$this->args['button_details_border_left']   = $this->args['button_border_top'];
				}
			}

			/**
			 * Fires on first render.
			 *
			 * @access protected
			 * @since 3.11
			 */
			protected function on_first_render() {
				// Skip if empty.
				if ( null === $this->args || empty( $this->args ) ) {
					return;
				}

				if ( function_exists( 'wc_enqueue_js' ) && function_exists( 'fusion_is_post_card' ) && fusion_is_post_card() && 'yes' === $this->args['show_variations'] ) {
					$this->enqueue_wc_variation_js( '.fusion-post-card-cart' );
				}

				if ( class_exists( 'Avada' ) && class_exists( 'WooCommerce' ) ) {
					global $avada_woocommerce;

					$js_folder_suffix = FUSION_BUILDER_DEV_MODE ? '/assets/js' : '/assets/min/js';
					$js_folder_url    = Avada::$template_dir_url . $js_folder_suffix;
					$js_folder_path   = Avada::$template_dir_path . $js_folder_suffix;
					$version          = Avada::get_theme_version();

					if ( '1' === $this->args['enable_quick_view'] ) {
						$avada_woocommerce->quick_view_init();

						Fusion_Dynamic_JS::enqueue_script(
							'awb-woo-quick-view',
							$js_folder_url . '/general/awb-woo-quick-view.js',
							$js_folder_path . '/general/awb-woo-quick-view.js',
							[ 'jquery', 'fusion-flexslider' ],
							$version,
							true
						);

						Fusion_Dynamic_JS::localize_script(
							'awb-woo-quick-view',
							'avadaWooCommerceVars',
							$avada_woocommerce::get_avada_wc_vars()
						);
					}

					if ( 'yes' === $this->args['show_variations'] ) {
						Fusion_Dynamic_JS::enqueue_script(
							'avada-woo-products',
							$js_folder_url . '/general/avada-woo-products.js',
							$js_folder_path . '/general/avada-woo-products.js',
							[ 'jquery', 'fusion-flexslider' ],
							$version,
							true
						);

						Fusion_Dynamic_JS::localize_script(
							'avada-woo-products',
							'avadaWooCommerceVars',
							$avada_woocommerce::get_avada_wc_vars()
						);
					}
				}
			}

			/**
			 * Fires after post cards rendered.
			 *
			 * @access public
			 * @param int $element_counter The number of element.
			 * @since 3.11
			 */
			public function render_variation_js( $element_counter ) {
				if ( function_exists( 'wc_enqueue_js' ) && 'yes' === $this->args['show_variations'] ) {
					$this->enqueue_wc_variation_js( '.fusion-post-cards-' . ( $element_counter - 1 ) );
				}
			}

			/**
			 * Enqueue WC Variation js
			 *
			 * @access protected
			 * @param string $selector The selector.
			 * @since 3.11
			 */
			protected function enqueue_wc_variation_js( $selector ) {
				wc_enqueue_js(
					"
					if ( ! jQuery( '" . $selector . "' ).closest( '.product' ).length ) {
						jQuery( '" . $selector . "' ).parent().addClass( 'product' );
					}

					jQuery( 'body' ).on( 'change', '" . $selector . " .variation_id', function( e ) {
						if ( '' !== jQuery( this ).val() ) {
							jQuery( this ).parent().find( '.add_to_cart_button' ).attr( 'data-product_id', jQuery(this).val() );
						}
						if ( jQuery.trim( jQuery( e.currentTarget ).closest( 'form' ).find( '.avada-variation' ).text() ).length ) {
							jQuery( e.currentTarget ).closest( 'form' ).find( '.avada-variation' ).closest( 'tr' ).removeClass( 'awb-hide-element' );
						}
					} ).on( 'hide_variation', '.variations_form', function( e ) {
						jQuery( e.currentTarget ).find( '.add_to_cart_button' ).removeClass( 'wc-variation-is-unavailable' ).addClass( 'disabled wc-variation-selection-needed' );
						jQuery( e.currentTarget ).find( '.add_to_cart_button' ).attr( 'data-product_id', jQuery( e.currentTarget ).find( '[name=\"product_id\"]' ).val() );
						jQuery( e.currentTarget ).find( '.avada-variation' ).closest( 'tr' ).addClass( 'awb-hide-element' );
					} ).on( 'show_variation', '.variations_form', function( e ) {
						jQuery( e.currentTarget ).find( '.add_to_cart_button' ).removeClass( 'disabled wc-variation-selection-needed wc-variation-is-unavailable' );
						
						// To avoid change of product URL on variatoon selection.
						var productLink = jQuery( e.currentTarget ).closest( '.product' ).find( '.woocommerce-product-gallery__image > a' );
						if ( productLink.length ) {
							productLink.attr( 'href', productLink.attr( 'data-o_href' ) );
						}
					} );
					jQuery( 'body' ).on( 'click', '" . $selector . " .add_to_cart_button', function( e ) {
						var \$thisbutton = jQuery( this );
						if ( \$thisbutton.is('.disabled') ) {
							e.preventDefault();

							if ( \$thisbutton.is('.wc-variation-is-unavailable') ) {
								window.alert( wc_add_to_cart_variation_params.i18n_unavailable_text );
							} else if ( \$thisbutton.is('.wc-variation-selection-needed') ) {
								window.alert( wc_add_to_cart_variation_params.i18n_make_a_selection_text );
							}

							return true;
						} else {

							// Added wrapper.
							if ( ! \$thisbutton.find( 'span' ).length ) {
								\$thisbutton.wrapInner( '<span></span>' );
							}
							\$thisbutton.removeClass( 'added' ).addClass( 'loading' );

						}
						
					} );
				"
				);
			}

			/**
			 * Set default icons for text links
			 *
			 * @access public
			 * @since 3.3
			 */
			public function set_icon_defaults() {
				if ( 'custom' !== $this->args['button_style'] ) {
					$this->args['icon_position'] = 'left';
					$this->args['button_icon']   = 'fa-shopping-cart fas';
				}

				if ( 'custom' !== $this->args['product_link_style'] ) {
					$this->args['icon_details_position'] = 'left';
					$this->args['button_details_icon']   = 'fa-list-ul fas';
				}
			}

			/**
			 * Generates the post card cart content
			 *
			 * @access public
			 * @since 3.3
			 * @return string HTML output.
			 */
			public function get_cart_content() {
				global $product, $avada_woocommerce;
				$this->set_icon_defaults();
				if ( ! empty( $this->args['button_icon'] ) ) {
					add_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'add_icon_placeholder' ], 20 );
				}

				ob_start();

				if ( $product->is_type( 'simple' ) ) {
					$this->add_quantity_wrapper();
				}

				if ( 'yes' === $this->args['show_variations'] && $product->is_type( 'variable' ) ) {
					remove_action( 'woocommerce_reset_variations_link', [ $avada_woocommerce, 'add_single_variation_markup' ] );
					remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
					if ( ! $this->live_request ) {
						add_action( 'woocommerce_single_variation', [ $this, 'add_quantity_wrapper' ], 15 );
						add_action( 'woocommerce_single_variation', [ $this, 'add_cart_buttons_wrapper' ], 20 );
					}

					if ( '' !== $this->args['clear_content'] ) {
						add_filter( 'woocommerce_reset_variations_link', [ $this, 'add_variation_clear_text' ], 10 );
					}

					woocommerce_variable_add_to_cart();
				} else {
					$this->add_cart_buttons_wrapper();
				}

				$html = ob_get_clean();
				if ( ! empty( $this->args['button_icon'] ) ) {
					remove_filter( 'woocommerce_product_add_to_cart_text', [ $this, 'add_icon_placeholder' ], 20 );
					$html = str_replace( '@|@', '<i ' . FusionBuilder::attributes( 'post-card-cart-icon' ) . '></i>', $html );
				}

				// Restore hook and filter for variations.
				if ( 'yes' === $this->args['show_variations'] && $product->is_type( 'variable' ) ) {
					add_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
					remove_action( 'woocommerce_single_variation', [ $this, 'add_quantity_wrapper' ], 15 );
					remove_action( 'woocommerce_single_variation', [ $this, 'add_cart_buttons_wrapper' ], 20 );

					if ( '' !== $this->args['clear_content'] ) {
						remove_filter( 'woocommerce_reset_variations_link', [ $this, 'add_variation_clear_text' ], 10 );
					}

					add_action( 'woocommerce_reset_variations_link', [ $avada_woocommerce, 'add_single_variation_markup' ] );
				}

				return $html;
			}

			/**
			 * Generates quantity wrapper.
			 *
			 * @access public
			 * @since 3.8
			 * @return void
			 */
			public function add_quantity_wrapper() {
				global $product;

				$show_quantity = 'yes' === $this->args['show_quantity_input'] && $product->is_purchasable();
				?>
					<?php if ( apply_filters( 'fusion_cart_show_quantity', $show_quantity, $this->args ) ) { ?>
						<div class="awb-post-card-cart-cart-wrapper">
							<div class="fusion-post-card-cart-quantity"><?php echo $this->get_quantity(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php } ?>
				<?php
			}

			/**
			 * Generates the cart buttons wrapper.
			 *
			 * @access public
			 * @since 3.8
			 * @return void
			 */
			public function add_cart_buttons_wrapper() {
				global $product;

				if ( $this->has_buttons_wrapper() ) {
					?>
					<div class="fusion-post-card-cart-button-wrapper">
				<?php } ?>
				<?php
				if ( 'yes' === $this->args['show_add_to_cart_button'] ) {
					?>
					<?php echo $this->get_add_to_cart(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
				}
				?>

				<?php if ( 'yes' === $this->args['show_product_link_button'] ) { ?>
					<?php echo $this->get_product_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php } ?>

				<?php if ( $this->has_buttons_wrapper() ) { ?>
					</div>
					<?php
				}

				$show_quantity = 'yes' === $this->args['show_quantity_input'] && $product->is_purchasable();
				if ( ( ( 'yes' === $this->args['show_variations'] && $product->is_type( 'variable' ) ) || $product->is_type( 'simple' ) ) && apply_filters( 'fusion_cart_show_quantity', $show_quantity, $this->args ) ) {
					?>
					</div>
					<?php
				}
			}

			/**
			 * Add variation clear text.
			 *
			 * @param string $text The text.
			 * @since 3.8
			 * @return string HTML output.
			 */
			public function add_variation_clear_text( $text ) {
				if ( 'text' === $this->args['clear_content'] ) {
					return '<a class="reset_variations" href="#">' . $this->args['clear_text'] . '</a>';
				}

				if ( 'icon' === $this->args['clear_content'] ) {
					$icon_class = fusion_font_awesome_name_handler( $this->args['clear_icon'] );
					return '<a class="reset_variations" href="#"><i aria-hidden="true" class="' . $icon_class . '"></i></a>';
				}
				return $text;
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public function icon_attr() {

				$attr = [
					'class'       => fusion_font_awesome_name_handler( $this->args['button_icon'] ),
					'aria-hidden' => 'true',
				];

				$attr['class'] .= ' button-icon-' . $this->args['icon_position'];
				return $attr;
			}

			/**
			 * Builds the attributes array.
			 *
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public function icon_details_attr() {

				$attr = [
					'class'       => fusion_font_awesome_name_handler( $this->args['button_details_icon'] ),
					'aria-hidden' => 'true',
				];

				$attr['class'] .= ' button-icon-' . $this->args['icon_details_position'];
				return $attr;
			}

			/**
			 * Add an icon to the button text.
			 *
			 * @access public
			 * @since 3.3
			 * @param string $text Button text.
			 * @return string
			 */
			public function add_icon_placeholder( $text = '' ) {
				if ( 'left' === $this->args['icon_position'] ) {
					return '@|@' . $text;
				}
				return $text . '@|@';
			}

			/**
			 * Generates the quantity input
			 *
			 * @access public
			 * @since 3.3
			 */
			public function get_quantity() {
				global $product;
				woocommerce_quantity_input(
					[
						'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
						'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security
					]
				);
			}

			/**
			 * Generates the 'Add to cart' button
			 *
			 * @access public
			 * @since 3.3
			 * @return string
			 */
			public function get_add_to_cart() {
				if ( class_exists( 'Avada' ) ) {
					global $product, $avada_woocommerce;
					$button_class  = empty( $this->args['button_size'] ) ? ' fusion-button-default-size' : '';
					$button_class .= 'custom' === $this->args['button_style'] ? ' button-default' : '';
					$button_class .= $product->is_type( 'simple' ) ? ' add_to_cart_button ajax_add_to_cart' : '';

					ob_start();
					if ( 'yes' === $this->args['show_variations'] && $product->is_type( 'variable' ) ) {
						$this->template_loop_variation_add_to_cart();
					} else {
						$avada_woocommerce->template_loop_add_to_cart( [ 'class' => 'fusion-post-card-cart-add-to-cart' . $button_class ] );
					}
					return ob_get_clean();
				}

				return '';
			}

			/**
			 * Generates the Details/Quick view button
			 *
			 * @access public
			 * @since 3.2
			 * @return string
			 */
			public function get_product_link() {
				global $product;
				ob_start();
				$has_quick_view     = '1' === $this->args['enable_quick_view'] ? ' fusion-has-quick-view' : '';
				$button_size_class  = empty( $this->args['button_details_size'] ) ? ' fusion-button-default-size' : '';
				$button_size_class .= 'custom' === $this->args['product_link_style'] ? ' button-default' : '';
				$add_styles         = (bool) ( ( ! $product->is_purchasable() || ! $product->is_in_stock() ) && ! $product->is_type( 'external' ) );
				$icon               = ! empty( $this->args['button_details_icon'] ) ? '<i ' . FusionBuilder::attributes( 'post-card-cart-details-icon' ) . '></i>' : '';
				?>

				<a href="<?php echo esc_url_raw( get_permalink() ); ?>" class="fusion-post-card-cart-product-link show_details_button<?php echo esc_attr( $has_quick_view . $button_size_class ); ?>"<?php echo ( $add_styles ) ? ' style="float:none;max-width:none;text-align:center;"' : ''; ?>>
					<?php echo 'left' === $this->args['icon_details_position'] ? $icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'Details', 'Avada' ); ?>
					<?php echo 'right' === $this->args['icon_details_position'] ? $icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>

				<?php
				if ( '1' === $this->args['enable_quick_view'] ) :
					$product_id = $product->get_ID();
					if ( ! empty( $product_id ) ) {
						$image_info = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' );
					}
					$image_height = isset( $image_info['2'] ) ? $image_info['2'] : 0;
					$image_width  = isset( $image_info['1'] ) ? $image_info['1'] : 0;
					?>
					<a href="#fusion-quick-view" class="fusion-post-card-cart-product-link fusion-quick-view <?php echo esc_attr( $button_size_class ); ?>" data-image-height="<?php echo $image_height; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-image-width="<?php echo $image_width; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"<?php echo ( $add_styles ) ? ' style="float:none;max-width:none;text-align:center;"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php echo 'left' === $this->args['icon_details_position'] ? $icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php esc_html_e( 'Quick View', 'Avada' ); ?>
						<?php echo 'right' === $this->args['icon_details_position'] ? $icon : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<?php
				endif;
				$html = ob_get_clean();
				return $html;
			}

			/**
			 * Generates loop add to cart button for variation.
			 *
			 * @access public
			 * @since 3.8
			 * @return void
			 */
			public function template_loop_variation_add_to_cart() {
				global $product;

				$button_class  = 'fusion-post-card-cart-add-to-cart add_to_cart_button ajax_variation_add_to_cart disabled wc-variation-selection-needed';
				$button_class .= 'yes' !== get_option( 'woocommerce_enable_ajax_add_to_cart' ) ? ' disabled_ajax_add_to_cart' : '';
				$button_class .= empty( $this->args['button_size'] ) ? ' fusion-button-default-size' : '';
				$button_class .= 'custom' === $this->args['button_style'] ? ' button-default' : '';

				$add_to_cart_variation = sprintf( '<a href="#" class="%s" data-product_id="%d">%s</a>', $button_class, absint( $product->get_id() ), $this->add_icon_placeholder( $product->single_add_to_cart_text() ) );

				$replacement_icon = ( ! empty( $this->args['button_icon'] ) ) ? '<i ' . FusionBuilder::attributes( 'post-card-cart-icon' ) . '></i>' : '';
				echo str_replace( '@|@', $replacement_icon, $add_to_cart_variation ); //phpcs:ignore WordPress.Security.EscapeOutput

				printf( '<input type="hidden" name="add-to-cart" value="%d" />', absint( $product->get_id() ) );
				printf( '<input type="hidden" name="product_id" value="%d" />', absint( $product->get_id() ) );
				printf( '<input type="hidden" name="variation_id" class="variation_id" value="0" />' );
			}

			/**
			 * Used to set any other variables for use on front-end editor template.
			 *
			 * @static
			 * @access public
			 * @since 2.0.0
			 * @return array
			 */
			public static function get_element_extras() {
				$fusion_settings = awb_get_fusion_settings();
				return [
					'add_to_cart_text'       => esc_attr__( 'Add to cart', 'fusion-builder' ),
					'quick_view_text'        => esc_attr__( 'Quick View', 'Avada' ),
					'details_text'           => esc_attr__( 'Details', 'Avada' ),
					'woocommerce_variations' => $fusion_settings->get( 'woocommerce_variations' ),
				];
			}

			/**
			 * Get the styles.
			 *
			 * @access public
			 * @since 3.0
			 * @return string
			 */
			public function get_style_vars() {
				$fusion_settings = awb_get_fusion_settings();

				$custom_vars      = [];
				$css_vars_options = [
					'margin_top'    => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_right'  => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_bottom' => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
					'margin_left'   => [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ],
				];

				if ( 'floated' === $this->args['cart_layout'] ) {
					$custom_vars['justify-content'] = $this->args['justify'];
					$custom_vars['align-items']     = 'center';
				} else {
					$custom_vars['flex-direction'] = 'column';
					$custom_vars['align-items']    = $this->args['align'];
				}

				if ( 'yes' === $this->args['show_add_to_cart_button'] ) {
					$css_vars_options['button_margin_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_margin_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_margin_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_margin_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				if ( 'yes' === $this->args['show_product_link_button'] ) {
					$css_vars_options['button_details_margin_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_margin_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_margin_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_margin_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				// Button wrapper if both buttons are used.
				if ( $this->has_buttons_wrapper() ) {
					if ( 'floated' === $this->args['buttons_layout'] ) {
						$custom_vars['button-align-items'] = 'center';
						if ( 'stacked' === $this->args['cart_layout'] ) {
							$custom_vars['button-justify-content'] = $this->args['buttons_justify'];
						}
					} elseif ( 'stacked' === $this->args['buttons_layout'] ) {
						$custom_vars['button-flex-direction'] = 'column';
						$custom_vars['button-align-items']    = $this->args['buttons_alignment'];
					}

					// Button wrapper expand full width.
					if ( 'yes' === $this->args['buttons_stretch'] ) {
						$custom_vars['button-a-justify-content'] = 'center';

						// Stacked buttons next to quantity.
						if ( 'floated' === $this->args['cart_layout'] ) {
							if ( 'stacked' === $this->args['buttons_layout'] ) {

								$custom_vars['button-flex']        = '1';
								$custom_vars['button-align-items'] = 'stretch';
							} else {
								$custom_vars['button-flex']   = '1';
								$custom_vars['button-a-flex'] = '1';
							}
						} else {
							if ( 'stacked' === $this->args['buttons_layout'] ) {
								$custom_vars['button-align-items'] = 'stretch';
							} else {
								$custom_vars['button-a-flex'] = '1';
							}
						}
					}
				}

				if ( 'custom' === $this->args['quantity_style'] ) {
					$css_vars_options['quantity_margin_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_margin_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_margin_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_margin_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					// Quantity height.
					$height = '36px';
					if ( ! $this->is_default( 'quantity_height' ) ) {
						$custom_vars['quantity-height'] = fusion_library()->sanitize->get_value_with_unit( $this->args['quantity_height'] );
					}

					// Quantity width.
					$width = '36px';
					if ( ! $this->is_default( 'quantity_width' ) ) {
						$width = fusion_library()->sanitize->get_value_with_unit( $this->args['quantity_width'] );

						if ( false !== strpos( $width, '%' ) ) {
							$custom_vars['quantity-width'] = 'calc( 100% - var(--awb-quantity-height) - var(--awb-quantity-height) )';
						} else {
							$custom_vars['quantity-width'] = $width;
						}
					}

					// Quantity wrapper.
					if ( ! $this->is_default( 'quantity_width' ) || ! $this->is_default( 'quantity_height' ) ) {
						$custom_vars['quantity-wrapper-width'] = 'calc( ' . $width . ' + var(--awb-quantity-height) + var(--awb-quantity-height) )';
					}

					$css_vars_options['quantity_radius_top_left']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_radius_bottom_left']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_radius_bottom_right'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_radius_top_right']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_font_size']           = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_border_sizes_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_border_sizes_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_border_sizes_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['quantity_border_sizes_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                               = 'quantity_color';
					$css_vars_options[]                               = 'quantity_background';
					$css_vars_options[]                               = 'quantity_border_color';
					$css_vars_options['qbutton_border_sizes_top']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['qbutton_border_sizes_right']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['qbutton_border_sizes_bottom']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['qbutton_border_sizes_left']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                               = 'qbutton_color';
					$css_vars_options[]                               = 'qbutton_background';
					$css_vars_options[]                               = 'qbutton_border_color';
					$css_vars_options[]                               = 'qbutton_color_hover';
					$css_vars_options[]                               = 'qbutton_background_hover';
					$css_vars_options[]                               = 'qbutton_border_color_hover';
				}

				if ( 'custom' === $this->args['button_style'] ) {
					// Button size.
					if ( ! $this->is_default( 'button_size' ) ) {

						$button_size_map = [
							'small'  => [
								'padding'     => '9px 20px',
								'line_height' => '14px',
								'font_size'   => '12px',
							],
							'medium' => [
								'padding'     => '11px 23px',
								'line_height' => '16px',
								'font_size'   => '13px',
							],
							'large'  => [
								'padding'     => '13px 29px',
								'line_height' => '17px',
								'font_size'   => '14px',
							],
							'xlarge' => [
								'padding'     => '17px 40px',
								'line_height' => '21px',
								'font_size'   => '18px',
							],
						];

						if ( isset( $button_size_map[ $this->args['button_size'] ] ) ) {
							$button_dimensions                 = $button_size_map[ $this->args['button_size'] ];
							$custom_vars['button-padding']     = $button_dimensions['padding'];
							$custom_vars['button-line-height'] = $button_dimensions['line_height'];
							$custom_vars['button-font-size']   = $button_dimensions['font_size'];
						}
					}

					$css_vars_options['button_border_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_border_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_border_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_border_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                       = 'button_color';
					$css_vars_options[]                       = 'button_border_color';
					$css_vars_options[]                       = 'button_color_hover';
					$css_vars_options[]                       = 'button_border_color_hover';

					// Button gradient.
					if ( isset( $this->args['button_gradient_top'] ) && '' !== $this->args['button_gradient_top'] ) {
						$css_vars_options[] = 'button_gradient_top';
					}
					if ( isset( $this->args['button_gradient_bottom'] ) && '' !== $this->args['button_gradient_bottom'] ) {
						$css_vars_options[] = 'button_gradient_bottom';
					} elseif ( isset( $this->args['button_gradient_top'] ) && '' !== $this->args['button_gradient_top'] ) {
						$custom_vars['button-gradient-bottom'] = $this->args['button_gradient_top'];
					}

					if ( isset( $this->args['button_gradient_top_hover'] ) && '' !== $this->args['button_gradient_top_hover'] ) {
						$css_vars_options[] = 'button_gradient_top_hover';
					}
					if ( isset( $this->args['button_gradient_bottom_hover'] ) && '' !== $this->args['button_gradient_bottom_hover'] ) {
						$css_vars_options[] = 'button_gradient_bottom_hover';
					} elseif ( isset( $this->args['button_gradient_top_hover'] ) && '' !== $this->args['button_gradient_top_hover'] ) {
						$custom_vars['button-gradient-bottom-hover'] = $this->args['button_gradient_top_hover'];
					}
				} else {
					$css_vars_options[]                 = 'link_color';
					$css_vars_options[]                 = 'link_hover_color';
					$css_vars_options['link_font_size'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				if ( 'custom' === $this->args['product_link_style'] ) {
					// Button size.
					if ( ! $this->is_default( 'button_details_size' ) ) {

						$button_size_map = [
							'small'  => [
								'padding'     => '9px 20px',
								'line_height' => '14px',
								'font_size'   => '12px',
							],
							'medium' => [
								'padding'     => '11px 23px',
								'line_height' => '16px',
								'font_size'   => '13px',
							],
							'large'  => [
								'padding'     => '13px 29px',
								'line_height' => '17px',
								'font_size'   => '14px',
							],
							'xlarge' => [
								'padding'     => '17px 40px',
								'line_height' => '21px',
								'font_size'   => '18px',
							],
						];

						if ( isset( $button_size_map[ $this->args['button_details_size'] ] ) ) {
							$button_dimensions                         = $button_size_map[ $this->args['button_details_size'] ];
							$custom_vars['button-details-padding']     = $button_dimensions['padding'];
							$custom_vars['button-details-line-height'] = $button_dimensions['line_height'];
							$custom_vars['button-details-font-size']   = $button_dimensions['font_size'];
						}
					}

					$css_vars_options['button_details_border_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_border_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_border_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_details_border_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                               = 'button_details_color';
					$css_vars_options[]                               = 'button_details_border_color';

					if ( isset( $this->args['button_details_gradient_top'] ) && '' !== $this->args['button_details_gradient_top'] ) {
						$css_vars_options[] = 'button_details_gradient_top';
					}
					if ( isset( $this->args['button_details_gradient_bottom'] ) && '' !== $this->args['button_details_gradient_bottom'] ) {
						$css_vars_options[] = 'button_details_gradient_bottom';
					} elseif ( isset( $this->args['button_details_gradient_top'] ) && '' !== $this->args['button_details_gradient_top'] ) {
						$custom_vars['button-details-gradient-bottom'] = $this->args['button_details_gradient_top'];
					}

					if ( isset( $this->args['button_details_gradient_top_hover'] ) && '' !== $this->args['button_details_gradient_top_hover'] ) {
						$css_vars_options[] = 'button_details_gradient_top_hover';
					}
					if ( isset( $this->args['button_details_gradient_bottom_hover'] ) && '' !== $this->args['button_details_gradient_bottom_hover'] ) {
						$css_vars_options[] = 'button_details_gradient_bottom_hover';
					} elseif ( isset( $this->args['button_details_gradient_top_hover'] ) && '' !== $this->args['button_details_gradient_top_hover'] ) {
						$custom_vars['button-details-gradient-bottom-hover'] = $this->args['button_details_gradient_top_hover'];
					}

					$css_vars_options[] = 'button_details_color_hover';
					$css_vars_options[] = 'button_details_border_color_hover';
				} else {
					$css_vars_options[]                         = 'product_link_color';
					$css_vars_options[]                         = 'product_link_hover_color';
					$css_vars_options['product_link_font_size'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				if ( 'floated' === $this->args['variation_layout'] && ! $this->is_default( 'variation_label_area_width' ) ) {
					$css_vars_options['variation_label_area_width'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				// Variation Label Typo.
				$css_vars_options[]                       = 'label_color';
				$css_vars_options['label_font_size']      = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				$css_vars_options[]                       = 'label_line_height';
				$css_vars_options['label_letter_spacing'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				$css_vars_options[]                       = 'label_text_transform';

				// Font family and weight.
				$text_styles = Fusion_Builder_Element_Helper::get_font_styling( $this->args, 'label_typography', 'array' );
				foreach ( $text_styles as $rule => $value ) {
					$custom_vars[ 'label-typography-' . $rule ] = $value;
				}

				// Select variation type styling.
				if ( ! $this->is_default( 'select_style' ) ) {
					$css_vars_options['select_height']         = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['select_font_size']      = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                        = 'select_line_height';
					$css_vars_options['select_letter_spacing'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                        = 'select_text_transform';

					// Font family and weight.
					$font_styles = Fusion_Builder_Element_Helper::get_font_styling( $this->args, 'select_typography', 'array' );
					foreach ( $font_styles as $rule => $value ) {
						$custom_vars[ 'select-typography-' . $rule ] = $value;
					}

					$css_vars_options[]                             = 'select_color';
					$css_vars_options[]                             = 'select_background';
					$css_vars_options[]                             = 'select_border_color';
					$css_vars_options['select_border_sizes_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['select_border_sizes_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['select_border_sizes_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['select_border_sizes_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					// Border separator with arrow.
					if ( ! $this->is_default( 'select_border_color' ) && ! $this->is_default( 'select_border_sizes_right' ) && ! $this->is_default( 'select_border_sizes_left' ) ) {
						$custom_vars['arrow-border-left'] = fusion_library()->sanitize->get_value_with_unit( $this->args['select_border_sizes_left'] ) . ' solid ' . $this->args['select_border_color'];
					}

					$css_vars_options['border_radius_top_left']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['border_radius_top_right']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['border_radius_bottom_right'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['border_radius_bottom_left']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
				}

				// Swatch styling if enabled.
				if ( ! $this->is_default( 'swatch_style' ) && $fusion_settings->get( 'woocommerce_variations' ) ) {
					$css_vars_options['swatch_margin_top']          = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_margin_right']        = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_margin_bottom']       = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_margin_left']         = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[]                             = 'swatch_background_color';
					$css_vars_options[]                             = 'swatch_border_color';
					$css_vars_options[]                             = 'swatch_background_color_active';
					$css_vars_options['swatch_border_sizes_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_border_sizes_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_border_sizes_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['swatch_border_sizes_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					if ( ! $this->is_default( 'swatch_border_color_active' ) ) {
						$css_vars_options[]                       = 'swatch_border_color_active';
						$custom_vars['swatch_border_color_hover'] = Fusion_Color::new_color( $this->args['swatch_border_color_active'] )->get_new( 'alpha', '0.5' )->to_css_var_or_rgba();
					}

					$css_vars_options['color_swatch_height']         = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_padding_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_padding_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_padding_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_padding_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_padding_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					// Color swatch.
					if ( ! $this->is_default( 'color_swatch_width' ) ) {
						$width                             = 'auto' === $this->args['color_swatch_width'] ? 'auto' : fusion_library()->sanitize->get_value_with_unit( $this->args['color_swatch_width'] );
						$custom_vars['color_swatch_width'] = $width;
					}

					$css_vars_options['color_swatch_border_radius_top_left']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_border_radius_top_right']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_border_radius_bottom_right'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['color_swatch_border_radius_bottom_left']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					// Image swatch.
					$css_vars_options['image_swatch_height']         = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_padding_top']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_padding_right']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_padding_bottom'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_padding_left']   = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					if ( ! $this->is_default( 'image_swatch_width' ) ) {
						$width                                   = 'auto' === $this->args['image_swatch_width'] ? 'auto' : fusion_library()->sanitize->get_value_with_unit( $this->args['image_swatch_width'] );
						$custom_vars['color_swatch_width']       = $width;
						$custom_vars['color_swatch_image_width'] = 'auto' !== $this->args['image_swatch_width'] ? '100%' : 'auto';
					}

					$css_vars_options['image_swatch_border_radius_top_left']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_border_radius_top_right']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_border_radius_bottom_right'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['image_swatch_border_radius_bottom_left']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];

					// Button swatch.
					$css_vars_options['button_swatch_height']                     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_padding_top']                = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_padding_right']              = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_padding_bottom']             = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_padding_left']               = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_border_radius_top_left']     = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_border_radius_top_right']    = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_border_radius_bottom_right'] = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_border_radius_bottom_left']  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options['button_swatch_font_size']                  = [ 'callback' => [ 'Fusion_Sanitize', 'get_value_with_unit' ] ];
					$css_vars_options[] = 'button_swatch_color';
					$css_vars_options[] = 'button_swatch_color_active';

					if ( ! $this->is_default( 'button_swatch_width' ) ) {
						$width                              = 'auto' === $this->args['button_swatch_width'] ? 'auto' : fusion_library()->sanitize->get_value_with_unit( $this->args['button_swatch_width'] );
						$custom_vars['button_swatch_width'] = $width;
					}
				}

				return $this->get_css_vars_for_options( $css_vars_options ) . $this->get_custom_css_vars( $custom_vars );
			}

			/**
			 * Do we have wrapper for buttons.
			 *
			 * @access public
			 * @since 3.3
			 * @return boolean
			 */
			public function has_buttons_wrapper() {
				return ( 'yes' === $this->args['show_product_link_button'] || 'yes' === $this->args['show_add_to_cart_button'] )
					&& ! ( 'floated' === $this->args['cart_layout'] && 'floated' === $this->args['buttons_layout'] && 'no' === $this->args['buttons_stretch'] );
			}

			/**
			 * Check if current product is in cart.
			 *
			 * @access public
			 * @since 3.3
			 * @return void
			 */
			public function in_cart() {
				$this->args['in_cart'] = fusion_library()->woocommerce->is_product_in_cart( get_the_ID() );
			}

			/**
			 * Builds the array of attributes.
			 *
			 * @access public
			 * @since 3.3
			 * @return array
			 */
			public function attr() {
				$attr = fusion_builder_visibility_atts(
					$this->args['hide_on_mobile'],
					[
						'class' => 'fusion-woo-cart fusion-post-card-cart',
						'style' => $this->get_style_vars(),
					]
				);

				if ( $this->args['animation_type'] ) {
					$attr = Fusion_Builder_Animation_Helper::add_animation_attributes( $this->args, $attr );
				}

				if ( $this->args['in_cart'] ) {
					$attr['class'] .= ' fusion-item-in-cart';
				}

				if ( $this->args['class'] ) {
					$attr['class'] .= ' ' . $this->args['class'];
				}

				$attr['class'] .= ' awb-variation-layout-' . $this->args['variation_layout'];
				$attr['class'] .= ' awb-variation-clear-' . $this->args['variation_clear'];
				$attr['class'] .= ' awb-label-' . $this->args['show_label'];

				if ( '' !== $this->args['variation_text_align'] ) {
					$attr['class'] .= ' awb-variation-text-align-' . $this->args['variation_text_align'];
				}

				$attr['class'] .= ' awb-add-to-cart-style-' . ( '' === $this->args['button_style'] ? 'link' : 'button' );
				$attr['class'] .= ' awb-product-style-' . ( '' === $this->args['product_link_style'] ? 'link' : 'button' );

				if ( $this->args['id'] ) {
					$attr['id'] = $this->args['id'];
				}

				return $attr;
			}

			/**
			 * Load base CSS.
			 *
			 * @access public
			 * @since 3.3
			 * @return void
			 */
			public function add_css_files() {
				if ( class_exists( 'Avada' ) && class_exists( 'WooCommerce', false ) ) {
					$version = Avada::get_theme_version();
					Fusion_Dynamic_CSS::enqueue_style( Avada::$template_dir_path . '/assets/css/dynamic/woocommerce/woo-quick-view.min.css', Avada::$template_dir_url . '/assets/css/dynamic/woocommerce/woo-quick-view.min.css' );

					Fusion_Media_Query_Scripts::$media_query_assets[] = [
						'avada-max-sh-cbp-woo-quick-view',
						get_template_directory_uri() . '/assets/css/media/max-sh-cbp-woo-quick-view.min.css',
						[],
						$version,
						Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-max-sh-cbp' ),
					];

					Fusion_Media_Query_Scripts::$media_query_assets[] = [
						'avada-min-sh-cbp-woo-quick-view',
						get_template_directory_uri() . '/assets/css/media/min-sh-cbp-woo-quick-view.min.css',
						[],
						$version,
						Fusion_Media_Query_Scripts::get_media_query_from_key( 'fusion-min-sh-cbp' ),
					];
				}
				FusionBuilder()->add_element_css( FUSION_BUILDER_PLUGIN_DIR . 'assets/css/shortcodes/post-card-cart.min.css' );
			}
		}
	}

	new FusionSC_PostCardCart();

}

/**
 * Map shortcode to Avada Builder.
 *
 * @since 3.3
 */
function fusion_element_post_card_cart() {
	$fusion_settings = awb_get_fusion_settings();

	fusion_builder_map(
		fusion_builder_frontend_data(
			'FusionSC_PostCardCart',
			[
				'name'         => esc_attr__( 'Post Card Cart', 'fusion-builder' ),
				'shortcode'    => 'fusion_post_card_cart',
				'icon'         => 'fusiona-post-cards-cart',
				'templates'    => [ 'post_cards' ],
				'component'    => true,
				'subparam_map' => [
					'variation_label_area_width'           => 'variation_label_width',
					'fusion_font_family_label_typography'  => 'label_fonts',
					'fusion_font_variant_label_typography' => 'label_fonts',
					'label_font_size'                      => 'label_fonts',
					'label_text_transform'                 => 'label_fonts',
					'label_line_height'                    => 'label_fonts',
					'label_letter_spacing'                 => 'label_fonts',
					'label_color'                          => 'label_fonts',
					'fusion_font_family_select_typography' => 'select_fonts',
					'fusion_font_variant_select_typography' => 'select_fonts',
					'select_font_size'                     => 'select_fonts',
					'select_text_transform'                => 'select_fonts',
					'select_line_height'                   => 'select_fonts',
					'select_letter_spacing'                => 'select_fonts',
					'select_color'                         => 'select_fonts',
				],
				'params'       => [
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Cart Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the layout of cart components. Floated will have components side by side. Stacked will have one component per row.', 'fusion-builder' ),
						'param_name'  => 'cart_layout',
						'default'     => 'stacked',
						'value'       => [
							'floated' => esc_attr__( 'Floated', 'fusion-builder' ),
							'stacked' => esc_attr__( 'Stacked', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Cart Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the content alignment.', 'fusion-builder' ),
						'param_name'  => 'justify',
						'default'     => 'flex-start',
						'grid_layout' => true,
						'back_icons'  => true,
						'icons'       => [
							'flex-start'    => '<span class="fusiona-horizontal-flex-start"></span>',
							'center'        => '<span class="fusiona-horizontal-flex-center"></span>',
							'flex-end'      => '<span class="fusiona-horizontal-flex-end"></span>',
							'space-between' => '<span class="fusiona-horizontal-space-between"></span>',
							'space-around'  => '<span class="fusiona-horizontal-space-around"></span>',
							'space-evenly'  => '<span class="fusiona-horizontal-space-evenly"></span>',
						],
						'value'       => [
							'flex-start'    => esc_html__( 'Flex Start', 'fusion-builder' ),
							'center'        => esc_html__( 'Center', 'fusion-builder' ),
							'flex-end'      => esc_html__( 'Flex End', 'fusion-builder' ),
							'space-between' => esc_html__( 'Space Between', 'fusion-builder' ),
							'space-around'  => esc_html__( 'Space Around', 'fusion-builder' ),
							'space-evenly'  => esc_html__( 'Space Evenly', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'cart_layout',
								'value'    => 'floated',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Cart Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the content alignment.', 'fusion-builder' ),
						'param_name'  => 'align',
						'default'     => 'flex-start',
						'grid_layout' => true,
						'back_icons'  => true,
						'icons'       => [
							'flex-start' => '<span class="fusiona-horizontal-flex-start"></span>',
							'center'     => '<span class="fusiona-horizontal-flex-center"></span>',
							'flex-end'   => '<span class="fusiona-horizontal-flex-end"></span>',
						],
						'value'       => [
							'flex-start' => esc_html__( 'Flex Start', 'fusion-builder' ),
							'center'     => esc_html__( 'Center', 'fusion-builder' ),
							'flex-end'   => esc_html__( 'Flex End', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'cart_layout',
								'value'    => 'floated',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Variations', 'fusion-builder' ),
						'description' => esc_attr__( 'Display the variations if product type is variable.', 'fusion-builder' ),
						'param_name'  => 'show_variations',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_cart',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Quantity', 'fusion-builder' ),
						'description' => esc_attr__( 'Display the quantity input.', 'fusion-builder' ),
						'param_name'  => 'show_quantity_input',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Add To Cart', 'fusion-builder' ),
						'description' => esc_attr__( 'Display the Add To Cart button.', 'fusion-builder' ),
						'param_name'  => 'show_add_to_cart_button',
						'default'     => 'yes',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Product Link', 'fusion-builder' ),
						'description' => esc_attr__( 'Display the Product Link .', 'fusion-builder' ),
						'param_name'  => 'show_product_link_button',
						'default'     => 'yes',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Product Quick View', 'fusion-builder' ),
						'description' => esc_attr__( 'Enable product quick view for products.', 'fusion-builder' ),
						'param_name'  => 'enable_quick_view',
						'value'       => [
							''    => esc_attr__( 'Default', 'fusion-builder' ),
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Buttons Span', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls if buttons spans the full width/remaining width of row.', 'fusion-builder' ),
						'param_name'  => 'buttons_stretch',
						'default'     => 'no',
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'or'          => true,
						'dependency'  => [
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Buttons Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the layout for buttons.  Floated will have them side by side.  Stacked will have one per row.', 'fusion-builder' ),
						'param_name'  => 'buttons_layout',
						'default'     => 'floated',
						'value'       => [
							'floated' => esc_attr__( 'Floated', 'fusion-builder' ),
							'stacked' => esc_attr__( 'Stacked', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Buttons Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the content alignment.', 'fusion-builder' ),
						'param_name'  => 'buttons_alignment',
						'default'     => 'flex-start',
						'grid_layout' => true,
						'back_icons'  => true,
						'icons'       => [
							'flex-start' => '<span class="fusiona-horizontal-flex-start"></span>',
							'center'     => '<span class="fusiona-horizontal-flex-center"></span>',
							'flex-end'   => '<span class="fusiona-horizontal-flex-end"></span>',
						],
						'value'       => [
							'flex-start' => esc_html__( 'Flex Start', 'fusion-builder' ),
							'center'     => esc_html__( 'Center', 'fusion-builder' ),
							'flex-end'   => esc_html__( 'Flex End', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'buttons_layout',
								'value'    => 'floated',
								'operator' => '!=',
							],
							[
								'element'  => 'buttons_stretch',
								'value'    => 'yes',
								'operator' => '!=',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Buttons Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the content alignment.', 'fusion-builder' ),
						'param_name'  => 'buttons_justify',
						'default'     => 'space-between',
						'grid_layout' => true,
						'back_icons'  => true,
						'icons'       => [
							'flex-start'    => '<span class="fusiona-horizontal-flex-start"></span>',
							'center'        => '<span class="fusiona-horizontal-flex-center"></span>',
							'flex-end'      => '<span class="fusiona-horizontal-flex-end"></span>',
							'space-between' => '<span class="fusiona-horizontal-space-between"></span>',
							'space-around'  => '<span class="fusiona-horizontal-space-around"></span>',
							'space-evenly'  => '<span class="fusiona-horizontal-space-evenly"></span>',
						],
						'value'       => [
							'flex-start'    => esc_html__( 'Flex Start', 'fusion-builder' ),
							'center'        => esc_html__( 'Center', 'fusion-builder' ),
							'flex-end'      => esc_html__( 'Flex End', 'fusion-builder' ),
							'space-between' => esc_html__( 'Space Between', 'fusion-builder' ),
							'space-around'  => esc_html__( 'Space Around', 'fusion-builder' ),
							'space-evenly'  => esc_html__( 'Space Evenly', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'buttons_layout',
								'value'    => 'floated',
								'operator' => '==',
							],
							[
								'element'  => 'cart_layout',
								'value'    => 'stacked',
								'operator' => '==',
							],
							[
								'element'  => 'buttons_stretch',
								'value'    => 'yes',
								'operator' => '!=',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'checkbox_button_set',
						'heading'     => esc_attr__( 'Element Visibility', 'fusion-builder' ),
						'param_name'  => 'hide_on_mobile',
						'value'       => fusion_builder_visibility_options( 'full' ),
						'default'     => fusion_builder_default_visibility( 'array' ),
						'description' => esc_attr__( 'Choose to show or hide the element on small, medium or large screens. You can choose more than one at a time.', 'fusion-builder' ),
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS Class', 'fusion-builder' ),
						'description' => esc_attr__( 'Add a class to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'class',
						'value'       => '',
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'CSS ID', 'fusion-builder' ),
						'description' => esc_attr__( 'Add an ID to the wrapping HTML element.', 'fusion-builder' ),
						'param_name'  => 'id',
						'value'       => '',
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'margin',
						'value'            => [
							'margin_top'    => '',
							'margin_right'  => '',
							'margin_bottom' => '',
							'margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Quantity Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select whether you want to custom style the quantity field.', 'fusion-builder' ),
						'param_name'  => 'quantity_style',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
						'default'     => '',
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Quantity Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'quantity_margin',
						'value'            => [
							'quantity_margin_top'    => '',
							'quantity_margin_right'  => '',
							'quantity_margin_bottom' => '',
							'quantity_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Quantity Input Dimensions', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'quantity_height_field',
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'value'            => [
							'quantity_width'  => '',
							'quantity_height' => '',
						],
						'dependency'       => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Quantity Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'quantity_border_radius',
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'value'            => [
							'quantity_radius_top_left'     => '',
							'quantity_radius_top_right'    => '',
							'quantity_radius_bottom_right' => '',
							'quantity_radius_bottom_left'  => '',
						],
						'dependency'       => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Quantity Input Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the select field. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'quantity_font_size',
						'value'       => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Quantity Input Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the select fields.', 'fusion-builder' ),
						'param_name'  => 'quantity_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'body_typography', 'color' ),
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Quantity Input Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the select fields.', 'fusion-builder' ),
						'param_name'  => 'quantity_background',
						'value'       => '',
						'default'     => 'rgba(255,255,255,0)',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Quantity Input Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the select fields.', 'fusion-builder' ),
						'param_name'  => 'quantity_border_sizes',
						'value'       => [
							'quantity_border_sizes_top'    => '',
							'quantity_border_sizes_right'  => '',
							'quantity_border_sizes_bottom' => '',
							'quantity_border_sizes_left'   => '',
						],
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Quantity Input Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the select fields.', 'fusion-builder' ),
						'param_name'  => 'quantity_border_color',
						'value'       => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'     => $fusion_settings->get( 'sep_color' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Quantity Button Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the select fields.', 'fusion-builder' ),
						'param_name'  => 'qbutton_border_sizes',
						'value'       => [
							'qbutton_border_sizes_top'    => '',
							'qbutton_border_sizes_right'  => '',
							'qbutton_border_sizes_bottom' => '',
							'qbutton_border_sizes_left'   => '',
						],
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Quantity Button Text Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the select fields.', 'fusion-builder' ),
						'param_name'    => 'qbutton_color',
						'value'         => '',
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'       => $fusion_settings->get( 'body_typography', 'color' ),
						'dependency'    => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'body_typography' ),
								'preview' => [
									'selector' => '.quantity input.minus, .quantity input.plus',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'qbutton_background', 'qbutton_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Quantity Button Background Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the background color of the select fields.', 'fusion-builder' ),
						'param_name'    => 'qbutton_background',
						'value'         => '',
						'default'       => $fusion_settings->get( 'qty_bg_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'qty_bg_hover_color' ),
								'preview' => [
									'selector' => '.quantity input.minus, .quantity input.plus',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'qbutton_color', 'qbutton_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Quantity Button Border Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the border color of the select fields.', 'fusion-builder' ),
						'param_name'    => 'qbutton_border_color',
						'value'         => '',
						'default'       => $fusion_settings->get( 'sep_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'show_quantity_input',
								'value'    => 'yes',
								'operator' => '==',
							],
							[
								'element'  => 'quantity_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'qty_bg_hover_color' ),
								'preview' => [
									'selector' => '.quantity input.minus, .quantity input.plus',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'qbutton_color', 'qbutton_background' ],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Add To Cart Link Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select whether you want to custom style the add to cart button.', 'fusion-builder' ),
						'param_name'  => 'button_style',
						'value'       => [
							''       => esc_attr__( 'Text Link', 'fusion-builder' ),
							'custom' => esc_attr__( 'Button', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Add To Cart Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'button_margin',
						'value'            => [
							'button_margin_top'    => '',
							'button_margin_right'  => '',
							'button_margin_bottom' => '',
							'button_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Button Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the button size.', 'fusion-builder' ),
						'param_name'  => 'button_size',
						'default'     => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'small'  => esc_attr__( 'Small', 'fusion-builder' ),
							'medium' => esc_attr__( 'Medium', 'fusion-builder' ),
							'large'  => esc_attr__( 'Large', 'fusion-builder' ),
							'xlarge' => esc_attr__( 'XLarge', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Button Border Size', 'fusion-builder' ),
						'param_name'       => 'button_border_width',
						'description'      => esc_attr__( 'Controls the border size. In pixels.', 'fusion-builder' ),
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'value'            => [
							'button_border_top'    => '',
							'button_border_right'  => '',
							'button_border_bottom' => '',
							'button_border_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Icon', 'fusion-builder' ),
						'param_name'  => 'button_icon',
						'value'       => '',
						'description' => esc_attr__( 'Click an icon to select, click again to deselect.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Icon Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the position of the icon on the button.', 'fusion-builder' ),
						'param_name'  => 'icon_position',
						'value'       => [
							'left'  => esc_attr__( 'Left', 'fusion-builder' ),
							'right' => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => 'left',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'button_icon',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Add To Cart Link Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the text link. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'link_font_size',
						'value'       => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '!=',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Link Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the link.', 'fusion-builder' ),
						'param_name'  => 'link_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'link_color' ),
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '!=',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'states'      => [
							'hover' => [
								'label'      => __( 'Hover', 'fusion-builder' ),
								'param_name' => 'link_hover_color',
								'default'    => $fusion_settings->get( 'link_hover_color' ),
								'preview'    => [
									'selector' => '.fusion-post-card-cart-add-to-cart',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Text Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_color',
						'value'         => '',
						'default'       => $fusion_settings->get( 'button_accent_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_accent_hover_color' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-add-to-cart',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_gradient_top', 'button_gradient_bottom', 'button_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Gradient Top Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_gradient_top',
						'value'         => '',
						'default'       => $fusion_settings->get( 'primary_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_gradient_top_color_hover' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-add-to-cart',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_color', 'button_gradient_bottom', 'button_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Gradient Bottom Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_gradient_bottom',
						'value'         => '',
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'       => $fusion_settings->get( 'button_gradient_bottom_color' ),
						'dependency'    => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_gradient_bottom_color_hover' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-add-to-cart',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_gradient_top', 'button_color', 'button_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Border Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the border color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_border_color',
						'value'         => '',
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'       => $fusion_settings->get( 'button_border_color' ),
						'dependency'    => [
							[
								'element'  => 'button_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_add_to_cart_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_border_hover_color' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-add-to-cart',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_gradient_top', 'button_gradient_bottom', 'button_color' ],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Product Link Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select whether you want to custom style the product link.', 'fusion-builder' ),
						'param_name'  => 'product_link_style',
						'value'       => [
							''       => esc_attr__( 'Text Link', 'fusion-builder' ),
							'custom' => esc_attr__( 'Button', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Product Link Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'button_details_margin',
						'value'            => [
							'button_details_margin_top'    => '',
							'button_details_margin_right'  => '',
							'button_details_margin_bottom' => '',
							'button_details_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Product Link Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the text link. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'product_link_font_size',
						'value'       => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '!=',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Product Link Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the link.', 'fusion-builder' ),
						'param_name'  => 'product_link_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'button_accent_color' ),
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '!=',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'states'      => [
							'hover' => [
								'label'      => __( 'Hover', 'fusion-builder' ),
								'param_name' => 'product_link_hover_color',
								'default'    => $fusion_settings->get( 'link_hover_color' ),
								'preview'    => [
									'selector' => '.fusion-post-card-cart-product-link',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Button Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the button size.', 'fusion-builder' ),
						'param_name'  => 'button_details_size',
						'default'     => '',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'small'  => esc_attr__( 'Small', 'fusion-builder' ),
							'medium' => esc_attr__( 'Medium', 'fusion-builder' ),
							'large'  => esc_attr__( 'Large', 'fusion-builder' ),
							'xlarge' => esc_attr__( 'XLarge', 'fusion-builder' ),
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'dependency'  => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Button Border Size', 'fusion-builder' ),
						'param_name'       => 'button_details_border_width',
						'description'      => esc_attr__( 'Controls the border size. In pixels.', 'fusion-builder' ),
						'group'            => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'value'            => [
							'button_details_border_top'    => '',
							'button_details_border_right'  => '',
							'button_details_border_bottom' => '',
							'button_details_border_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Icon', 'fusion-builder' ),
						'param_name'  => 'button_details_icon',
						'value'       => '',
						'description' => esc_attr__( 'Click an icon to select, click again to deselect.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Icon Position', 'fusion-builder' ),
						'description' => esc_attr__( 'Choose the position of the icon on the button.', 'fusion-builder' ),
						'param_name'  => 'icon_details_position',
						'value'       => [
							'left'  => esc_attr__( 'Left', 'fusion-builder' ),
							'right' => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'default'     => 'left',
						'group'       => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'button_icon',
								'value'    => '',
								'operator' => '!=',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Text Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_details_color',
						'value'         => '',
						'default'       => $fusion_settings->get( 'button_accent_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_accent_hover_color' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-product-link',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_details_gradient_top', 'button_details_gradient_bottom', 'button_details_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Gradient Top Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_details_gradient_top',
						'value'         => '',
						'default'       => $fusion_settings->get( 'button_gradient_top_color' ),
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'dependency'    => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_gradient_top_color_hover' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-product-link',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_details_color', 'button_details_gradient_bottom', 'button_details_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Gradient Bottom Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the text color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_details_gradient_bottom',
						'value'         => '',
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'       => $fusion_settings->get( 'button_gradient_bottom_color' ),
						'dependency'    => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_gradient_bottom_color_hover' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-product-link',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_details_color', 'button_details_gradient_top', 'button_details_border_color' ],
					],
					[
						'type'          => 'colorpickeralpha',
						'heading'       => esc_attr__( 'Button Border Color', 'fusion-builder' ),
						'description'   => esc_attr__( 'Controls the border color of the button.', 'fusion-builder' ),
						'param_name'    => 'button_details_border_color',
						'value'         => '',
						'group'         => esc_attr__( 'Cart', 'fusion-builder' ),
						'default'       => $fusion_settings->get( 'button_border_color' ),
						'dependency'    => [
							[
								'element'  => 'product_link_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_product_link_button',
								'value'    => 'yes',
								'operator' => '==',
							],
						],
						'callback'      => [
							'function' => 'fusion_style_block',
						],
						'states'        => [
							'hover' => [
								'label'   => __( 'Hover', 'fusion-builder' ),
								'default' => $fusion_settings->get( 'button_border_hover_color' ),
								'preview' => [
									'selector' => '.fusion-post-card-cart-product-link',
									'type'     => 'class',
									'toggle'   => 'hover',
								],
							],
						],
						'connect-state' => [ 'button_details_color', 'button_details_gradient_top', 'button_details_gradient_bottom' ],
					],
					[
						'heading'    => '',
						'content'    => __( '<i class="fusiona-info-circle"></i> Please enable <b>Show Variations</b> option on <b>General Tabs</b> in order to display variation options.', 'fusion-builder' ),
						'param_name' => 'variations_info',
						'type'       => 'info',
						'group'      => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency' => [
							[
								'element'  => 'show_variations',
								'value'    => 'yes',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Variation Layout', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the layout for the variations.  Floated will have the label and select side by side.  Stacked will have one per row.', 'fusion-builder' ),
						'param_name'  => 'variation_layout',
						'default'     => 'floated',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'       => [
							'floated' => esc_attr__( 'Floated', 'fusion-builder' ),
							'stacked' => esc_attr__( 'Stacked', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Show Label', 'fusion-builder' ),
						'description' => esc_attr__( 'Display the variation attribute label.', 'fusion-builder' ),
						'param_name'  => 'show_label',
						'default'     => 'yes',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'       => [
							'yes' => esc_attr__( 'Yes', 'fusion-builder' ),
							'no'  => esc_attr__( 'No', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Label Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the typography of the label text. Leave empty for the global font family.', 'fusion-builder' ),
						'param_name'       => 'label_fonts',
						'choices'          => [
							'font-family'    => 'label_typography',
							'font-size'      => 'label_font_size',
							'text-transform' => 'label_text_transform',
							'line-height'    => 'label_line_height',
							'letter-spacing' => 'label_letter_spacing',
							'color'          => 'label_color',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'font-size'      => '',
							'text-transform' => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'color'          => $fusion_settings->get( 'body_typography', 'color' ),
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
							[
								'element'  => 'show_label',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Label Width', 'fusion-builder' ),
						'description'      => esc_html__( 'Leave empty for automatic width.  Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'variation_label_width',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'variation_label_area_width' => '',
						],
						'dependency'       => [
							[
								'element'  => 'variation_layout',
								'value'    => 'floated',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
							[
								'element'  => 'show_label',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Text Alignment', 'fusion-builder' ),
						'description' => esc_attr__( 'Select the text alignment for the variation label and variation swatches.', 'fusion-builder' ),
						'param_name'  => 'variation_text_align',
						'default'     => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'       => [
							''       => esc_attr__( 'Text Flow', 'fusion-builder' ),
							'left'   => esc_attr__( 'Left', 'fusion-builder' ),
							'center' => esc_attr__( 'Center', 'fusion-builder' ),
							'right'  => esc_attr__( 'Right', 'fusion-builder' ),
						],
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],

					// Swatch Styles.
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Swatch Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select whether you want to custom style the Avada variation swatches.', 'fusion-builder' ),
						'param_name'  => 'swatch_style',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Swatch Item Margin', 'fusion-builder' ),
						'description'      => esc_attr__( 'In pixels or percentage, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'swatch_margin',
						'value'            => [
							'swatch_margin_top'    => '',
							'swatch_margin_right'  => '',
							'swatch_margin_bottom' => '',
							'swatch_margin_left'   => '',
						],
						'callback'         => [
							'function' => 'fusion_style_block',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Swatch Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the color, image and button swatch fields.', 'fusion-builder' ),
						'param_name'  => 'swatch_border_sizes',
						'value'       => [
							'swatch_border_sizes_top'    => '',
							'swatch_border_sizes_right'  => '',
							'swatch_border_sizes_bottom' => '',
							'swatch_border_sizes_left'   => '',
						],
						'callback'    => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
					],
					[
						'type'             => 'subgroup',
						'heading'          => esc_html__( 'Swatch Styling', 'fusion-builder' ),
						'description'      => esc_html__( 'Use filters to see specific type of content.', 'fusion-builder' ),
						'param_name'       => 'swatch_styling',
						'default'          => 'regular',
						'group'            => esc_html__( 'Variations', 'fusion-builder' ),
						'remove_from_atts' => true,
						'value'            => [
							'regular' => esc_html__( 'Regular', 'fusion-builder' ),
							'active'  => esc_html__( 'Active', 'fusion-builder' ),
						],
						'icons'            => [
							'regular' => '<span class="fusiona-regular-state" style="font-size:18px;"></span>',
							'active'  => '<span class="fusiona-hover-state" style="font-size:18px;"></span>',
						],
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swatch Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the color, image and button swatch fields.', 'fusion-builder' ),
						'param_name'  => 'swatch_background_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_bg_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'swatch_styling',
							'tab'  => 'regular',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swatch Active Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the color, image and button swatch fields when active.', 'fusion-builder' ),
						'param_name'  => 'swatch_background_color_active',
						'value'       => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'swatch_styling',
							'tab'  => 'active',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swatch Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the color, image and button swatch fields.', 'fusion-builder' ),
						'param_name'  => 'swatch_border_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_border_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'swatch_styling',
							'tab'  => 'regular',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Swatch Active Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the color, image and button swatch fields when active.', 'fusion-builder' ),
						'param_name'  => 'swatch_border_color_active',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_focus_border_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'subgroup'    => [
							'name' => 'swatch_styling',
							'tab'  => 'active',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Color Swatch Dimensions', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'color_swatch_dimensions',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'            => [
							'color_swatch_width'  => '',
							'color_swatch_height' => '',
						],
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Color Swatch Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'Controls the padding of the color swatches.  Enter values including any valid CSS unit, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'color_swatch_padding',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'color_swatch_padding_top'    => '',
							'color_swatch_padding_right'  => '',
							'color_swatch_padding_bottom' => '',
							'color_swatch_padding_left'   => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Color Swatch Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'color_swatch_border_radius',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'color_swatch_border_radius_top_left'  => '',
							'color_swatch_border_radius_top_right' => '',
							'color_swatch_border_radius_bottom_right' => '',
							'color_swatch_border_radius_bottom_left' => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Image Swatch Dimensions', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'image_swatch_dimensions',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'            => [
							'image_swatch_height' => '',
							'image_swatch_width'  => '',
						],
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Image Swatch Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'Controls the padding of the image swatches.  Enter values including any valid CSS unit, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'image_swatch_padding',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'image_swatch_padding_top'    => '',
							'image_swatch_padding_right'  => '',
							'image_swatch_padding_bottom' => '',
							'image_swatch_padding_left'   => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Image Swatch Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'image_swatch_border_radius',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'image_swatch_border_radius_top_left'  => '',
							'image_swatch_border_radius_top_right' => '',
							'image_swatch_border_radius_bottom_right' => '',
							'image_swatch_border_radius_bottom_left' => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Button Swatch Dimensions', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.  Leave empty for auto.', 'fusion-builder' ),
						'param_name'       => 'button_swatch_dimensions',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'            => [
							'button_swatch_width'  => '',
							'button_swatch_height' => '',
						],
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_attr__( 'Button Swatch Padding', 'fusion-builder' ),
						'description'      => esc_attr__( 'Controls the padding of the button swatches.  Enter values including any valid CSS unit, ex: 10px or 10%.', 'fusion-builder' ),
						'param_name'       => 'button_swatch_padding',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'button_swatch_padding_top'    => '',
							'button_swatch_padding_right'  => '',
							'button_swatch_padding_bottom' => '',
							'button_swatch_padding_left'   => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Button Swatch Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'button_swatch_border_radius',
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'button_swatch_border_radius_top_left'  => '',
							'button_swatch_border_radius_top_right' => '',
							'button_swatch_border_radius_bottom_right' => '',
							'button_swatch_border_radius_bottom_left' => '',
						],
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'textfield',
						'heading'     => esc_attr__( 'Button Swatch Font Size', 'fusion-builder' ),
						'description' => esc_html__( 'Controls the font size of the button swatches. Enter value including any valid CSS unit, ex: 20px.', 'fusion-builder' ),
						'param_name'  => 'button_swatch_font_size',
						'value'       => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Button Swatch Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the button swatches.', 'fusion-builder' ),
						'param_name'  => 'button_swatch_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'link_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Button Swatch Active Text Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the text color of the button swatches when active.', 'fusion-builder' ),
						'param_name'  => 'button_swatch_color_active',
						'value'       => '',
						'default'     => $fusion_settings->get( 'primary_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'dependency'  => [
							[
								'element'  => 'swatch_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],

					// Select Styles.
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Select Style', 'fusion-builder' ),
						'description' => esc_attr__( 'Select whether you want to custom style the select fields for variations.', 'fusion-builder' ),
						'param_name'  => 'select_style',
						'value'       => [
							''       => esc_attr__( 'Default', 'fusion-builder' ),
							'custom' => esc_attr__( 'Custom', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Select Height', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'field_height',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'value'            => [
							'select_height' => '',
						],
						'dependency'       => [
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],

					[
						'type'             => 'typography',
						'heading'          => esc_attr__( 'Select Typography', 'fusion-builder' ),
						'description'      => esc_html__( 'Controls the typography of the Select option. Leave empty for the global font family.', 'fusion-builder' ),
						'param_name'       => 'select_fonts',
						'choices'          => [
							'font-family'    => 'select_typography',
							'font-size'      => 'select_font_size',
							'text-transform' => 'select_text_transform',
							'line-height'    => 'select_line_height',
							'letter-spacing' => 'select_letter_spacing',
							'color'          => 'select_color',
						],
						'default'          => [
							'font-family'    => '',
							'variant'        => '400',
							'font-size'      => '',
							'text-transform' => '',
							'line-height'    => '',
							'letter-spacing' => '',
							'color'          => '',
						],
						'remove_from_atts' => true,
						'global'           => true,
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
						],
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Select Background Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the background color of the select fields.', 'fusion-builder' ),
						'param_name'  => 'select_background',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_bg_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'callback'    => [
							'function' => 'fusion_style_block',
						],
						'dependency'  => [
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'dimension',
						'heading'     => esc_attr__( 'Select Border Size', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border size of the select fields.', 'fusion-builder' ),
						'param_name'  => 'select_border_sizes',
						'value'       => [
							'select_border_sizes_top'    => '',
							'select_border_sizes_right'  => '',
							'select_border_sizes_bottom' => '',
							'select_border_sizes_left'   => '',
						],
						'callback'    => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'dependency'  => [
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
					],
					[
						'type'        => 'colorpickeralpha',
						'heading'     => esc_attr__( 'Select Border Color', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the border color of the select fields.', 'fusion-builder' ),
						'param_name'  => 'select_border_color',
						'value'       => '',
						'default'     => $fusion_settings->get( 'form_border_color' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_style_block',
						],
					],
					[
						'type'             => 'dimension',
						'remove_from_atts' => true,
						'heading'          => esc_html__( 'Select Border Radius', 'fusion-builder' ),
						'description'      => esc_html__( 'Enter values including any valid CSS unit, ex: 10px.', 'fusion-builder' ),
						'param_name'       => 'border_radius',
						'group'            => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'       => [
							[
								'element'  => 'select_style',
								'value'    => 'custom',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'         => [
							'function' => 'fusion_style_block',
							'args'     => [

								'dimension' => true,
							],
						],
						'value'            => [
							'border_radius_top_left'     => '',
							'border_radius_top_right'    => '',
							'border_radius_bottom_right' => '',
							'border_radius_bottom_left'  => '',
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Variation Clear', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls how you want to disable the variation clear link.', 'fusion-builder' ),
						'param_name'  => 'variation_clear',
						'value'       => [
							'absolute' => esc_attr__( 'Absolute', 'fusion-builder' ),
							'inline'   => esc_attr__( 'Inline', 'fusion-builder' ),
							'hide'     => esc_attr__( 'Hide', 'fusion-builder' ),
						],
						'default'     => 'absolute',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
					],
					[
						'type'        => 'radio_button_set',
						'heading'     => esc_attr__( 'Clear Content', 'fusion-builder' ),
						'description' => esc_attr__( 'Controls the content type for the clear link.  Default will use WooCommerce text string.', 'fusion-builder' ),
						'param_name'  => 'clear_content',
						'value'       => [
							''     => esc_attr__( 'Default', 'fusion-builder' ),
							'text' => esc_attr__( 'Text', 'fusion-builder' ),
							'icon' => esc_attr__( 'Icon', 'fusion-builder' ),
						],
						'default'     => '',
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'variation_clear',
								'value'    => 'hide',
								'operator' => '!=',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_cart',
							'ajax'     => true,
						],
					],
					[
						'type'        => 'iconpicker',
						'heading'     => esc_attr__( 'Clear Icon', 'fusion-builder' ),
						'param_name'  => 'clear_icon',
						'value'       => '',
						'description' => esc_attr__( 'Click an icon to select, click again to deselect.', 'fusion-builder' ),
						'group'       => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'  => [
							[
								'element'  => 'variation_clear',
								'value'    => 'hide',
								'operator' => '!=',
							],
							[
								'element'  => 'clear_content',
								'value'    => 'icon',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'    => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_cart',
							'ajax'     => true,
						],
					],
					[
						'type'         => 'textfield',
						'heading'      => esc_attr__( 'Clear Text', 'fusion-builder' ),
						'param_name'   => 'clear_text',
						'value'        => '',
						'description'  => esc_attr__( 'Custom text to use for the variation clear link.', 'fusion-builder' ),
						'dynamic_data' => true,
						'group'        => esc_attr__( 'Variations', 'fusion-builder' ),
						'dependency'   => [
							[
								'element'  => 'variation_clear',
								'value'    => 'hide',
								'operator' => '!=',
							],
							[
								'element'  => 'clear_content',
								'value'    => 'text',
								'operator' => '==',
							],
							[
								'element'  => 'show_variations',
								'value'    => 'no',
								'operator' => '!=',
							],
						],
						'callback'     => [
							'function' => 'fusion_ajax',
							'action'   => 'get_fusion_post_card_cart',
							'ajax'     => true,
						],
					],

					'fusion_animation_placeholder' => [
						'preview_selector' => '.fusion-post-card-cart',
					],
				],
				'callback'     => [
					'function' => 'fusion_ajax',
					'action'   => 'get_fusion_post_card_cart',
					'ajax'     => true,
				],
			]
		)
	);
}
add_action( 'fusion_builder_wp_loaded', 'fusion_element_post_card_cart' );
