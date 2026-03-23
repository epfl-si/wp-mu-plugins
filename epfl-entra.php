<?php
/**
 * Integration with EPFL Entra
 *
 * This must-use plugin ensures that the `openid-connect-generic`
 * plug-in is configured to authenticate with Microsoft Entra.
 *
 * In the EPFL setup, acquiring / destroying the relevant credentials
 * is done via a REST API hosted at app-portal.epfl.ch. For security
 * reasons, the credentials to this REST API are *not* made available
 * to the main (interactive) Web pods; rather, the
 *
 * @link              https://app-portal.epfl.ch/
 * @package           EFPL-MU-plugins
 *
 * @wordpress-plugin
 * Plugin Name:       Entra App Portal
 * Plugin URI:        https://github.com/epfl-si/wp-mu-plugins
 * Description:       Manage Entra credentials via app-portal.epfl.ch
 * Version:           0.1.0
 * Author:            EPFL ISAS-FSD
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       EFPL-MU-plugins
 */

namespace EPFL\AppPortal;

if ( ! defined( 'WPINC' ) ) {
    die;
}


/**
 * Abstraction for the Entra-relevant data of this (or any) WordPress instance.
 */
class WordPress {
  public $url;
  public $tagline;
  public $appId;

  private function get_additional_test_hostnames () {
    # TODO: maybe we could find a way to plumb this down better than a constant list.
    return [
      "wp-test-apple.epfl.ch",
      "wp-test-grapes.epfl.ch",
      "wp-test-kiwi.epfl.ch",
      "wp-test-lemon.epfl.ch",
      "wp-test-orange.epfl.ch",
      "wp-test-alpha.epfl.ch",
      "wp-test-rc.epfl.ch"
    ];
  }

  public function __construct ($url, $tagline, $appId) {
    $this->url     = $url;
    $this->tagline = $tagline;
    $this->appId   = $appId;
  }

  public static function this_site () {
    $option = @get_option("openid_connect_generic_settings");
    $clientId = $option ? $option["client_id"]: null;
    return new static(
      \site_url(), \get_bloginfo("description"),
      $clientId);
  }

  public function get_oidc_redirect_urls () {
    $p = parse_url($this->url);
    $hostnames = [$p["host"]];
    if ($p["host"] === "wpn-test.epfl.ch") {
      $hostnames = array_merge($hostnames, $this->get_additional_test_hostnames());
    }

    return array_map(function ($hostname) {
      return $this->get_redirect_uri($hostname);
    }, $hostnames);
  }

  public function get_redirect_uri ($hostname = NULL) {
    $p = parse_url($this->url);

    if ($hostname === NULL) {
      $hostname = $p["host"];
    }
    $path = $p["path"];

    return "https://{$hostname}{$path}/wp-admin/admin-ajax.php?action=openid-connect-authorize";
  }

  public function get_app_portal_redirect_uris ($api) {
    $app_response = $api->read_entra_app($this);
    if (isset($app_response) and
        isset($app_response["spa"]) and
        isset($app_response["spa"]["redirectUris"])) {
      return $app_response["spa"]["redirectUris"];
    } else {
      return NULL;
    }
  }

  public function use_new_entra_app ($api) {
      error_log("ENTRA-MUPLUGIN - Creating app ...");
      $oidc_settings = get_option("openid_connect_generic_settings");
      if (isset($oidc_settings["client_id"])) {
        $redirect_uris = $this->get_app_portal_redirect_uris($api) ?? [];
        foreach ($redirect_uris as $redirect_uri) {
          error_log("ENTRA-MUPLUGIN - app portal redirectUri ...{$redirect_uri}");
          if ($redirect_uri === $this->get_redirect_uri()) {
            error_log("ENTRA-MUPLUGIN - redirectUri confirmed ...");
            return;
          }
        }
      }

      $appId = $api->create_entra_app($this)["appId"];
      $tenantId = getenv("ENTRA_APP_TENANT_ID");

      $oidc_settings["login_type"] = "auto";
      $oidc_settings["client_id"] = $appId;
      $oidc_settings["client_secret"] = "";  # So-called single-page Web app
      $oidc_settings["endpoint_login"] = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize";
      $oidc_settings["endpoint_token"] = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
      $oidc_settings["issuer"] = "https://login.microsoftonline.com/{$tenantId}/v2.0";
      $oidc_settings["scope"] = "openid profile email {$appId}/.default";
      $oidc_settings["endpoint_userinfo"] = "https://api.epfl.ch/v2/oidc/userinfo?groups&rights=WordPress.Editor";
      $oidc_settings["endpoint_end_session"] = "";
      $oidc_settings["acr_values"] = "";

      $oidc_settings["no_sslverify"] = "";
      $oidc_settings["http_request_timeout"] = "15";
      $oidc_settings["identity_key"] = "uniqueid";
      $oidc_settings["nickname_key"] = "gaspar";
      $oidc_settings["email_format"] = "{email}";
      $oidc_settings["displayname_format"] = "";
      $oidc_settings["identify_with_username"] = "";
      $oidc_settings["state_time_limit"] = "";
      $oidc_settings["enforce_privacy"] = 0;
      $oidc_settings["allow_internal_idp"] = 1;
      $oidc_settings["alternate_redirect_uri"] = "";
      $oidc_settings["token_refresh_enable"] = "";
      $oidc_settings["link_existing_users"] = "1";
      $oidc_settings["create_if_does_not_exist"] = "";
      $oidc_settings["redirect_user_back"] = "";
      $oidc_settings["redirect_on_logout"] = "";
      $oidc_settings["enable_logging"] = "";
      $oidc_settings["log_limit"] = "";

      error_log("ENTRA-MUPLUGIN - Setting options ... {$appId}");
      update_option("openid_connect_generic_settings", $oidc_settings);
      error_log("ENTRA-MUPLUGIN - Done {$appId}");
  }
}

class AppPortalException extends \Exception {
  public $result;

  public function __construct ($message, $code, $result) {
    parent::__construct($message, $code);
    $this->result = $result;
  }

  /**
   * Workaround to https://go.epfl.ch/INC0788077
   */
  public function means404 () {
      return ($this->getCode() == 404 || $this->isFaux403());
  }

  private function isFaux403 () {
    return ($this->getCode() == 403
            and is_array($this->result)
            and @$this->result["Message"] == "Error getting application configuration");
  }

  public function __toString () {
    $result = json_encode($this->result);
    return "app-portal HTTP status {$this->getCode()}: {$result}";
  }
}

class AppPortalAPI {
  private function get_api_credentials () {
    $clientId     = getenv('ENTRA_APP_CLIENT_ID');
    $clientSecret = getenv('ENTRA_APP_CLIENT_SECRET');
    $tenantId     = getenv('ENTRA_APP_TENANT_ID');

    if ($clientId && $clientSecret && $tenantId) {

      return [$clientId, $clientSecret, $tenantId];
    } else {

      return NULL;
    }
  }

  /**
   * Tell whether this entire mu-plugin should be active or inactive.
   *
   * The intent is for the caller to ensure that this mu-plugin only
   * be active in “offline” environments, i.e. the WordPress operator
   * or wp-cron pods; *not* the interactive (Web server) pods.
   *
   * @return TRUE iff credentials to the app-portal API are available in the process environment.
   */
  public function is_available () {
    return $this->get_api_credentials() !== NULL;
  }

  private $cached_token;

  private function get_token () {
    if ($this->cached_token) return $this->cached_token;

    $credentials = $this->get_api_credentials();
    if ($credentials === NULL) {
      throw new \RuntimeException("No app-portal credentials available.");
    }

    [$clientId, $clientSecret, $tenantId] = $credentials;

    $url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

    $postFields = http_build_query([
      'client_id'     => $clientId,
      'client_secret' => $clientSecret,
      'scope'         => "api://{$clientId}/.default",
      'grant_type'    => 'client_credentials',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST            => true,
      CURLOPT_POSTFIELDS      => $postFields,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_HTTPHEADER      => [
        'Content-Type: application/x-www-form-urlencoded',
      ],
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
      $error = curl_error($ch);
      curl_close($ch);
      throw new \RuntimeException("cURL error: {$error}");
    }

    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus < 200 || $httpStatus >= 300) {
      throw new \RuntimeException("Failed to generate token: {$httpStatus} {$response}");
    }

    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
      $this->cached_token = $data['access_token'];
      return $this->cached_token;
    } else {
      throw new \RuntimeException("No access_token in response: {$response}");
    }
  }

  private function make_app_portal_url ($url_suffix) {
    $app_portal_url_base = getenv("APP_PORTAL_URL") ?: "https://app-portal.epfl.ch";
    $slashsep = substr($url_suffix, 0, 1) == "/" ? "" : "/";
    return "{$app_portal_url_base}{$slashsep}{$url_suffix}";
  }

  private function call_app_portal_api ($method, $url_suffix, $body_params = NULL) {
    $token = $this->get_token();

    $url = $this->make_app_portal_url($url_suffix);
    $ch = curl_init($url);

    $curlopts = [
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_HTTPHEADER      => [
        "Content-Type: application/json",
        "Authorization: Bearer {$token}"
      ]
    ];

    if ($method === "GET") {
      # Nothing
    } elseif ($method === "POST") {
      $curlopts[CURLOPT_POST] = true;
    } else {
      $curlopts[CURLOPT_CUSTOMREQUEST] = $method;
    }

    if ($body_params !== NULL) {
      $curlopts[CURLOPT_POSTFIELDS] = json_encode($body_params);
    }

    curl_setopt_array($ch, $curlopts);

    $response = curl_exec($ch);
    if ($response === false) {
      $error = curl_error($ch);
      curl_close($ch);
      throw new \RuntimeException("cURL error at {$url}: {$error}");
    }

    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $payload = json_decode($response, true);

    if ($httpStatus < 200 || $httpStatus >= 300) {
      throw new AppPortalException("{$method} call to {$url} failed with status ${httpStatus}: {$response}",
                                   $httpStatus, $payload ?? $response);
    } else {
      return $payload;
    }
  }

  private function get_environment_id () {
    return (getenv("ENV") === "prod") ? 3 : 2;
  }

  public function generate_site_name ($name) {
      $segmentIndex = 1;
      while (strlen($name) >= 50 && $segmentIndex < count(explode('-', $name))) {
          $segments = explode('-', $name);

          $firstPart = array_slice($segments, 0, $segmentIndex);
          $firstPart = array_map(function ($segment) {
              return $segment[0];
          }, $firstPart);

          $remaining = array_slice($segments, $segmentIndex);

          $abbreviated = implode('-', array_merge($firstPart, $remaining));

          $name = $abbreviated;
          $segmentIndex++;
      }
      error_log("ENTRA-MUPLUGIN - generate_site_name returns ... {$name}");
      return strtolower($name);
  }

  public function create_entra_app ($wordpress) {
    $name = str_replace(".epfl.ch", "",str_replace("/", "-", str_replace("https://","",$wordpress->url)));
    error_log("ENTRA-MUPLUGIN - Creating app : calling API ... {$name}");
    if (substr($name, -1) === "-") {
        // Remove the last character
        $name = substr($name, 0, -1);
    }
    $response = $this->call_app_portal_api("POST", "/app-portal-api/v1/portal/oidc-apps", [
      "authorizedUsers" => [],
      "config_desc" => "WordPress site {$wordpress->url}",
      "description" => "Application for site" . str_replace("/","-",$wordpress->url),
      "displayName" => "WP ({$this->generate_site_name($name)})",
      "environmentID" => $this->get_environment_id(),
      "notes" => "Entra application for WordPress site ({$wordpress->url})",
      "spa" => [ "redirectUris" => $wordpress->get_oidc_redirect_urls() ],
      "unitID" => "13030"
    ]);
    error_log("ENTRA-MUPLUGIN - App created {$name}");
    return $response["App"];
  }

  private function get_relative_url_of_app ($wordpress) {
    return "/app-portal-api/v1/portal/oidc-apps/{$wordpress->appId}";
  }

  public function read_entra_app ($wordpress) {
    try {
      $response = $this->call_app_portal_api(
        "GET", $this->get_relative_url_of_app($wordpress));

      if (! $response["App"]) {
        throw new \RuntimeException("read_entra_app failed: " . json_encode($response));
      }

      return $response["App"];
    } catch (AppPortalException $e) {
      if ($e->means404()) {
        $url = $this->get_relative_url_of_app($wordpress);
        error_log("WARNING: HTTP GET {$url}: {$e}, continuing");
        return NULL;
      } else {
        throw $e;
      }
    }
  }

  public function delete_entra_app ($wordpress) {
    $url = $this->get_relative_url_of_app($wordpress);
    try {
      $response = $this->call_app_portal_api(
        "DELETE", $url);

      if ($response["Message"] != "") {
        throw new \RuntimeException("delete_entra_app failed: " . json_encode($response));
      }
    } catch (AppPortalException $e) {
      if ($e->means404()) {
        error_log("WARNING: HTTP DELETE {$url}: {$e}, continuing");
      } else {
        throw $e;
      }
    }
  }
}

/**
 * Switch to PKCE workflow if no secret has been provided : used for
 * single page apps (SPA) configuration
 */
add_filter('openid-connect-generic-auth-url', function( $url ) {
    $settings = get_option('openid_connect_generic_settings', array());
    if (isset($settings['client_secret']) && $settings['client_secret'] !== '') {
        return $url;
    }

    // Generate a random string for the code challenge
    $code_verifier = bin2hex(random_bytes(64));
    $hash = hash('sha256', $code_verifier, true);
    $code_challenge = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    $url.= '&code_challenge=' . $code_challenge;
    $url .= '&code_challenge_method=S256';

    $parsed = wp_parse_url($url);
    $query  = [];
    parse_str($parsed['query'], $query);
    $state = $query['state'];

    set_transient(
        'epfl_oidc_pkce_' . $state,
        $code_verifier,
        15 * MINUTE_IN_SECONDS
    );
    return $url;
});

add_filter('openid-connect-generic-alter-request', function( $request, $operation ) {
    if ( $operation != 'get-authentication-token' && $operation != 'refresh-token' ) {
        return $request;
    }
    $settings = get_option('openid_connect_generic_settings', array());
    if (isset($settings['client_secret']) && $settings['client_secret'] !== '') {
        return $request;
    }
    unset($request['body']['client_secret']);

    if (isset($request['body']['redirect_uri']) &&
        isset($_SERVER['HTTP_X_KONG_ORIG_HOST'])) {
      $request['body']['redirect_uri'] = preg_replace(
        '#^(https?://)[^/]+#',
        '$1' . $_SERVER['HTTP_X_KONG_ORIG_HOST'],
        $request['body']['redirect_uri']);
    }

    # Entra wants to believe that the browser is performing the query:
    $request['headers']['Origin'] = $request['body']['redirect_uri'];

    $state = sanitize_text_field(wp_unslash($_GET['state']));
    $transient_key = 'epfl_oidc_pkce_' . $state;
    $code_verifier = get_transient( $transient_key );
    $request['body']['code_verifier'] = $code_verifier;
    delete_transient( $transient_key );
    return $request;
}, 10, 2);

/**
 * Update OpenID login button text
 */
add_filter('openid-connect-generic-login-button-text', function( $text ) {
    $text = __('Login EPFL');
    return $text;
});

/**
 * Update plugin configuration fields:
 *
 * - hide OpenID client secret
 * - add field hide login form
 *
 */
add_filter('openid-connect-generic-settings-fields', function( $fields ) {
    unset($fields["client_secret"]);
    $fields['hide_login_form'] = array(
        'title' => __('Hide login form'),
        'description' => __('Prevent user to log in with Wordpress user/password'),
        'type' => 'checkbox',
        'section' => 'authorization_settings',
    );
    return $fields;
});

/**
 * Bugware specific to daggerhart-openid-connect-generic: disable JWKS admin_notice
 *
 * As we obtain OIDC credentials over a secure TLS connection, we
 * don't need to perform extra crypto to validate them.
 * daggerhart-openid-connect-generic insists otherwise, by means of an
 * alarming WordPress `admin_notice`. Don't let them.
 */
add_action('init', function() {
  $wp_filter = $GLOBALS['wp_filter'];
  if ($admin_notices = @$wp_filter['admin_notices']) {
      foreach ($admin_notices->callbacks as $priority => $callbacks) {
          foreach ($callbacks as $id => $callback) {
              $fn = $callback['function'];
              if (is_array($fn) && strpos($callback['function'][1], 'jwks_required') !== false) {
                  error_log("Found it!! At priority $priority");  // XXX DONTKEEPTHIS
                  remove_action('admin_notices', $callback['function'], $priority);
              }
          }
      }
  }
},
  20); // i.e. after OpenID_Connect_Generic::init() returns

$api = new AppPortalAPI();

define('OPENID_PLUGIN', 'daggerhart-openid-connect-generic/openid-connect-generic.php');

if ($api->is_available()) {

  add_action('activated_plugin', function ($plugin, $network_wide) use ($api) {
    if ($plugin === OPENID_PLUGIN) {
      WordPress::this_site()->use_new_entra_app($api);
    }
  }, 10, 2);

  add_action('deactivated_plugin', function ($plugin, $network_wide) use ($api) {
    if (FALSE !== strpos($plugin, '/openid-connect-generic.php')) {
      $api->delete_entra_app(WordPress::this_site());
    }
  }, 10, 2);

  add_action('wp_operator_post_restore', function ($unused) use ($api) {
    if (! is_plugin_active(OPENID_PLUGIN)) return;
    $this_site = WordPress::this_site();
    $redirect_uris = $this_site->get_app_portal_redirect_uris($api) || [];
    foreach ($redirect_uris as $redirect_uri) {
      if ($redirect_uri === $this_site->get_redirect_uri()) {
        return;  # Restore is at same URL as before; dont't touch anything
      }
    }

    # Restore is at new URL; need new App Portal credentials
    $this_site->use_new_entra_app($api);
  });
}
