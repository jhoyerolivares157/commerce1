<?php if ( !defined( 'ABSPATH' ) ) {
	die;
} // Cannot access pages directly.
/**
 *
 * Field: Switcher
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( !class_exists( 'OVIC_Field_switcher' ) ) {
	class OVIC_Field_switcher extends OVIC_Fields
	{
		public function __construct( $field, $value = '', $unique = '', $where = '' )
		{
			parent::__construct( $field, $value, $unique, $where );
		}

		public function output()
		{
			echo $this->element_before();
			$label = ( isset( $this->field['label'] ) ) ? '<div class="ovic-text-desc">' . $this->field['label'] . '</div>' : '';
			echo '<label><input type="checkbox" name="' . $this->element_name() . '" value="1"' . $this->element_class() . $this->element_attributes() . checked( $this->element_value(), 1, false ) . '/><em data-on="' . __( 'on', 'ovic-toolkit' ) . '" data-off="' . __( 'off', 'ovic-toolkit' ) . '"></em><span></span></label>' . $label;
			echo $this->element_after();
		}
	}
}
