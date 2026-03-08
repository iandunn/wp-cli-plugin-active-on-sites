Feature: Validate single-site installs are rejected

  Background:
    Given a WP install

  Scenario: Command errors on single-site installs
    When I try `wp plugin active-on-sites hello-dolly`
    Then STDERR should contain:
      """
      This only works on Multisite installations.
      """
    And the return code should be 1
