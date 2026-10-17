=== Company House Search ===
Contributors: pawansharma06
Tags: companies-house, company-search, gov-uk, hmrc, uk-companies
Donate link: https://www.paypal.com/paypalme/pawan6161
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search UK Companies House records including companies, officers, and disqualified officers directly on your WordPress website via shortcode.

== Description ==
Company House Search enables UK Companies House search directly on your WordPress site using a simple `[company_house]` shortcode powered by the official GOV.UK Companies House REST API.

= Features =
* Search UK Companies by name or company registration number
* Search Company Officers
* Search Disqualified Officers and view disqualification records
* View detailed company profiles (Registered Address, Status, Type, Incorporation Date, Accounts, Confirmation Statements, SIC Codes, Previous Company Names, and Active/Resigned Officers)
* Server-side WordPress AJAX proxy keeps your Companies House API Key hidden from frontend visitors

== 3rd Party Service ==
1. A free UK Companies House REST API Key is required for the plugin to work ([https://developer.company-information.service.gov.uk/](https://developer.company-information.service.gov.uk/)).
2. This plugin communicates with `https://api.company-information.service.gov.uk/` to fetch search results and company profiles.
3. The plugin does not store any visitor search queries or personal data.
4. Information provided by Companies House is licensed under the [Open Government Licence v3.0](https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/).

== Installation ==
1. Upload the `company-house` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **Company House** menu in your WordPress admin panel and enter your Companies House REST API Key.
4. Place the `[company_house]` shortcode on any page or post.

== Frequently Asked Questions ==
= How do I display the search form? =
Add the shortcode `[company_house]` to any page, post, or shortcode block.

= Do you have questions or issues with Company House Search? =
Drop an email to: info@planetarymarketing.in

== Changelog ==
= 2.0.0 =
* Security: Moved Companies House API calls to a server-side WordPress AJAX endpoint so the API key is never exposed in frontend HTML.
* Added: Support for searching Officers and Disqualified Officers, plus a Company Officers tab inside the company profile modal.
* Fixed: Shortcode now properly uses output buffering (`ob_start()` / `ob_get_clean()`) instead of echoing directly.
* Fixed: Pagination off-by-one bug and null-safety for missing company fields.
* Improved: Scoped CSS styles under `.ch_wrapper` to prevent theme button conflicts, and only enqueue assets on pages using the shortcode.

= 1.0 =
* Initial release.
