<?php
/**
 * Security Secrets Management
 *
 * Handles secure retrieval of application secrets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the secret key securely.
 *
 * Priorities:
 * 1. jawda_SCRT_KEY constant (defined in wp-config.php)
 * 2. Environment variable 'jawda_SCRT_KEY'
 * 3. Fallback (empty string or throws error in debug mode)
 *
 * @return string The secret key.
 */
function jawda_get_secret_key(): string {
	// 1. Check Constant
	if ( defined( 'jawda_SCRT_KEY' ) && ! empty( constant( 'jawda_SCRT_KEY' ) ) ) {
		return constant( 'jawda_SCRT_KEY' );
	}

	// 2. Check Environment Variable
	$env_key = getenv( 'jawda_SCRT_KEY' );
	if ( false !== $env_key && ! empty( $env_key ) ) {
		return $env_key;
	}

	// 3. Fallback / Failure Handling
	$message = '[jawda] Missing jawda_SCRT_KEY. Please define it in wp-config.php.';

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// Log the warning in debug mode
		error_log( $message );
	}

	return '';
}

/**
 * Check if the secret key is available.
 *
 * @return bool True if key exists and is not empty.
 */
function jawda_has_secret_key(): bool {
	return ! empty( jawda_get_secret_key() );
}

/**
 * Require the secret key to be present.
 *
 * Throws an exception or returns a WP_Error if missing, depending on context.
 * Useful for critical features that absolutely require the key.
 *
 * @throws Exception If in debug mode and key is missing.
 * @return void
 */
function jawda_require_secret_key() {
	if ( ! jawda_has_secret_key() ) {
		$msg = 'Critical Error: jawda_SCRT_KEY is missing.';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			throw new Exception( $msg );
		} else {
			error_log( $msg );
			wp_die( __( 'A critical system configuration is missing. Please contact the administrator.', 'jawda' ) );
		}
	}
}

// -----------------------------------------------------------------------------
// Auto-Initialization & Backward Compatibility
// -----------------------------------------------------------------------------

// 1. Ensure jawda_SCRT_KEY is defined as a constant if available via env
if ( ! defined( 'jawda_SCRT_KEY' ) ) {
    $env_key = getenv( 'jawda_SCRT_KEY' );
    if ( false !== $env_key && ! empty( $env_key ) ) {
        if ( ! defined( 'jawda_SCRT_KEY' ) ) {
            define( 'jawda_SCRT_KEY', $env_key  );
        }
    } else {
        // Define as empty string to avoid "undefined constant" notices if checked elsewhere
        if ( ! defined( 'jawda_SCRT_KEY' ) ) {
            define( 'jawda_SCRT_KEY', ''  );
        }
    }
}

// 2. Backward Compatibility for 'scrtky'
// If 'scrtky' is NOT defined, define it using the new secure key.
// This prevents fatal errors in legacy code that might rely on it.
if ( ! defined( 'scrtky' ) ) {
    // We use the new key as the value for the old key.
    // If the new key is not set (empty), the old key becomes empty.
    // This removes the hardcoded secret while keeping the constant defined.
    define( 'scrtky', jawda_get_secret_key() );
}
