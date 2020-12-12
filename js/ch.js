jQuery(function() {

    jQuery(document.body).on('submit', '#ch_companysearch', function(e) {
        e.preventDefault();
        var q = jQuery("#company_name").val();
        var key = jQuery("#key").val();
        jQuery.ajax({
            url: "https://api.companieshouse.gov.uk/search/companies/?q=" + q + "&items_per_page=10&start_index=0",
            type: "GET",
            crossDomain: true,
            contentType: "application/json",
            dataType: 'json',
            success: function(data) {
                var datahtml = '';
                jQuery("#loading").html('');
                for (var x = 0; x < data.items.length; x++) {
                    datahtml += '<tr><td><a href="#" data-comid="' + data.items[x].company_number + '" class="ch-company"><strong>' + data.items[x].title + '</strong></a><br/><p class="ch-fade">Matching previous names:<br/>' + data.items[x].snippet + '<br/>' + data.items[x].description + '</p><p>' + data.items[x].address_snippet + '</p></td></tr>';
                }
                jQuery('#ch_search_data').html(datahtml);
                jQuery('#ch_total_result').html(data.total_results + " matches found ");
                var pages = Math.ceil(data.total_results / 10);
                var pagination = "";
                if (pages > 10) {
                    var y = 1;
                    while (y !== 11) {
                        pagination += '<li><a href="#" data-page="' + (y - 1) + '" data-query="' + q + '" >' + y + '</a></li>';
                        y++;
                    }
                    jQuery('#ch-pagination').html(pagination);
                } else {
                    var y = 1;
                    while (y != pages) {
                        pagination += '<li><a href="#" data-page="' + (y - 1) + '" data-query="' + q + '" >' + y + '</a></li>';
                        y++;
                    }
                    jQuery('#ch-pagination').html(pagination);
                }
            },
            beforeSend: function(xhr) {
                jQuery("#loading").html('<img src="/wp-content/plugins/company-house/images/loading.gif">');
                xhr.setRequestHeader("Authorization", "Basic " + btoa(myTrim(key) + ":"));
            },
            error: function() {}
        });

    });
    jQuery(document.body).on('click', '#ch-pagination a', function(e) {
        jQuery('#ch-pagination a').css("background", "#fff");
        jQuery(this).css("background", "#c2c2c2");
        var key = jQuery("#key").val();
        var items_per_page = 10;
        var page = jQuery(this).data('page');
        var q = jQuery(this).data('query');
        jQuery.ajax({
            url: "https://api.companieshouse.gov.uk/search/companies/?q=" + q + "&items_per_page=" + items_per_page + "&start_index=" + (page * items_per_page),
            type: "GET",
            crossDomain: true,
            contentType: "application/json",
            dataType: 'json',
            success: function(data) {
                var datahtml = '';
                jQuery("#loading").html('');
                for (var x = 0; x < data.items.length; x++) {
                    datahtml += '<tr><td><a href="#" data-comid="' + data.items[x].company_number + '" class="ch-company"><strong>' + data.items[x].title + '</strong></a><br/><p class="ch-fade">Matching previous names:<br/>' + data.items[x].snippet + '<br/>' + data.items[x].description + '</p><p>' + data.items[x].address_snippet + '</p></td></tr>';
                }
                jQuery('#ch_search_data').html(datahtml);
                jQuery('#ch_page').html("&nbsp;| Page :" + data.page_number);
            },
            beforeSend: function(xhr) {
                jQuery("#loading").html('<img src="/wp-content/plugins/company-house/images/loading.gif">');
                xhr.setRequestHeader("Authorization", "Basic " + btoa(myTrim(key) + ":"));
            },
            error: function() {}
        });
    });
    jQuery(document.body).on('click', '#ch_popup_info_close', function(e) {
        jQuery('.ch_popup_info').css("display", "none");
    });
    jQuery(document.body).on('click', '.ch-company', function(e) {
        e.preventDefault();
        var key = jQuery("#key").val();
        var company_number = jQuery(this).data('comid');
        jQuery.ajax({
            url: "https://api.companieshouse.gov.uk/company/" + company_number,
            type: "GET",
            crossDomain: true,
            contentType: "application/json",
            dataType: 'json',
            success: function(data) {
                jQuery("#floatloading").html('');
                jQuery("#ch_company_name").html(data.company_name);
                jQuery("#ch_company_number").html(data.company_number);
                jQuery("#ch_company_address").html(data.registered_office_address.address_line_1 + ',' + data.registered_office_address.address_line_2 + ',' + data.registered_office_address.locality + ',' + data.registered_office_address.postal_code + ',' + data.registered_office_address.region);
                jQuery("#ch_company_status").html(data.company_status);
                jQuery("#ch_company_type").html(data.type);
                jQuery("#ch_company_incorporation_on").html(data.date_of_creation);
                jQuery("#period_end_on").html(data.accounts.last_accounts.period_end_on);
                jQuery("#due_on").html(data.accounts.next_accounts.due_on);
                jQuery("#made_up_to").html(data.accounts.last_accounts.made_up_to);
                jQuery("#next_made_up_to").html(data.confirmation_statement.next_made_up_to);
                jQuery("#next_due").html(data.confirmation_statement.next_due);
                jQuery("#last_made_up_to").html(data.confirmation_statement.last_made_up_to);
                jQuery("#sic_codes").html(data.sic_codes[0]);
                var previous_company = '<tr><th>Name</th><th>Period</th></tr>';
                for (var x = 0; x < data.previous_company_names.length; x++) {
                    previous_company += '<tr><td>' + data.previous_company_names[x].name + '</td><td>' + data.previous_company_names[x].effective_from + ' - ' + data.previous_company_names[x].ceased_on + '</td></tr>';
                }
                jQuery("#previous_company").html(previous_company);
            },
            beforeSend: function(xhr) {
                jQuery('.ch_popup_info').css("display", "block");
                jQuery("#floatloading").html('<img src="/wp-content/plugins/company-house/images/loading.gif">');
                xhr.setRequestHeader("Authorization", "Basic " + btoa(myTrim(key) + ":"));
            },
            error: function() {}
        });
    });

    function myTrim(x) {
        return x.replace(/^\s+|\s+$/gm, '');
    }


});