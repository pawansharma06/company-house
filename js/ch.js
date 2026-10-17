jQuery(function($) {
    'use strict';

    var currentCompanyNumber = '';

    /**
     * Escape HTML entities to prevent DOM XSS.
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Join non-empty address parts cleanly.
     */
    function formatAddress(addrObj) {
        if (!addrObj || typeof addrObj !== 'object') {
            return 'N/A';
        }
        var parts = [
            addrObj.premises,
            addrObj.address_line_1,
            addrObj.address_line_2,
            addrObj.locality,
            addrObj.region,
            addrObj.postal_code,
            addrObj.country
        ].filter(function(part) {
            return part && String(part).trim() !== '';
        });

        return parts.length ? escapeHtml(parts.join(', ')) : 'N/A';
    }

    /**
     * Render loading spinner HTML.
     */
    function getSpinnerHtml() {
        var src = (typeof chsp_ajax_obj !== 'undefined' && chsp_ajax_obj.loading_img)
            ? chsp_ajax_obj.loading_img
            : '';
        if (src) {
            return '<img src="' + escapeHtml(src) + '" alt="Loading..." />';
        }
        return '<span class="ch_loading_text">Loading...</span>';
    }

    /**
     * Display or clear error message.
     */
    function showError(message) {
        var $err = $('#ch_error_msg');
        if (!message) {
            $err.hide().text('');
            return;
        }
        $err.text(message).show();
    }

    /**
     * Render search result rows depending on search type.
     */
    function renderSearchResults(items, searchType) {
        if (!items || !items.length) {
            return '<tr><td class="ch_no_results">No matching records found.</td></tr>';
        }

        var html = '';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];

            if (searchType === 'search_companies') {
                var companyNum = escapeHtml(item.company_number || '');
                var title = escapeHtml(item.title || 'Unnamed Company');
                var snippet = escapeHtml(item.snippet || '');
                var description = escapeHtml(item.description || '');
                var addressSnippet = escapeHtml(item.address_snippet || '');
                var status = escapeHtml(item.company_status || '');

                html += '<tr><td>';
                html += '<div class="ch_result_header">';
                html += '<a href="#" data-comid="' + companyNum + '" class="ch-company"><strong>' + title + '</strong></a>';
                if (status) {
                    html += ' <span class="ch_status_pill ch_status_' + status.toLowerCase() + '">' + status + '</span>';
                }
                html += '</div>';
                if (description || snippet) {
                    html += '<p class="ch-fade">';
                    if (description) {
                        html += description;
                    }
                    if (snippet) {
                        html += (description ? '<br/>' : '') + '<small>Matching previous/other info: ' + snippet + '</small>';
                    }
                    html += '</p>';
                }
                if (addressSnippet) {
                    html += '<p class="ch_address_line">' + addressSnippet + '</p>';
                }
                html += '</td></tr>';

            } else if (searchType === 'search_officers') {
                var officerTitle = escapeHtml(item.title || 'Officer');
                var officerDesc = escapeHtml(item.description || '');
                var officerAddress = escapeHtml(item.address_snippet || '');
                var officerSnippet = escapeHtml(item.snippet || '');

                html += '<tr><td>';
                html += '<div class="ch_result_header"><strong class="ch_officer_title">' + officerTitle + '</strong></div>';
                if (officerDesc || officerSnippet) {
                    html += '<p class="ch-fade">' + officerDesc + (officerSnippet ? '<br/>' + officerSnippet : '') + '</p>';
                }
                if (officerAddress) {
                    html += '<p class="ch_address_line">' + officerAddress + '</p>';
                }
                html += '</td></tr>';

            } else if (searchType === 'search_disqualified') {
                var dqTitle = escapeHtml(item.title || 'Disqualified Officer');
                var dqDesc = escapeHtml(item.description || '');
                var dqAddress = escapeHtml(item.address_snippet || '');
                var selfLink = (item.links && item.links.self) ? escapeHtml(item.links.self) : '';

                html += '<tr><td>';
                html += '<div class="ch_result_header">';
                if (selfLink) {
                    html += '<a href="#" data-resource="' + selfLink + '" class="ch-disqualified-officer"><strong>' + dqTitle + '</strong></a>';
                } else {
                    html += '<strong>' + dqTitle + '</strong>';
                }
                html += ' <span class="ch_status_pill ch_status_dissolved">Disqualified</span>';
                html += '</div>';
                if (dqDesc) {
                    html += '<p class="ch-fade">' + dqDesc + '</p>';
                }
                if (dqAddress) {
                    html += '<p class="ch_address_line">' + dqAddress + '</p>';
                }
                html += '</td></tr>';
            }
        }
        return html;
    }

    /**
     * Render pagination links (up to 10 pages as limited by Companies House search window).
     */
    function renderPagination(totalResults, currentPage, query, searchType) {
        var totalPages = Math.ceil((totalResults || 0) / 10);
        var maxPages = Math.min(totalPages, 10);
        if (maxPages <= 1) {
            $('#ch-pagination').html('');
            return;
        }

        var pagination = '';
        for (var y = 1; y <= maxPages; y++) {
            var pageIndex = y - 1;
            var activeClass = (pageIndex === currentPage) ? ' class="active"' : '';
            pagination += '<li><a href="#"' + activeClass +
                ' data-page="' + pageIndex + '"' +
                ' data-query="' + escapeHtml(query) + '"' +
                ' data-type="' + escapeHtml(searchType) + '">' + y + '</a></li>';
        }
        $('#ch-pagination').html(pagination);
    }

    /**
     * Perform search request via WordPress AJAX proxy.
     */
    function executeSearch(query, searchType, page) {
        if (!query || !query.trim()) {
            showError('Please enter a search term.');
            return;
        }

        showError('');
        $('#loading').html(getSpinnerHtml());

        $.ajax({
            url: chsp_ajax_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'chsp_api_request',
                nonce: chsp_ajax_obj.nonce,
                request_type: searchType,
                q: query.trim(),
                page: page
            },
            success: function(response) {
                $('#loading').html('');
                if (!response || !response.success) {
                    var msg = (response && response.data && response.data.message)
                        ? response.data.message
                        : 'Unable to retrieve search results.';
                    showError(msg);
                    return;
                }

                var data = response.data || {};
                var items = data.items || [];
                var totalResults = data.total_results || items.length || 0;

                $('#ch_search_data').html(renderSearchResults(items, searchType));
                $('#ch_total_result').text(totalResults + ' matches found');
                $('#ch_page').text(totalResults > 0 ? ' | Page: ' + (page + 1) : '');
                renderPagination(totalResults, page, query.trim(), searchType);
            },
            error: function(xhr) {
                $('#loading').html('');
                var msg = 'Error communicating with the server.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                showError(msg);
            }
        });
    }

    // Search Category Tab Switching
    $(document.body).on('click', '.ch_tab_btn', function() {
        var $btn = $(this);
        $('.ch_tab_btn').removeClass('active');
        $btn.addClass('active');

        var searchType = $btn.data('type');
        var placeholder = $btn.data('placeholder');

        $('#ch_search_type').val(searchType);
        $('#company_name').attr('placeholder', placeholder);

        // If user already typed a query, run search for the new tab automatically.
        var currentQuery = $('#company_name').val();
        if (currentQuery && currentQuery.trim() !== '') {
            executeSearch(currentQuery, searchType, 0);
        } else {
            $('#ch_search_data').html('');
            $('#ch_total_result').text('');
            $('#ch_page').text('');
            $('#ch-pagination').html('');
            showError('');
        }
    });

    // Search Form Submit
    $(document.body).on('submit', '#ch_companysearch', function(e) {
        e.preventDefault();
        var query = $('#company_name').val();
        var searchType = $('#ch_search_type').val() || 'search_companies';
        executeSearch(query, searchType, 0);
    });

    // Pagination Click
    $(document.body).on('click', '#ch-pagination a', function(e) {
        e.preventDefault();
        var page = parseInt($(this).data('page'), 10) || 0;
        var query = $(this).data('query');
        var searchType = $(this).data('type') || $('#ch_search_type').val() || 'search_companies';
        executeSearch(query, searchType, page);
    });

    // Close Modal
    function closeModal() {
        $('#ch_popup_overlay').fadeOut(150);
    }

    $(document.body).on('click', '#ch_popup_info_close', function(e) {
        e.preventDefault();
        closeModal();
    });

    $(document.body).on('click', '#ch_popup_overlay', function(e) {
        if ($(e.target).is('#ch_popup_overlay')) {
            closeModal();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#ch_popup_overlay').is(':visible')) {
            closeModal();
        }
    });

    // Modal Internal Tab Switching (Overview vs Officers)
    $(document.body).on('click', '.ch_modal_tab', function() {
        var target = $(this).data('target');
        $('.ch_modal_tab').removeClass('active');
        $(this).addClass('active');
        $('.ch_modal_pane').hide();
        $(target).show();

        if (target === '#ch_modal_officers' && currentCompanyNumber) {
            loadCompanyOfficers(currentCompanyNumber);
        }
    });

    /**
     * Load Company Officers into Modal Tab.
     */
    function loadCompanyOfficers(companyNumber) {
        var $container = $('#ch_officers_list_content');
        if ($container.data('loaded-for') === companyNumber) {
            return;
        }

        $container.html('<div class="ch_loading">' + getSpinnerHtml() + '</div>');

        $.ajax({
            url: chsp_ajax_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'chsp_api_request',
                nonce: chsp_ajax_obj.nonce,
                request_type: 'company_officers',
                company_number: companyNumber
            },
            success: function(response) {
                if (!response || !response.success || !response.data || !response.data.items) {
                    $container.html('<p>No officers listed for this company.</p>');
                    return;
                }

                var items = response.data.items;
                if (!items.length) {
                    $container.html('<p>No officers listed for this company.</p>');
                    return;
                }

                var html = '<table class="ch_sub_table"><thead><tr><th>Name</th><th>Role</th><th>Appointed</th><th>Status</th></tr></thead><tbody>';
                for (var i = 0; i < items.length; i++) {
                    var off = items[i];
                    var name = escapeHtml(off.name || 'N/A');
                    var role = escapeHtml(off.officer_role || 'N/A');
                    var appointed = escapeHtml(off.appointed_on || 'N/A');
                    var resigned = off.resigned_on ? 'Resigned (' + escapeHtml(off.resigned_on) + ')' : 'Active';

                    html += '<tr>';
                    html += '<td><strong>' + name + '</strong></td>';
                    html += '<td>' + role + '</td>';
                    html += '<td>' + appointed + '</td>';
                    html += '<td>' + resigned + '</td>';
                    html += '</tr>';
                }
                html += '</tbody></table>';
                $container.html(html).data('loaded-for', companyNumber);
            },
            error: function() {
                $container.html('<p>Could not load company officers.</p>');
            }
        });
    }

    // Click Company Result to View Details Modal
    $(document.body).on('click', '.ch-company', function(e) {
        e.preventDefault();
        var companyNumber = $(this).data('comid');
        if (!companyNumber) {
            return;
        }

        currentCompanyNumber = companyNumber;
        $('#ch_officers_list_content').data('loaded-for', '').empty();

        // Reset modal view to Overview tab
        $('#ch_modal_tabs').show();
        $('.ch_modal_tab').removeClass('active').first().addClass('active');
        $('.ch_modal_pane').hide();
        $('#ch_modal_overview').show();

        $('#ch_popup_info_data').hide();
        $('#ch_company_name').text('Loading...');
        $('#floatloading').html(getSpinnerHtml());
        $('#ch_popup_overlay').fadeIn(150);

        $.ajax({
            url: chsp_ajax_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'chsp_api_request',
                nonce: chsp_ajax_obj.nonce,
                request_type: 'company_detail',
                company_number: companyNumber
            },
            success: function(response) {
                $('#floatloading').html('');
                if (!response || !response.success || !response.data) {
                    $('#ch_company_name').text('Error loading company details');
                    return;
                }

                var data = response.data;
                $('#ch_company_name').text(data.company_name || 'N/A');
                $('#ch_company_number').text(data.company_number || 'N/A');
                $('#ch_company_address').html(formatAddress(data.registered_office_address));
                $('#ch_company_status').text(data.company_status || 'N/A');
                $('#ch_company_type').text(data.type || 'N/A');
                $('#ch_company_incorporation_on').text(data.date_of_creation || 'N/A');

                // Accounts (null-safe)
                var accounts = data.accounts || {};
                var lastAccounts = accounts.last_accounts || {};
                var nextAccounts = accounts.next_accounts || {};
                $('#period_end_on').text(nextAccounts.period_end_on || lastAccounts.period_end_on || 'N/A');
                $('#due_on').text(nextAccounts.due_on || 'N/A');
                $('#made_up_to').text(lastAccounts.made_up_to || 'N/A');

                // Confirmation Statement (null-safe)
                var confStmt = data.confirmation_statement || {};
                $('#next_made_up_to').text(confStmt.next_made_up_to || 'N/A');
                $('#next_due').text(confStmt.next_due || 'N/A');
                $('#last_made_up_to').text(confStmt.last_made_up_to || 'N/A');

                // SIC codes (null-safe, supports multiple codes)
                if (Array.isArray(data.sic_codes) && data.sic_codes.length > 0) {
                    $('#sic_codes').text(data.sic_codes.join(', '));
                } else {
                    $('#sic_codes').text('None supplied');
                }

                // Previous company names (null-safe)
                var prevNames = data.previous_company_names || [];
                if (prevNames.length > 0) {
                    var prevHtml = '<thead><tr><th>Name</th><th>Period</th></tr></thead><tbody>';
                    for (var x = 0; x < prevNames.length; x++) {
                        prevHtml += '<tr><td>' + escapeHtml(prevNames[x].name || '') + '</td><td>' +
                            escapeHtml(prevNames[x].effective_from || '') + ' &ndash; ' +
                            escapeHtml(prevNames[x].ceased_on || '') + '</td></tr>';
                    }
                    prevHtml += '</tbody>';
                    $('#previous_company').html(prevHtml);
                    $('#ch_previous_names_wrap').show();
                } else {
                    $('#previous_company').html('');
                    $('#ch_previous_names_wrap').hide();
                }

                $('#ch_popup_info_data').show();
            },
            error: function() {
                $('#floatloading').html('');
                $('#ch_company_name').text('Could not load company profile.');
            }
        });
    });

    // Click Disqualified Officer Result to View Disqualification Details
    $(document.body).on('click', '.ch-disqualified-officer', function(e) {
        e.preventDefault();
        var resourcePath = $(this).data('resource');
        if (!resourcePath) {
            return;
        }

        $('#ch_modal_tabs').hide();
        $('.ch_modal_pane').hide();
        $('#ch_modal_disqualified').show();
        $('#ch_popup_info_data').hide();
        $('#ch_company_name').text('Loading...');
        $('#floatloading').html(getSpinnerHtml());
        $('#ch_popup_overlay').fadeIn(150);

        $.ajax({
            url: chsp_ajax_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'chsp_api_request',
                nonce: chsp_ajax_obj.nonce,
                request_type: 'disqualified_detail',
                resource_path: resourcePath
            },
            success: function(response) {
                $('#floatloading').html('');
                if (!response || !response.success || !response.data) {
                    $('#ch_company_name').text('Error loading disqualification details');
                    return;
                }

                var d = response.data;
                var fullName = [d.title, d.forename, d.other_forenames, d.surname].filter(Boolean).join(' ') || d.name || 'Disqualified Officer';
                $('#ch_company_name').text(fullName);

                var html = '<div class="ch_detail_grid">';
                if (d.date_of_birth) {
                    html += '<div><strong>Date of Birth:</strong> ' + escapeHtml(d.date_of_birth) + '</div>';
                }
                if (d.nationality) {
                    html += '<div><strong>Nationality:</strong> ' + escapeHtml(d.nationality) + '</div>';
                }
                html += '</div><hr/>';

                var disqualifications = d.disqualifications || [];
                if (disqualifications.length > 0) {
                    html += '<strong>Disqualifications (' + disqualifications.length + ')</strong>';
                    html += '<table class="ch_sub_table"><thead><tr><th>From</th><th>Until</th><th>Reason / Act</th><th>Companies</th></tr></thead><tbody>';
                    for (var i = 0; i < disqualifications.length; i++) {
                        var dq = disqualifications[i];
                        var reason = (dq.reason && dq.reason.act) ? (dq.reason.act + ' (' + (dq.reason.description_identifier || '') + ')') : 'N/A';
                        var companies = Array.isArray(dq.company_names) ? dq.company_names.join(', ') : 'N/A';
                        html += '<tr>';
                        html += '<td>' + escapeHtml(dq.disqualified_from || 'N/A') + '</td>';
                        html += '<td>' + escapeHtml(dq.disqualified_until || 'N/A') + '</td>';
                        html += '<td>' + escapeHtml(reason) + '</td>';
                        html += '<td>' + escapeHtml(companies) + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                } else {
                    html += '<p>No active disqualification records found.</p>';
                }

                $('#ch_disqualified_detail_content').html(html);
                $('#ch_popup_info_data').show();
            },
            error: function() {
                $('#floatloading').html('');
                $('#ch_company_name').text('Could not load disqualification profile.');
            }
        });
    });
});