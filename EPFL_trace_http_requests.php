<?php
/**
 * EPFL HTTP requests tracer
 *
 * @package     EPFLTraceHTTP
 * @author      ISAS-FSD
 * @copyright   Copyright (c) 2026, EPFL
 * @license     GPL-2.0-or-later
 *
 * @wordpress-mu-plugins
 * Plugin Name: EPFL Observability - Trace HTTP Requests
 * Plugin URI:  https://github.com/epfl-si/wp-mu-plugins
 * Description: Must-use plugin that activate traces of all 'wp_remote_*' calls if the Opentelemetry modules are activated
 * Version:     1.0.0
 * Author:      ISAS-FSD
 * Author URI:  https://go.epfl.ch/isas-fsd
 *
 */

add_action('plugins_loaded', function () {

	// Don't install the hook without OpenTelemetry features installed
	if ( ! class_exists( \OpenTelemetry\API\Globals::class ) ) {
		return;
	}

	add_filter( 'pre_http_request', function ( $preempt, $args, $url ) {

		if ( isset( $args['_otel_traced'] ) ) {
			return $preempt;
		}

		$method = strtoupper( $args['method'] ?? 'GET' );
		$parsed = wp_parse_url( $url );

		$scheme = $parsed['scheme'] ?? 'https';
		$host   = $parsed['host'] ?? '';
		$port   = $parsed['port'] ?? null;
		$path   = $parsed['path'] ?? '/';

		// For obfuscation purposes, remove query string completely
		$sanitizedUrl = $scheme . '://' . $host;
		if ( $port ) {
			$sanitizedUrl .= ':' . $port;
		}
		$sanitizedUrl .= $path;

		$tracer = \OpenTelemetry\API\Globals::tracerProvider()->getTracer( 'wp-http' );

		if ( ! $tracer ) {
			return $preempt;
		}

		// Set a stable span name
		$span = $tracer->spanBuilder( $method . ' ' . $host )
		               ->setSpanKind( \OpenTelemetry\API\Trace\SpanKind::KIND_CLIENT )
		               ->startSpan();

		$scope = $span->activate();

		try {

			// Add some usable infos
			$span->setAttribute( 'http.request.method', $method );
			$span->setAttribute( 'url.full', $sanitizedUrl );
			$span->setAttribute( 'server.address', $host );

			if ( $port ) {
				$span->setAttribute( 'server.port', $port );
			}

			$args['_otel_traced'] = true;

			$response = wp_remote_request( $url, $args );

			if ( ! is_wp_error( $response ) ) {
				$status = wp_remote_retrieve_response_code( $response );
				$span->setAttribute( 'http.response.status_code', $status );
			} else {
				$span->recordException( $response );
			}

			return $response;

		} finally {
			$span->end();
			$scope->detach();
		}

	}, 10, 3 );

});
