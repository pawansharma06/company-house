# Company House Search – WordPress Plugin

A secure, lightweight WordPress plugin that integrates with the official [UK Companies House Public Data API](https://developer-specs.company-information.service.gov.uk/companies-house-public-data-api/reference) to let visitors search UK companies, company officers, and disqualified officers directly on your website via a simple shortcode.

---

## Features

- **Company Search**: Search registered UK companies by company name or registration number (`GET /search/companies`).
- **Company Profile Modal**: View detailed company profiles (`GET /company/{companyNumber}`) including:
  - Registered Office Address, Company Status, Company Type, and Incorporation Date
  - Next & Last Accounts dates
  - Confirmation Statement dates
  - Nature of Business (SIC codes)
  - Previous Company Names & effective periods
- **Company Officers Tab**: View active and resigned officers for any selected company (`GET /company/{company_number}/officers`).
- **Officer Search**: Search company officers by name (`GET /search/officers`).
- **Disqualified Officers Search**: Search disqualified officers (`GET /search/disqualified-officers`) and inspect full natural/corporate disqualification records (`GET /disqualified-officers/natural/{officer_id}` & `/disqualified-officers/corporate/{officer_id}`).
- **Secure Server-Side API Proxy**: All requests are routed through WordPress (`admin-ajax.php`) using HTTP Basic Authentication (`base64_encode( $api_key . ':' )`). Your API key is stored on the server and **never exposed** in frontend HTML or browser network traffic.
- **Smart Key Compatibility**: Automatically supports Live REST keys, Web (JavaScript origin) keys, and Sandbox/Test keys.
- **Scoped & Responsive Styling**: All CSS rules are scoped under `.ch_wrapper` so they will not conflict with your WordPress theme.

---

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.2 or higher
- **Companies House API Key**: Free registration at the [Companies House Developer Hub](https://developer.company-information.service.gov.uk/manage-applications)

---

## Installation

1. Clone or download this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/pawansharma06/company-house.git
   ```
   *(Or upload the `company-house` folder to `/wp-content/plugins/` via FTP or zip upload).*
2. Activate **Company House Search** from the **Plugins** menu in WordPress Admin.
3. Navigate to **Company House** in the WordPress Admin sidebar.
4. Paste your **Companies House API Key** and click **Save Settings**.

---

## Generating a Companies House API Key

1. Sign in to the [Companies House Developer Hub](https://developer.company-information.service.gov.uk/manage-applications).
2. Create an application (choose **Live** environment for real company data).
3. Create an API key:
   - Choose **REST** as the application type (recommended for server-side requests).
   - **Tip for Local Development (XAMPP / localhost)**: Leave **Restricted IPs** blank while testing locally so your local IP address is not rejected with an `HTTP 400` error.
4. Copy the generated API key and paste it into **WP Admin → Company House → API Key**.

---

## Usage

### Shortcode
Add the shortcode to any page, post, or widget block:

```text
[company_house]
```

### PHP Template
To render the search interface directly inside a theme template file:

```php
<?php echo do_shortcode( '[company_house]' ); ?>
```

---

## Customization

All frontend elements are wrapped inside `.ch_wrapper` and styled via [`css/ch.css`](css/ch.css). You can override any style in your theme's **Additional CSS**:

- `.ch_wrapper .ch_search_form` – Search form wrapper
- `.ch_wrapper .ch_tab_btn` – Category tabs (`Companies`, `Officers`, `Disqualified Officers`)
- `.ch_wrapper .ch-btn` – Search submit button
- `.ch_wrapper .ch_results_table` – Results table
- `.ch_wrapper .ch_popup_info` – Company / Officer details modal dialog

---

## Changelog

### 2.0.1
- **Security**: Moved all Companies House API requests to a server-side WordPress AJAX proxy (`wp_ajax_ch_api_request`) with nonce verification so the API key is never exposed in frontend HTML.
- **Added**: Search tabs for **Companies**, **Officers**, and **Disqualified Officers**.
- **Added**: **Officers** tab inside the company profile modal and full disqualification details modal.
- **Improved**: Automatic authorization fallback for REST keys, Web keys (`Origin` header), and Sandbox keys, plus RFC 3986 query encoding and detailed error messages.
- **Fixed**: Shortcode output buffering (`ob_start()` / `ob_get_clean()`), pagination off-by-one bug, null-safe property handling in JavaScript, and scoped CSS selectors.

### 1.0
- Initial release.

---

## License & Attribution

- Licensed under the [GPL v2 or later](LICENSE).
- Public data retrieved from Companies House is licensed under the [Open Government Licence v3.0](https://www.nationalarchives.gov.uk/doc/open-government-licence/version/3/).

## Contact & Support

- **Author**: Pawan Sharma ([Planetary Marketing](https://planetarymarketing.in))
- **Email**: [info@planetarymarketing.in](mailto:info@planetarymarketing.in)
