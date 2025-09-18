@paygw @paygw_mpesakenya @secrets
Feature: M-Pesa Kenya payment gateway test

  In order to control student access to courses
  I need to be able to add an M-Pesa Kenya payment gateway

  Background:
    Given the following "users" exist:
      | username | phone2    | country |
      | student1 | 712345678 | KE      |
      | student2 | 723456789 | KE      |
      | manager1 | 734567890 | KE      |
    And the following "courses" exist:
      | fullname | shortname |
      | Test Course 1 | TC1      |
      | Test Course 2 | TC2      |
    And the following "activities" exist:
      | activity | name      | course | idnumber |
      | page     | Page1     | TC1    | page1    |
      | page     | Page2     | TC2    | page2    |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | manager1 | TC1    | manager |
      | manager1 | TC2    | manager |
    And the following "core_payment > payment accounts" exist:
      | name           | gateways     |
      | M-Pesa Account | mpesakenya   |
    And I log in as "admin"
    And I configure mpesa
    And I add "Enrolment on payment" enrolment method in "Test Course 1" with:
      | Payment account | M-Pesa Account  |
      | Enrolment fee   | 500             |
      | Currency        | Kenyan Shilling |
    And I add "Enrolment on payment" enrolment method in "Test Course 2" with:
      | Payment account | M-Pesa Account  |
      | Enrolment fee   | 500             |
      | Currency        | Kenyan Shilling |
    And I log out

  @javascript
  Scenario: Student can make a payment using M-Pesa
    When I log in as "student1"
    And I am on course index
    And I follow "Test Course 1"
    Then I should see "This course requires a payment for entry"
    And I press "Select payment type"
    And I should see "M-Pesa Kenya" in the "Select payment type" "dialogue"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I should see "M-Pesa Payment"
    And I set the field "Phone Number" to "712345678"
    And I press "Pay with M-Pesa"
    Then I should see "Processing payment"
    And I wait until the page is ready
    And I should see "Payment successful"
    And I should be enrolled in course "Test Course 1"

  @javascript
  Scenario: Student can cancel a payment
    When I log in as "student1"
    And I am on course index
    And I follow "Test Course 1"
    Then I should see "This course requires a payment for entry"
    And I press "Select payment type"
    And I should see "M-Pesa Kenya" in the "Select payment type" "dialogue"
    And I click on "Cancel" "button" in the "Select payment type" "dialogue"
    Then I should not be enrolled in course "Test Course 1"

  @javascript
  Scenario: Student sees error for invalid phone number
    When I log in as "student1"
    And I am on course index
    And I follow "Test Course 1"
    Then I should see "This course requires a payment for entry"
    And I press "Select payment type"
    And I should see "M-Pesa Kenya" in the "Select payment type" "dialogue"
    And I click on "Proceed" "button" in the "Select payment type" "dialogue"
    And I should see "M-Pesa Payment"
    And I set the field "Phone Number" to "123"
    And I press "Pay with M-Pesa"
    Then I should see "Please enter a valid M-Pesa phone number"
    And I should not be enrolled in course "Test Course 1"
