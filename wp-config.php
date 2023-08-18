<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'commerce' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Z.8OU,jpU6})]u<1o_8U&.xs@hnmr0yF31B&jt6O|ip<c<Y^@h8=2~qz#1@9eAiY' );
define( 'SECURE_AUTH_KEY',  'F&&AuHJ4u4P{:3H^O(y]bZCWmmFYX[J=,J>)MsGosU]eUY>K,L[C^l$&r+| )yiV' );
define( 'LOGGED_IN_KEY',    ']_1=@MyxuM_B5l4eRZ???@B:wZNlc:20s*yNNhLCRUN}6bgwY4LbFw6/UdoNGH9?' );
define( 'NONCE_KEY',        'r/7]UpY1<36llS)T&IOjF<0A~*v!-@q;xx}gMX4,U.+qb?qGk{ meQC{59X*(mQa' );
define( 'AUTH_SALT',        'sam1-n]iQa5jQlFqm9fz?H4T8Z^xyJw/.b<lS<ZstR~f_f%&Gk-&9.SO)f})pGZ?' );
define( 'SECURE_AUTH_SALT', 'YT[)*<T _F|Qb]v suQL]pA9O}QeR/E#*!Kk$FLh^,d&:]z%q-L<qZFbpe~ s;F%' );
define( 'LOGGED_IN_SALT',   '0yj56V1_AkhoLT<kdg#{~8HtBSIbk}rc??NeIJ+(<r)@y/PLvyKFZiVFH?1x8]dk' );
define( 'NONCE_SALT',       '9UR4=!zTz4mahgK#?~G-!gy=uz2|i<=>a`wkX5_-ttWqo<c<$ /k!;>_scrjo_E0' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
