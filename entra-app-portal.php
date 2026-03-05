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
    return new static(
      \site_url(), \get_bloginfo("description"),
      @get_option("openid_connect_generic_settings")["client_id"]);
  }

  public function get_oidc_redirect_urls () {
    $p = parse_url($this->url);
    $hostnames = [$p["host"]];
    if ($p["host"] === "wpn-test.epfl.ch") {
      $hostnames = array_merge($hostnames, $this->get_additional_test_hostnames());
    }

    return array_map(function ($hostname) use ($path) {
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

  public function use_new_entra_app ($api) {
      $oidc_settings = get_option("openid_connect_generic_settings");
      $appId = $api->create_entra_app()["appId"];

      $oidc_settings["login_type"] = "auto";
      $oidc_settings["client_id"] = $appId;
      $oidc_settings["client_secret"] = "";  # So-called single-page Web app
      $oidc_settings["endpoint_login"] = "https://login.microsoftonline.com/{$app->tenantId}/oauth2/v2.0/authorize";
      $oidc_settings["endpoint_token"] = "https://login.microsoftonline.com/{$app->tenantId}/oauth2/v2.0/token";
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
      $oidc_settings["enforce_privacy"] = "0";
      $oidc_settings["alternate_redirect_uri"] = "";
      $oidc_settings["token_refresh_enable"] = "";
      $oidc_settings["link_existing_users"] = "1";
      $oidc_settings["create_if_does_not_exist"] = "";
      $oidc_settings["redirect_user_back"] = "";
      $oidc_settings["redirect_on_logout"] = "";
      $oidc_settings["enable_logging"] = "";
      $oidc_settings["log_limit"] = "";

      set_option("openid_connect_generic_settings", $oidc_settings);
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
      throw new RuntimeException("No app-portal credentials available.");
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
      throw new RuntimeException("cURL error: {$error}");
    }

    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus < 200 || $httpStatus >= 300) {
      throw new RuntimeException("Failed to generate token: {$httpStatus} {$response}");
    }

    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
      $this->token = $data['access_token'];
      return $this->token;
    } else {
      throw new RuntimeException("No access_token in response: {$response}");
    }
  }

  private function make_app_portal_url ($url_suffix) {
    $app_portal_url_base = getenv("APP_PORTAL_URL") ?: "https://app-portal.epfl.ch";
    $slashsep = substr($url_suffix, 0, 1) == "/" ? "" : "/";
    return "{$app_portal_url_base}{$slashsep}{$url_suffix}";
  }

  private function call_app_portal_api ($method, $url_suffix, $body_params = NULL) {
    $token = $this->get_token();

    $url = make_app_portal_url($url_suffix);
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
      throw new RuntimeException("cURL error at {$url}: {$error}");
    }

    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus < 200 || $httpStatus >= 300) {
      throw new RuntimeException("{$method} call to {$url} failed: {$httpStatus} {$response}");
    }

    return json_decode($response, true);
  }

  private function get_environment_id () {
    return (getenv("ENV") === "prod") ? 3 : 2;
  }

  public function create_entra_app ($wordpress) {
    $response = $this->call_app_portal_api("POST", "/app-portal-api/v1/portal/oidc-apps", [
      authorizedUsers => ["AAD_All Outside EPFL Users", "AAD_All Hosts Users", "AAD_All Student Users", "AAD_All Staff Users"],
      config_desc => "WordPress site {$wordpress->url}",
      description => "Application for site {$wordpress->tagline}",
      environmentID => $this->get_environment_id(),
      notes => "Entra application for WordPress site ({$wordpress->url})",
      spa => [ redirectUris => $wordpress->get_oidc_redirect_urls() ],
      unitID => "13030"
    ]);
    if (! $response["ok"]) {
      throw new RuntimeException("create_entra_app failed: " . json_encode($response));
    }

    return $response["App"];
  }

  private function get_relative_url_of_app ($wordpress) {
    return "/app-portal-api/v1/portal/oidc-apps/{$wordpress->appId}";
  }

  public function read_entra_app ($wordpress) {
    $response = $this->call_app_portal_api(
      "GET", $this->get_relative_url_of_app($wordpress));

    if (! $response["App"]) {
      throw new RuntimeException("read_entra_app failed: " . json_encode($response));
    }

    return $response["App"];
  }

  public function delete_entra_app ($wordpress) {
    $response = $this->call_app_portal_api(
      "DELETE", $this->get_relative_url_of_app($wordpress));

    if (! $response["ok"]) {
      throw new RuntimeException("delete_entra_app failed: " . json_encode($response));
    }

    return $response;
  }
}

$api = AppPortalAPI();

define(OPENID_PLUGIN, 'openid-connect-generic/openid-connect-generic.php');

if ($api->is_available()) {
  add_action('activated_plugin', function ($plugin, $network_wide) {
    if ($plugin === OPENID_PLUGIN) {
      WordPress::this_site()->use_new_entra_app($api);
    }
  }, 10, 2);

  add_action('deactivated_plugin', function ($plugin, $network_wide) {
    if ($plugin === OPENID_PLUGIN) {
      $api->delete_entra_app(WordPress::this_site());
    }
  }, 10, 2);

  add_action('wp_operator_post_restore', function ($unused) {
    if (! is_plugin_active(OPENID_PLUGIN)) return;

    foreach ($api->read_entra_app($this_site)["redirectURIs"] as $redirect_uri) {
      if ($redirect_uri === $this_site->get_redirect_uri()) {
        return;  # Restore is at same URL as before; dont't touch anything
      }
    }

    # Restore is at new URL; need new App Portal credentials
    $this_site->use_new_entra_app($api);
  });
}
