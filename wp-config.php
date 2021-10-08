<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', '98553393houssem' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '>anLFmuKka|QpPo.hin/c_8|B G|Wewi~W3L`s<][AvM0Oi>=_~~_Gy=K,W2;<9?' );
define( 'SECURE_AUTH_KEY',  '/qTaI5r_EIr$M_apoQ[aw*&^nsO@x6AW;:*idXX,sv2K(7$pIi6RF/}cr:it;+ew' );
define( 'LOGGED_IN_KEY',    '19..t)S.Iz6,?B!i.ij?0?I!>|4>?S`dpx4J#^jH##DN%WDm3jI)1,i|X5vOd7L}' );
define( 'NONCE_KEY',        '7r[ DQp(Y>xx7Ydvt)uvAZJYf^3xYm>7&t9-AP_rMa>FT^`1I-,ii###9tbZW{Vj' );
define( 'AUTH_SALT',        '2z973K|x3BYSm7g<o*/@Os;40dTX<~TkLvQRZrFL.96X97U:VJ;exJBP(b);sa77' );
define( 'SECURE_AUTH_SALT', ')?wx+mR`3G}i?!BfQ$R6MDV&g:A*FJTVbt;y<Ch,FgpBUbJ}`AVe%:uVN&=eOC47' );
define( 'LOGGED_IN_SALT',   'GXf40IjO=^mmdcq0~*Eoi%H?suyNoo9ysRdd8*.LVe@)Z!5rl_[EWy]=N:{#q>I*' );
define( 'NONCE_SALT',       '{Owlv9VC yp<{R)JwTMQ`c&<Jj=CKV2tz2zXBLkY|&mHJ$mL*ynk.lTVs<l00A]/' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';
@ini_set('upload_max_size' , '256M' );
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );
define( 'FS_METHOD', 'direct' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

