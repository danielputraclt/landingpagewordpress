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
define( 'DB_NAME', 'landingpage' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
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
define( 'AUTH_KEY',         'N9*>b k%!FY_a>=ww;%&:7H;o-P1$k3YybN<u tR-h$YmGG){_x/>l4%62[:fXue' );
define( 'SECURE_AUTH_KEY',  '}$lBte[q!bp(Tv_!yE^MTL=-a<|{Xv(C69U,K2;VU.-%;sfUjzdYg <P]]<c=z9,' );
define( 'LOGGED_IN_KEY',    'An]DD=vO V^b1lb<LEK2CG#q4FOD)f>|yY.|Ee}(0a+v!w&D=s|%^-UY42fMcnB:' );
define( 'NONCE_KEY',        'W85-JXm/mGI]XeL-N:rPr9w#n0,E7OZ5rU$YSk!}(^aY=jA|}!*Wi@F]~5tP,fb~' );
define( 'AUTH_SALT',        'Uk1zdL(6n]5j(id%_b1odt1aWt>RrMrhiq.X$V%#|v[6_(-~ok)A:}d!X0yt}^}f' );
define( 'SECURE_AUTH_SALT', '+VluPZ0P6t(X55Ll)[@:6fV=KuNs}qHOrb@A9D0=TFPseUVomx=b,dLdS!|P>d2S' );
define( 'LOGGED_IN_SALT',   ']ypmg#HpbB6alVanQ[Td8t>Ox@pXKSP/V`E3c)W,7/))_f)o,kRgl/GnTkW}A/rt' );
define( 'NONCE_SALT',       'MQknm[$kavjfe2>1`CSep<R1}dBy&OcK9t@=){? 1s0S{ATts`<w>=SP#l c@@dh' );

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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
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
