<?php
/**
 * The base configuration for TyPrint
 *
 * The tp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "tp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Server settings
 * * Database settings
 * * Secret keys
 *
 * @link TODO
 *
 * @package TyPrint
 */

declare(strict_types=1);

// Server settings - You can get this info from your web host

/** The listen address for TyPrint */
const SERVER_ADDRESS = '0.0.0.0';

/** The listen port for TyPrint */
const SERVER_PORT = 3000;

// Database settings - You can get this info from your web host

/** The type of database for TyPrint */
/** Supported: mysql, postgres, sqlite */
const DB_TYPE = 'mysql';

/** The name of the database for TyPrint */
/** For sqlite, it's the path to the database file */
const DB_DATABASE = 'database_name_here';

/** Database username */
const DB_USER = 'username_here';

/** Database password */
const DB_PASSWORD = 'password_here';

/** Database hostname or socket */
const DB_HOST = '127.0.0.1';

/** Database table prefix */
/** Change this if you have multiple installations in the same database */
const DB_TABLE_PREFIX = 'tp_';

/**#@+
 * Authentication unique keys and salts.
 *
 * Must change these to different unique phrases!
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 */
const AUTH_KEY = 'put your unique phrase here';
const SECURE_AUTH_KEY = 'put your unique phrase here';
const LOGGED_IN_KEY = 'put your unique phrase here';
const NONCE_KEY = 'put your unique phrase here';

/**#@-*/

/**
 * For developers: TyPrint debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use TP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link TODO
 */
const TP_DEBUG = false;

/* Add any custom values between this line and the "stop editing" line. */


/* That's all, stop editing! Happy printing. */
