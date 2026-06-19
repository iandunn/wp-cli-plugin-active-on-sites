Feature: Validate single-site installs are rejected

  Background:
    Given a WP install

  Scenario: Command errors on single-site installs
    When I try `wp plugin active-on-sites wordpress-seo`
    Then STDERR should contain:
      """
      This only works on Multisite installations.
      """
    And the return code should be 1

  Scenario: --none flag also errors on single-site installs
    When I try `wp plugin active-on-sites --none`
    Then STDERR should contain:
      """
      This only works on Multisite installations.
      """
    And the return code should be 1
