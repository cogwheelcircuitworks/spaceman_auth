# spaceman_auth
Integrates nova labs spaceman with wordpress. 

This is a wordpress plugin-in which does the following:

* Generates a login page
* Accepts username and password
* Makes an authorization request to nova labs spaceman
* If successeful, passes Authorization Cookie to browser session, permitting navigation to spaceman pages which require authorization


## Installation

* Copy to your wp-content/plugins directory and activate via the wordpress administrative UI.
* Create a page containing the shortcode *[spaceman_auth_login]*


