Feature: List plugins that are not active on any site

  Background:
    Given a WP multisite install
    And I run `wp plugin install wordpress-seo --force`

  Scenario: Lists plugins inactive on all sites
    When I run `wp plugin active-on-sites --none`
    Then STDOUT should contain:
      """
      Plugins not active on any site:
      """
    And STDOUT should contain:
      """
      wordpress-seo
      """

  Scenario: Excludes plugins active on at least one site
    Given I run `wp plugin activate wordpress-seo`
    When I run `wp plugin active-on-sites --none`
    Then STDOUT should not contain:
      """
      wordpress-seo
      """

  @network
  Scenario: Silently excludes network-activated plugins
    Given I run `wp plugin activate wordpress-seo --network`
    When I run `wp plugin active-on-sites --none`
    Then STDOUT should not contain:
      """
      wordpress-seo
      """
    And STDERR should be empty
