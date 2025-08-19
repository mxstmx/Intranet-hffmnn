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
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_bozka' );

/** Database username */
define( 'DB_USER', 'wp_lc7rs' );

/** Database password */
define( 'DB_PASSWORD', 'Cc0q_bR6LZWfpf$6' );

/** Database hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define('AUTH_KEY', 'sNPzke+Y@-LuA|w_18;uSsN%z5AtT)Jv4d34sD/ku6~-6j83u4Q840Bazd@7f9&2');
define('SECURE_AUTH_KEY', '#)o!1-8Yia63s1:%27uj2j&W~/F65@mp35;nG&)+INH+(sq9Vxg;v[pxBD2Hf/Y-');
define('LOGGED_IN_KEY', 'x3g574G|B0R648PpG|CE2~wbR@T7z4[y)-b221U-0-:G3a8xwq*H14w9f07|IW*0');
define('NONCE_KEY', 'wq7&5t51bWD#tbHw8]4LFPw2|tJ1sNS6DG3u3pMOW7+vg28/~*/(0fY6Y1s65g@[');
define('AUTH_SALT', 'G@4Yp&a*_36bEV~~YUie[5bIB6Bbu7G]vl8@2-V|Vh_/18Du~vMg&P]w6z#X*of~');
define('SECURE_AUTH_SALT', 'i]s23&@%Q&X9|PfYjP71!3LBpP9m1fL6MF:81*#3-53!]628I7DbRw_Wdh)wkYT!');
define('LOGGED_IN_SALT', 'z*Tq9jT!06M@laO5:YWq30;1-B%2u~Tq52v@v836I2+ekUv4!e;v3)+3sn-ZB3KM');
define('NONCE_SALT', '*UG1G!kTqPJn5J*K:k2I12@-aX41;cLhfLo[*1tpBI5|(9[8SEZ2m08k[/V:pPn9');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'OPcOmJApU_';




/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
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
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
