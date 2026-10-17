=== Planetary Company Search for Companies House ===
Contributors: pawan6161
Tags: companies-house, company-search, uk-company, business-directory, company-lookup
Donate link: https://www.paypal.com/paypalme/pawan6161
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Search UK Companies House records including companies, officers, and disqualified officers directly on your WordPress website via shortcode.

== Description ==
Company House Search enables UK Companies House search directly on your WordPress site using a simple `[company_house]` shortcode powered by the official GOV.UK Companies House Public Data API.

= Features =
* Search UK Companies by name or company registration number
* Search Company Officers
* Search Disqualified Officers and view disqualification records
* View detailed company profiles (Registered Address, Status, Type, Incorporation Date, Accounts, Confirmation Statements, SIC Codes, Previous Company Names, and Active/Resigned Officers)
* Server-side WordPress AJAX proxy keeps your Companies House API Key hidden from frontend visitors

== External services ==
This plugin connects to the official UK Government **Companies House Public Data API** to retrieve public company, officer, and disqualified officer information when a user performs a search or clicks a company/officer record on a page containing the `[company_house]` shortcode.

* **Service**: UK Companies House Public Data API
* **API Endpoints**: `https://api.company-information.service.gov.uk` and `https://api-sandbox.company-information.service.gov.uk`
* **Data sent**: The search query term, company registration number, or officer resource path requested by the user, along with the site administrator's Companies House API key in the HTTP `Authorization` header. No personal visitor data is stored by this plugin.
* **Terms of Use / Licence**: [Open Government Licence v3.0](https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/) and [Companies House Developer Guidelines](https://developer-specs.company-information.service.gov.uk/guides/developerGuidelines)
* **Privacy Policy**: [Companies House Personal Information Charter / Privacy Notice](https://www.gov.uk/government/organisations/companies-house/about/personal-information-charter)

== Installation ==
1. Upload the `company-house` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **Company House** menu in your WordPress admin panel and enter your Companies House REST API Key.
4. Place the `[company_house]` shortcode on any page or post.

== Frequently Asked Questions ==
= How do I display the search form? =
Add the shortcode `[company_house]` to any page, post, or shortcode block.

= Where do I get a Companies House API key? =
You can register a free account and generate an API key at the [Companies House Developer Hub](https://developer.company-information.service.gov.uk/manage-applications).

= Do you have questions or issues with Company House Search? =
Drop an email to: info@planetarymarketing.in

== Changelog ==
= 2.0.1 =
* Security: Moved Companies House API calls to a server-side WordPress AJAX endpoint so the API key is never exposed in frontend HTML.
* Added: Support for searching Officers and Disqualified Officers, plus a Company Officers tab inside the company profile modal.
* Fixed: Shortcode now properly uses output buffering (`ob_start()` / `ob_get_clean()`) instead of echoing directly.
* Fixed: Pagination off-by-one bug and null-safety for missing company fields.
* Improved: Scoped CSS styles under `.ch_wrapper`, standardized 4-character PHP prefixes (`CHSP_` / `chsp_`), and added External Services disclosure for WordPress.org compliance.

= 1.0 =
* Initial release.
