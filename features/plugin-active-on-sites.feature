Feature: List sites where a plugin is active

  Background:
    Given a WP multisite install
    And I run `wp plugin install wordpress-seo --force`

  Scenario: Plugin is not installed
    When I try `wp plugin active-on-sites nonexistent-plugin`
    Then STDERR should contain:
      """
      nonexistent-plugin is not installed.
      """
    And the return code should be 1

  @network
  Scenario: Plugin is network-activated
    Given I run `wp plugin activate wordpress-seo --network`
    When I try `wp plugin active-on-sites wordpress-seo`
    Then STDERR should contain:
      """
      wordpress-seo is network-activated.
      """
    And the return code should be 0

  Scenario: Plugin is not active on any site
    When I run `wp plugin active-on-sites wordpress-seo`
    Then STDOUT should contain:
      """
      wordpress-seo is not active on any sites.
      """

  Scenario: Plugin is active on one site
    Given I run `wp plugin activate wordpress-seo`
    When I run `wp plugin active-on-sites wordpress-seo`
    Then STDOUT should contain:
      """
      Sites where wordpress-seo is active:
      """
    And STDOUT should contain:
      """
      https://example.com/
      """

  Scenario: Plugin is active on multiple sites
    Given I run `wp site create --slug=second-site`
    And I run `wp plugin activate wordpress-seo`
    And I run `wp plugin activate wordpress-seo --url=example.com/second-site`
    When I run `wp plugin active-on-sites wordpress-seo`
    Then STDOUT should contain:
      """
      Sites where wordpress-seo is active:
      """
    And STDOUT should contain:
      """
      https://example.com/
      """
    And STDOUT should contain:
      """
      second-site
      """
