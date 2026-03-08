Feature: List sites where a plugin is active

  Background:
    Given a WP multisite install
    And I run `wp plugin install hello-dolly --force`

  Scenario: Plugin is not installed
    When I try `wp plugin active-on-sites nonexistent-plugin`
    Then STDERR should contain:
      """
      nonexistent-plugin is not installed.
      """
    And the return code should be 1

  Scenario: Plugin is network-activated
    Given I run `wp plugin activate hello-dolly --network`
    When I try `wp plugin active-on-sites hello-dolly`
    Then STDERR should contain:
      """
      hello-dolly is network-activated.
      """
    And the return code should be 0

  Scenario: Plugin is not active on any site
    When I run `wp plugin active-on-sites hello-dolly`
    Then STDOUT should contain:
      """
      hello-dolly is not active on any sites.
      """

  Scenario: Plugin is active on one site
    Given I run `wp plugin activate hello-dolly`
    When I run `wp plugin active-on-sites hello-dolly`
    Then STDOUT should contain:
      """
      Sites where hello-dolly is active:
      """
    And STDOUT should contain:
      """
      https://example.com/
      """

  Scenario: Plugin is active on multiple sites
    Given I run `wp site create --slug=second-site`
    And I run `wp plugin activate hello-dolly`
    And I run `wp plugin activate hello-dolly --url=example.com/second-site`
    When I run `wp plugin active-on-sites hello-dolly`
    Then STDOUT should contain:
      """
      Sites where hello-dolly is active:
      """
    And STDOUT should contain:
      """
      https://example.com/
      """
    And STDOUT should contain:
      """
      second-site
      """
